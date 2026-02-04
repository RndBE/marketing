<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemDetail;
use Illuminate\Support\Facades\Storage;
use App\Models\InvoiceSignature;
use App\Models\InvoiceTerm;
use App\Models\Product;
use App\Models\DocNumber;
use App\Models\Penawaran; // Added import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $data = Invoice::query()
            ->whereNull('parent_id') // Hide Child Invoices (Termins) from main list
            ->with(['docNumber', 'user'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('judul', 'like', "%{$q}%")
                    ->orWhereHas('docNumber', fn($qq) => $qq->where('doc_no', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('data', 'q'));
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'tgl_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tgl_invoice'],
            'manual_item_name' => ['nullable', 'string', 'required_if:mode,manual'],
            'manual_item_price' => ['nullable', 'numeric', 'min:0', 'required_if:mode,manual'],
        ]);

        return DB::transaction(function () use ($payload) {
            $docNumber = $this->createDocNumber(auth()->id());

            $invoice = Invoice::create([
                'user_id' => auth()->id(),
                'doc_number_id' => $docNumber->id,
                'judul' => $payload['judul'],
                'catatan' => $payload['catatan'] ?? null,
                'status' => 'draft',
                'tgl_invoice' => $payload['tgl_invoice'],
                'jatuh_tempo' => $payload['jatuh_tempo'],
            ]);

            // Handle Manual Item Creation
            if (!empty($payload['manual_item_name']) && isset($payload['manual_item_price'])) {
                $amount = (int) $payload['manual_item_price'];

                $iItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'tipe' => 'custom',
                    'judul' => $payload['manual_item_name'],
                    'urutan' => 1,
                    'subtotal' => 0
                ]);

                InvoiceItemDetail::create([
                    'invoice_item_id' => $iItem->id,
                    'urutan' => 1,
                    'nama' => $payload['manual_item_name'],
                    'qty' => 1,
                    'satuan' => 'ls',
                    'harga' => $amount,
                    'subtotal' => $amount
                ]);

                $this->recalcItemSubtotal($iItem);
            }

            return redirect()->route('invoices.show', $invoice->id);
        });
    }

    public function createFromPenawaran(Penawaran $penawaran)
    {
        // Calculate grand total dynamically for the view
        $total = 0;
        foreach ($penawaran->items as $it) {
            $total += (int) $it->subtotal;
        }

        $discountAmount = 0;
        if ($penawaran->discount_enabled) {
            $dv = (float) ($penawaran->discount_value ?? 0);
            $dt = $penawaran->discount_type ?? 'percent';
            if ($dt === 'percent') {
                $discountAmount = (int) round($total * ($dv / 100));
            } else {
                $discountAmount = (int) round($dv);
            }
            if ($discountAmount > $total)
                $discountAmount = $total;
        }

        $dpp = $total - $discountAmount;
        $taxAmount = 0;
        if ($penawaran->tax_enabled) {
            $tr = (float) ($penawaran->tax_rate ?? 11);
            $taxAmount = (int) round($dpp * ($tr / 100));
        }

        $penawaran->grand_total_calculated = $dpp + $taxAmount;

        return view('invoices.create_from_penawaran', compact('penawaran'));
    }

    public function storeFromPenawaran(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'tgl_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tgl_invoice'],
            'type' => ['required', 'in:full,termin'],
            'percentage' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'termin_name' => ['nullable', 'string'],
            'grand_total' => ['required', 'numeric']
        ]);

        return DB::transaction(function () use ($payload, $penawaran) {
            $docNumber = $this->createDocNumber(auth()->id());

            // 1. Create Invoice Header
            $invoice = Invoice::create([
                'user_id' => auth()->id(), // Or penawaran owner? Let's use current user
                'doc_number_id' => $docNumber->id,
                'penawaran_id' => $penawaran->id,
                'judul' => $payload['judul'],
                'catatan' => $payload['catatan'] ?? null,
                'status' => 'draft',
                'tgl_invoice' => $payload['tgl_invoice'],
                'jatuh_tempo' => $payload['jatuh_tempo'],
                'tax_percent' => 0, // We calculate net in items for simplicty in termin, or copy settings?
                'discount_amount' => 0, // Logic varies. For termin, we usually just bill a flat amount.
            ]);

            // For Full Payment, we should try to copy everything?
            // OR finding: Simple approach is to treat termin as a single line item.
            // Even "Full Payment" can be a single line item "Pelunasan 100% ..." to avoid complexity of copying all items?
            // User might want detail. 
            // Let's implement:
            // - If Full: Copy all items.
            // - If Termin: Create one single custom item.

            if ($payload['type'] === 'full') {
                // Copy settings
                $invoice->update([
                    'tax_percent' => $penawaran->tax_enabled ? ($penawaran->tax_rate ?? 11) : 0,
                    'discount_amount' => $penawaran->discount_enabled ?
                        ($penawaran->discount_type == 'percent'
                            ? (int) round($penawaran->items->sum('subtotal') * ($penawaran->discount_value / 100))
                            : $penawaran->discount_value)
                        : 0,
                ]);

                // Copy Items
                foreach ($penawaran->items as $pItem) {
                    $iItem = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $pItem->product_id, // If links to product
                        'tipe' => $pItem->tipe == 'bundle' ? 'bundle' : 'custom',
                        'judul' => $pItem->judul,
                        'urutan' => $pItem->urutan,
                        'subtotal' => 0 // will recalc
                    ]);

                    foreach ($pItem->details as $pDetail) {
                        InvoiceItemDetail::create([
                            'invoice_item_id' => $iItem->id,
                            'product_detail_id' => $pDetail->product_detail_id, // if any
                            'urutan' => $pDetail->urutan,
                            'nama' => $pDetail->nama,
                            'spesifikasi' => $pDetail->spesifikasi,
                            'qty' => $pDetail->qty,
                            'satuan' => $pDetail->satuan,
                            'harga' => $pDetail->harga,
                            'subtotal' => $pDetail->subtotal
                        ]);
                    }
                    $this->recalcItemSubtotal($iItem);
                }
            } else {
                // TERMIN
                // create single item
                $percent = $payload['percentage'];
                $basis = $payload['grand_total']; // This includes tax. 
                // If we want to invoice "30% of Total", we usually make a single line item for that amount.
                // WE SHOULD NOT Apply tax again on invoice if the basis already has tax.
                // So Invoice tax = 0.

                $amount = (int) round($basis * ($percent / 100));

                $iItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'tipe' => 'custom',
                    'judul' => $payload['termin_name'] ?: "Pembayaran Termin {$percent}%",
                    'urutan' => 1,
                    'subtotal' => 0
                ]);

                InvoiceItemDetail::create([
                    'invoice_item_id' => $iItem->id,
                    'urutan' => 1,
                    'nama' => $payload['termin_name'] ?: "Pembayaran Termin {$percent}%",
                    'qty' => 1,
                    'satuan' => 'ls', // lumpsum
                    'harga' => $amount,
                    'subtotal' => $amount
                ]);

                $this->recalcItemSubtotal($iItem);
            }

            return redirect()->route('invoices.show', $invoice->id);
        });
    }

    public function show($id)
    {
        $invoice = Invoice::with(['docNumber', 'items.details', 'items.product', 'user', 'children.docNumber', 'terms', 'penawaran', 'signature'])->findOrFail($id);

        // Calculate totals dynamically just in case
        // ... (existing calculation logic if any, but we rely on DB values usually)

        $signatureTemplates = \App\Models\InvoiceSignatureTemplate::all();
        $termTemplates = \App\Models\InvoiceTermTemplate::all();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        $pics = \App\Models\Pic::all();

        return view('invoices.show', compact('invoice', 'signatureTemplates', 'termTemplates', 'products', 'pics'));
    }

    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'payment_info' => ['nullable', 'string'],
            'tgl_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tgl_invoice'],
            'status' => ['required', 'in:draft,sent,paid,cancelled'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'pic_id' => ['nullable', 'exists:pics,id'],
        ]);

        $invoice->update([
            'judul' => $payload['judul'],
            'catatan' => $payload['catatan'] ?? null,
            'payment_info' => $payload['payment_info'] ?? null,
            'tgl_invoice' => $payload['tgl_invoice'],
            'jatuh_tempo' => $payload['jatuh_tempo'],
            'status' => $payload['status'],
            'tax_percent' => $payload['tax_percent'] ?? 0,
            'discount_amount' => $payload['discount_amount'] ?? 0,
            'pic_id' => $payload['pic_id'] ?? null,
        ]);

        $this->recalcGrandTotal($invoice);

        return redirect()->route('invoices.show', $invoice->id);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    // --- Items Management ---

    public function addBundle(Request $request, Invoice $invoice)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $product = Product::with('details')->findOrFail($request->product_id);
        $qty = (float) ($request->qty ?: 1);

        DB::transaction(function () use ($invoice, $product, $qty) {
            $urutan = (int) $invoice->items()->max('urutan') + 1;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'tipe' => 'bundle',
                'judul' => $product->nama,
                'urutan' => $urutan,
                'subtotal' => 0
            ]);

            $urutanDetail = 1;
            foreach ($product->details as $pd) {
                $dQty = (float) ($pd->qty ?? 1);
                $harga = (int) ($pd->harga ?? 0);

                InvoiceItemDetail::create([
                    'invoice_item_id' => $item->id,
                    'product_detail_id' => $pd->id,
                    'urutan' => $urutanDetail++,
                    'nama' => $pd->nama,
                    'spesifikasi' => $pd->spesifikasi,
                    'qty' => $dQty * $qty,
                    'satuan' => $pd->satuan,
                    'harga' => $harga,
                    'subtotal' => (int) round(($dQty * $qty) * $harga)
                ]);
            }

            $this->recalcItemSubtotal($item);
        });

        return back()->with('success', 'Bundle added');
    }

    public function addCustomItem(Request $request, Invoice $invoice)
    {
        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            // New optional fields for Direct Price (Single Item)
            'is_single' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($invoice, $payload) {
            $urutan = (int) $invoice->items()->max('urutan') + 1;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'tipe' => 'custom',
                'judul' => $payload['judul'],
                'urutan' => $urutan,
                'subtotal' => 0
            ]);

            // If "Single Item" (Direct Price) is selected
            if (!empty($payload['is_single']) && $payload['is_single']) {
                $qty = (float) ($payload['qty'] ?? 1);
                $price = (int) ($payload['price'] ?? 0);
                $subtotal = $qty * $price;

                InvoiceItemDetail::create([
                    'invoice_item_id' => $item->id,
                    'urutan' => 1,
                    // Use item title as detail name if single
                    'nama' => $payload['judul'],
                    'qty' => $qty,
                    'satuan' => $payload['unit'] ?? 'ls',
                    'harga' => $price,
                    'subtotal' => $subtotal
                ]);

                $this->recalcItemSubtotal($item);
            }
        });

        return back()->with('success', 'Custom item added.');
    }

    public function addTerminItem(Request $request, Invoice $invoice)
    {
        $payload = $request->validate([
            'total_project' => ['required', 'numeric', 'min:0'],
            'termin_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'termin_step' => ['nullable', 'string'] // e.g. "Termin 1", "DP"
        ]);

        $basis = (int) $payload['total_project'];
        $percent = (float) $payload['termin_percent'];
        $amount = (int) round($basis * ($percent / 100));

        $name = $payload['termin_step'] ? "{$payload['termin_step']} ({$percent}%)" : "Pembayaran Termin {$percent}%";
        // Append total ref if needed: "$name - Total Project Rp..."

        DB::transaction(function () use ($invoice, $amount, $name) {
            $urutan = (int) $invoice->items()->max('urutan') + 1;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'tipe' => 'custom',
                'judul' => $name,
                'urutan' => $urutan,
                'subtotal' => 0
            ]);

            InvoiceItemDetail::create([
                'invoice_item_id' => $item->id,
                'urutan' => 1,
                'nama' => $name,
                'qty' => 1,
                'satuan' => 'ls',
                'harga' => $amount,
                'subtotal' => $amount
            ]);

            $this->recalcItemSubtotal($item);
        });

        return back()->with('success', 'Termin item added calculated from project total.');
    }

    public function storeTermin(Request $request, Invoice $invoice)
    {
        $payload = $request->validate([
            'termin_name' => ['required', 'string'],
            'termin_percent' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'tgl_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tgl_invoice'],
        ]);

        return DB::transaction(function () use ($payload, $invoice) {

            // Generate Suffix Document Number
            // Parent: 001/INV/2026 -> Child: 001/INV/2026-T1
            $parentDocNo = $invoice->docNumber->doc_no;
            $countChildren = $invoice->children()->count() + 1;
            $suffix = "-T{$countChildren}";
            $newDocNoStr = $parentDocNo . $suffix;

            // Create DocNumber record for consistency
            $parentDocNum = $invoice->docNumber;
            $docNumber = DocNumber::create([
                'prefix' => $parentDocNum->prefix,
                'seq' => $parentDocNum->seq,
                'doc_no' => $newDocNoStr,
            ]);
            $percent = (float) $payload['termin_percent'];
            $ratio = $percent / 100;

            // Create Child Invoice
            $child = Invoice::create([
                'user_id' => auth()->id(),
                'doc_number_id' => $docNumber->id,
                'parent_id' => $invoice->id,
                'penawaran_id' => $invoice->penawaran_id,
                'judul' => $payload['termin_name'],
                'catatan' => "Termin {$percent}% dari Invoice " . $invoice->docNumber?->doc_no,
                'status' => 'draft',
                'tgl_invoice' => $payload['tgl_invoice'],
                'jatuh_tempo' => $payload['jatuh_tempo'],
                'subtotal' => 0,
                'pic_id' => $invoice->pic_id, // Copy PIC
                'payment_info' => $invoice->payment_info, // Copy Payment Info
                // Copy Tax Percent
                'tax_percent' => $invoice->tax_percent,
                // Prorate Discount
                'discount_amount' => round($invoice->discount_amount * $ratio),
                'grand_total' => 0
            ]);

            // Copy Signature
            if ($invoice->signature) {
                $child->signature()->create([
                    'nama' => $invoice->signature->nama,
                    'jabatan' => $invoice->signature->jabatan,
                    'kota' => $invoice->signature->kota,
                    'tanggal' => $invoice->signature->tanggal,
                    'ttd_path' => $invoice->signature->ttd_path, // Share the same image file
                ]);
            }

            // Copy Terms
            foreach ($invoice->terms as $term) {
                $child->terms()->create([
                    'urutan' => $term->urutan,
                    'isi' => $term->isi
                ]);
            }

            // Copy and Prorate Items
            foreach ($invoice->items as $parentItem) {
                $childItem = InvoiceItem::create([
                    'invoice_id' => $child->id,
                    'tipe' => $parentItem->tipe,
                    'judul' => $parentItem->judul, // Keep original title
                    'urutan' => $parentItem->urutan,
                    'product_id' => $parentItem->product_id,
                    'subtotal' => 0
                ]);

                foreach ($parentItem->details as $parentDetail) {
                    $proratedPrice = round($parentDetail->harga * $ratio);

                    InvoiceItemDetail::create([
                        'invoice_item_id' => $childItem->id,
                        'urutan' => $parentDetail->urutan,
                        'nama' => $parentDetail->nama . " (Termin {$percent}%)", // Append info or keep clean? User said "menampilkan item dari termin", implying identity. Let's keep name clean or maybe append percentage if needed. Let's append to distinguish.
                        'qty' => $parentDetail->qty, // Keep Qty same
                        'satuan' => $parentDetail->satuan,
                        'spesifikasi' => $parentDetail->spesifikasi,
                        'harga' => $proratedPrice, // Prorated Price
                        'subtotal' => $proratedPrice * $parentDetail->qty
                    ]);
                }

                $this->recalcItemSubtotal($childItem);
            }

            // Recalculate Grand Total to sum up the new prorated items
            $this->recalcGrandTotal($child);

            return redirect()->route('invoices.show', $child->id);
        });
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $payload = $request->validate([
            'judul' => ['required', 'string'],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string']
        ]);

        $item->update($payload);

        if ($item->tipe === 'bundle') {
            $this->recalcItemSubtotal($item);
        } else {
            // Recalc custom item if qty changed
            if (isset($payload['qty']) || isset($payload['price'])) {
                $item->update([
                    'subtotal' => ($item->harga * ($item->qty ?: 1))
                ]);
                $this->recalcGrandTotal($invoice);
            }
        }

        return back();
    }

    public function deleteItem(Invoice $invoice, InvoiceItem $item)
    {
        $item->delete();
        $this->recalcGrandTotal($invoice);
        return back();
    }

    public function addItemDetail(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $payload = $request->validate([
            'nama' => 'required|string',
            'qty' => 'required|numeric',
            'harga' => 'required|numeric'
        ]);

        $urutan = (int) $item->details()->max('urutan') + 1;
        $qty = (float) $payload['qty'];
        $harga = (int) $payload['harga'];

        InvoiceItemDetail::create([
            'invoice_item_id' => $item->id,
            'urutan' => $urutan,
            'nama' => $payload['nama'],
            'qty' => $qty,
            'harga' => $harga,
            'subtotal' => (int) round($qty * $harga),
            'satuan' => $request->satuan
        ]);

        $this->recalcItemSubtotal($item);
        return back();
    }

    public function updateItemDetail(Request $request, Invoice $invoice, InvoiceItem $item, InvoiceItemDetail $detail)
    {
        $payload = $request->validate([
            'nama' => 'required|string',
            'qty' => 'required|numeric',
            'harga' => 'required|numeric'
        ]);

        $qty = (float) $payload['qty'];
        $harga = (int) $payload['harga'];

        $detail->update([
            'nama' => $payload['nama'],
            'qty' => $qty,
            'harga' => $harga,
            'subtotal' => (int) round($qty * $harga),
            'satuan' => $request->satuan
        ]);

        $this->recalcItemSubtotal($item);
        return back();
    }

    public function deleteItemDetail(Invoice $invoice, InvoiceItem $item, InvoiceItemDetail $detail)
    {
        $detail->delete();
        $this->recalcItemSubtotal($item);
        return back();
    }

    // --- Helpers ---

    protected function createDocNumber($userId)
    {
        // Simple doc number generation, copying pattern from Penawaran but simpler for now
        // Assuming DocNumber model usage is generic or we need a new prefix
        // Let's assume we reuse DocNumber logic.

        $prefix = "INV/" . date('Y/m/');
        $last = DocNumber::where('prefix', $prefix)->orderByDesc('seq')->first();
        $seq = $last ? $last->seq + 1 : 1;
        $docNo = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        return DocNumber::create([
            'prefix' => $prefix,
            'seq' => $seq,
            'doc_no' => $docNo
        ]);
    }

    protected function recalcItemSubtotal(InvoiceItem $item)
    {
        if ($item->tipe === 'bundle') {
            $sum = $item->details()->sum('subtotal');
            $qty = $item->qty ?: 1;
            $item->update(['subtotal' => $sum * $qty]);
        } else {
            // Custom item logic is handled elsewhere usually, or static
            // But if we need re-calc custom item, it's typically price * qty
        }
        $this->recalcGrandTotal($item->invoice);
    }

    protected function recalcGrandTotal(Invoice $invoice)
    {
        $subtotal = $invoice->items()->sum('subtotal');
        $discount = $invoice->discount_amount;
        $taxPercent = $invoice->tax_percent;

        $taxable = $subtotal - $discount;
        if ($taxable < 0)
            $taxable = 0;

        $taxAmount = (int) round($taxable * ($taxPercent / 100));
        $grandTotal = $taxable + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {

        $invoice->load(['docNumber', 'items.details', 'user', 'penawaran.cover', 'signature', 'terms', 'parent']); // Load Signature, Terms, Parent

        // Logic to get Logo
        $logo = null;
        // Try getting logo from linked Penawaran Cover if available
        if ($invoice->penawaran && $invoice->penawaran->cover && $invoice->penawaran->cover->logo_path) {
            $p1 = public_path('storage/' . ltrim($invoice->penawaran->cover->logo_path, '/'));
            $p2 = storage_path('app/public/' . ltrim($invoice->penawaran->cover->logo_path, '/'));
            $logo = is_file($p1) ? $p1 : (is_file($p2) ? $p2 : null);
        }

        // Fallback or Default Logo
        if (!$logo) {
            $logo = public_path('images/logo_arsol.png');
        }

        // Construct Kop Data
        $kop = [
            'logo' => $logo,
            'nama' => 'CV. ARTA SOLUSINDO',
            'alamat' => $invoice->penawaran?->cover?->perusahaan_alamat ?? 'Juwangen RT 10 RW 02 Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta 55571',
            'telp' => $invoice->penawaran?->cover?->perusahaan_telp ?? '(0274) 5044026 / 085727868505',
            'email' => $invoice->penawaran?->cover?->perusahaan_email ?? 'cv.artasolusindo@gmail.com',
        ];

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'kop'));
        $filename = "Invoice-" . str_replace(['/', '\\'], '-', $invoice->docNumber->doc_no) . ".pdf";
        return $pdf->stream($filename);
    }

    public function saveSignature(Request $request, Invoice $invoice)
    {
        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
            'ttd' => ['nullable', 'image', 'max:2048']
        ]);

        $path = null;
        if ($request->hasFile('ttd')) {
            // Delete old file if exists and replacing
            if ($invoice->signature && $invoice->signature->ttd_path) {
                Storage::disk('public')->delete($invoice->signature->ttd_path);
            }
            $path = $request->file('ttd')->store('invoices/signatures', 'public');
        } elseif ($invoice->signature) {
            // Keep old path if not replacing
            $path = $invoice->signature->ttd_path;
        }

        $invoice->signature()->updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'nama' => $payload['nama'],
                'jabatan' => $payload['jabatan'],
                'kota' => $payload['kota'],
                'tanggal' => $payload['tanggal'],
                'ttd_path' => $path
            ]
        );

        return back()->with('success', 'Tanda tangan disimpan.');
    }

    public function deleteSignature(Invoice $invoice)
    {
        if ($invoice->signature) {
            if ($invoice->signature->ttd_path) {
                Storage::disk('public')->delete($invoice->signature->ttd_path);
            }
            $invoice->signature()->delete();
        }
        return back()->with('success', 'Tanda tangan dihapus.');
    }

    // Terms Management
    public function addTerm(Request $request, Invoice $invoice)
    {
        $payload = $request->validate([
            'isi' => ['required', 'string'],
        ]);

        $urutan = $invoice->terms()->max('urutan') ?? 0;
        $urutan++;

        $term = $invoice->terms()->create([
            'urutan' => $urutan,
            'isi' => $payload['isi']
        ]);

        return response()->json([
            'message' => 'Term added.',
            'term' => $term
        ]);
    }

    // Load Signature Template
    public function loadSignatureTemplate(Request $request, Invoice $invoice)
    {
        $request->validate(['template_id' => 'required|exists:invoice_signature_templates,id']);

        $template = \App\Models\InvoiceSignatureTemplate::find($request->template_id);

        // Copy image if exists
        $newPath = null;
        if ($template->ttd_path) {
            $newPath = 'invoices/signatures/' . uniqid() . '_' . basename($template->ttd_path);
            Storage::disk('public')->copy($template->ttd_path, $newPath);

            // Delete old file if exists
            if ($invoice->signature && $invoice->signature->ttd_path) {
                Storage::disk('public')->delete($invoice->signature->ttd_path);
            }
        }

        $invoice->signature()->updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'nama' => $template->nama,
                'jabatan' => $template->jabatan,
                'kota' => $template->kota,
                'ttd_path' => $newPath,
                'tanggal' => now()
            ]
        );

        return back()->with('success', 'Signature loaded from template.');
    }

    // Load Term Template
    public function loadTermTemplate(Request $request, Invoice $invoice)
    {
        $request->validate(['template_id' => 'required|exists:invoice_term_templates,id']);
        $template = \App\Models\InvoiceTermTemplate::find($request->template_id);

        $startUrutan = $invoice->terms()->max('urutan') ?? 0;

        foreach ($template->terms as $isi) {
            $startUrutan++;
            $invoice->terms()->create([
                'urutan' => $startUrutan,
                'isi' => $isi
            ]);
        }

        return back()->with('success', 'Terms loaded from template.');
    }

    public function deleteTerm(Invoice $invoice, InvoiceTerm $term)
    {
        if ($term->invoice_id !== $invoice->id) {
            abort(404);
        }
        $term->delete();
        return response()->json(['message' => 'Term deleted.']);
    }
}
