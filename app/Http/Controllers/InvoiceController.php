<?php

namespace App\Http\Controllers;

use App\Models\DocNumber;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceItemDetail;
use App\Models\InvoiceSignatureTemplate;
use App\Models\InvoiceTerm;
use App\Models\InvoiceTermTemplate;
use App\Models\Penawaran;
use App\Models\Pic;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();
        $companyId = $this->currentCompanyId($user);

        $data = Invoice::query()
            ->whereNull('parent_id')
            ->with(['docNumber', 'user', 'company'])
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when(
                !$this->isSuperadmin($user) && !$user->hasRole('admin'),
                fn($query) => $query->where('user_id', $user->id)
            )
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('judul', 'like', "%{$q}%")
                        ->orWhereHas('docNumber', fn($qq) => $qq->where('doc_no', 'like', "%{$q}%"));
                });
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

        $user = $request->user();

        return DB::transaction(function () use ($payload, $user) {
            $companyId = (int) $this->currentCompanyId($user);
            $docNumber = $this->createDocNumber($companyId, (int) $user->id);

            $invoice = Invoice::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'doc_number_id' => $docNumber->id,
                'judul' => $payload['judul'],
                'catatan' => $payload['catatan'] ?? null,
                'status' => 'draft',
                'tgl_invoice' => $payload['tgl_invoice'],
                'jatuh_tempo' => $payload['jatuh_tempo'],
            ]);

            if (!empty($payload['manual_item_name']) && isset($payload['manual_item_price'])) {
                $amount = (int) $payload['manual_item_price'];

                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'tipe' => 'custom',
                    'judul' => $payload['manual_item_name'],
                    'urutan' => 1,
                    'subtotal' => 0,
                ]);

                InvoiceItemDetail::create([
                    'invoice_item_id' => $item->id,
                    'urutan' => 1,
                    'nama' => $payload['manual_item_name'],
                    'qty' => 1,
                    'satuan' => 'ls',
                    'harga' => $amount,
                    'subtotal' => $amount,
                ]);

                $this->recalcItemSubtotal($item);
            }

            return redirect()->route('invoices.show', $invoice->id);
        });
    }

    public function createFromPenawaran(Penawaran $penawaran)
    {
        $this->ensurePenawaranInvoiceAccess($penawaran);

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
            if ($discountAmount > $total) {
                $discountAmount = $total;
            }
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
        $this->ensurePenawaranInvoiceAccess($penawaran);
        $penawaran->loadMissing('items.details');

        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'tgl_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tgl_invoice'],
            'type' => ['required', 'in:full,termin'],
            'percentage' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'termin_name' => ['nullable', 'string'],
            'grand_total' => ['required', 'numeric'],
        ]);

        return DB::transaction(function () use ($payload, $penawaran) {
            $owner = $this->resolveCompanyUser((int) $penawaran->company_id, (int) $penawaran->id_user);
            $docNumber = $this->createDocNumber((int) $penawaran->company_id, (int) $owner->id);

            $invoice = Invoice::create([
                'company_id' => $penawaran->company_id,
                'user_id' => $owner->id,
                'doc_number_id' => $docNumber->id,
                'penawaran_id' => $penawaran->id,
                'pic_id' => $penawaran->id_pic,
                'judul' => $payload['judul'],
                'catatan' => $payload['catatan'] ?? null,
                'status' => 'draft',
                'tgl_invoice' => $payload['tgl_invoice'],
                'jatuh_tempo' => $payload['jatuh_tempo'],
                'tax_percent' => 0,
                'discount_amount' => 0,
            ]);

            if ($payload['type'] === 'full') {
                $invoice->update([
                    'tax_percent' => $penawaran->tax_enabled ? ($penawaran->tax_rate ?? 11) : 0,
                    'discount_amount' => $penawaran->discount_enabled
                        ? (($penawaran->discount_type ?? 'percent') === 'percent'
                            ? (int) round($penawaran->items->sum('subtotal') * ((float) ($penawaran->discount_value ?? 0) / 100))
                            : (int) ($penawaran->discount_value ?? 0))
                        : 0,
                ]);

                foreach ($penawaran->items as $pItem) {
                    $item = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $pItem->product_id,
                        'tipe' => $pItem->tipe === 'bundle' ? 'bundle' : 'custom',
                        'judul' => $pItem->judul,
                        'urutan' => $pItem->urutan,
                        'subtotal' => 0,
                    ]);

                    foreach ($pItem->details as $pDetail) {
                        InvoiceItemDetail::create([
                            'invoice_item_id' => $item->id,
                            'product_detail_id' => $pDetail->product_detail_id,
                            'urutan' => $pDetail->urutan,
                            'nama' => $pDetail->nama,
                            'spesifikasi' => $pDetail->spesifikasi,
                            'qty' => $pDetail->qty,
                            'satuan' => $pDetail->satuan,
                            'harga' => $pDetail->harga,
                            'subtotal' => $pDetail->subtotal,
                        ]);
                    }

                    $this->recalcItemSubtotal($item);
                }
            } else {
                $percent = (float) ($payload['percentage'] ?? 0);
                $basis = (float) $payload['grand_total'];
                $amount = (int) round($basis * ($percent / 100));

                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'tipe' => 'custom',
                    'judul' => $payload['termin_name'] ?: "Pembayaran Termin {$percent}%",
                    'urutan' => 1,
                    'subtotal' => 0,
                ]);

                InvoiceItemDetail::create([
                    'invoice_item_id' => $item->id,
                    'urutan' => 1,
                    'nama' => $payload['termin_name'] ?: "Pembayaran Termin {$percent}%",
                    'qty' => 1,
                    'satuan' => 'ls',
                    'harga' => $amount,
                    'subtotal' => $amount,
                ]);

                $this->recalcItemSubtotal($item);
            }

            return redirect()->route('invoices.show', $invoice->id);
        });
    }

    public function show(Invoice $invoice)
    {
        $this->ensureInvoiceViewAccess($invoice);

        $invoice->load([
            'docNumber',
            'items.details',
            'items.product',
            'user',
            'company',
            'children.docNumber',
            'terms',
            'penawaran.cover',
            'penawaran.company',
            'signature',
            'pic',
            'parent.docNumber',
        ]);

        $signatureTemplates = InvoiceSignatureTemplate::query()
            ->where('company_id', $invoice->company_id)
            ->latest()
            ->get();

        $termTemplates = InvoiceTermTemplate::query()
            ->where('company_id', $invoice->company_id)
            ->latest()
            ->get();

        $products = Product::query()
            ->where('company_id', $invoice->company_id)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        $pics = Pic::query()
            ->where('company_id', $invoice->company_id)
            ->orderBy('nama')
            ->get();

        return view('invoices.show', compact('invoice', 'signatureTemplates', 'termTemplates', 'products', 'pics'));
    }

    public function edit(Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

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

        if (!empty($payload['pic_id'])) {
            Pic::query()
                ->where('company_id', $invoice->company_id)
                ->findOrFail((int) $payload['pic_id']);
        }

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
        $this->ensureInvoiceEditAccess($invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    public function addBundle(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $product = Product::with('details')
            ->where('company_id', $invoice->company_id)
            ->findOrFail((int) $request->product_id);

        $qty = (float) ($request->qty ?: 1);

        DB::transaction(function () use ($invoice, $product, $qty) {
            $urutan = (int) $invoice->items()->max('urutan') + 1;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'tipe' => 'bundle',
                'judul' => $product->nama,
                'urutan' => $urutan,
                'subtotal' => 0,
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
                    'subtotal' => (int) round(($dQty * $qty) * $harga),
                ]);
            }

            $this->recalcItemSubtotal($item);
        });

        return back()->with('success', 'Bundle added');
    }

    public function addCustomItem(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
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
                'subtotal' => 0,
            ]);

            if (!empty($payload['is_single']) && $payload['is_single']) {
                $qty = (float) ($payload['qty'] ?? 1);
                $price = (int) ($payload['price'] ?? 0);
                $subtotal = (int) round($qty * $price);

                InvoiceItemDetail::create([
                    'invoice_item_id' => $item->id,
                    'urutan' => 1,
                    'nama' => $payload['judul'],
                    'qty' => $qty,
                    'satuan' => $payload['unit'] ?? 'ls',
                    'harga' => $price,
                    'subtotal' => $subtotal,
                ]);

                $this->recalcItemSubtotal($item);
            }
        });

        return back()->with('success', 'Custom item added.');
    }

    public function addTerminItem(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $payload = $request->validate([
            'total_project' => ['required', 'numeric', 'min:0'],
            'termin_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'termin_step' => ['nullable', 'string'],
        ]);

        $basis = (int) $payload['total_project'];
        $percent = (float) $payload['termin_percent'];
        $amount = (int) round($basis * ($percent / 100));
        $name = $payload['termin_step'] ? "{$payload['termin_step']} ({$percent}%)" : "Pembayaran Termin {$percent}%";

        DB::transaction(function () use ($invoice, $amount, $name) {
            $urutan = (int) $invoice->items()->max('urutan') + 1;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'tipe' => 'custom',
                'judul' => $name,
                'urutan' => $urutan,
                'subtotal' => 0,
            ]);

            InvoiceItemDetail::create([
                'invoice_item_id' => $item->id,
                'urutan' => 1,
                'nama' => $name,
                'qty' => 1,
                'satuan' => 'ls',
                'harga' => $amount,
                'subtotal' => $amount,
            ]);

            $this->recalcItemSubtotal($item);
        });

        return back()->with('success', 'Termin item added calculated from project total.');
    }

    public function storeTermin(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $payload = $request->validate([
            'termin_name' => ['required', 'string'],
            'termin_percent' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'tgl_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:tgl_invoice'],
        ]);

        return DB::transaction(function () use ($payload, $invoice) {
            $invoice->loadMissing(['docNumber', 'signature', 'terms', 'items.details', 'children', 'user']);
            $owner = $this->resolveCompanyUser((int) $invoice->company_id, (int) $invoice->user_id);

            $parentDocNo = $invoice->docNumber->doc_no;
            $countChildren = $invoice->children()->count() + 1;
            $newDocNoStr = $parentDocNo . "-T{$countChildren}";
            $parentDocNum = $invoice->docNumber;
            $docDate = Carbon::parse($payload['tgl_invoice']);

            $docNumber = DocNumber::create([
                'company_id' => $invoice->company_id,
                'prefix' => $parentDocNum->prefix,
                'doc_type' => $parentDocNum->doc_type,
                'user_code' => $parentDocNum->user_code,
                'seq' => $parentDocNum->seq,
                'month' => $docDate->month,
                'year' => $docDate->year,
                'doc_no' => $newDocNoStr,
            ]);

            $percent = (float) $payload['termin_percent'];
            $ratio = $percent / 100;

            $child = Invoice::create([
                'company_id' => $invoice->company_id,
                'user_id' => $owner->id,
                'doc_number_id' => $docNumber->id,
                'parent_id' => $invoice->id,
                'penawaran_id' => $invoice->penawaran_id,
                'judul' => $payload['termin_name'],
                'catatan' => "Termin {$percent}% dari Invoice " . $invoice->docNumber?->doc_no,
                'status' => 'draft',
                'tgl_invoice' => $payload['tgl_invoice'],
                'jatuh_tempo' => $payload['jatuh_tempo'],
                'subtotal' => 0,
                'pic_id' => $invoice->pic_id,
                'payment_info' => $invoice->payment_info,
                'tax_percent' => $invoice->tax_percent,
                'discount_amount' => (int) round($invoice->discount_amount * $ratio),
                'grand_total' => 0,
            ]);

            if ($invoice->signature) {
                $child->signature()->create([
                    'nama' => $invoice->signature->nama,
                    'jabatan' => $invoice->signature->jabatan,
                    'kota' => $invoice->signature->kota,
                    'tanggal' => $invoice->signature->tanggal,
                    'ttd_path' => $invoice->signature->ttd_path,
                ]);
            }

            foreach ($invoice->terms as $term) {
                $child->terms()->create([
                    'urutan' => $term->urutan,
                    'isi' => $term->isi,
                ]);
            }

            foreach ($invoice->items as $parentItem) {
                $childItem = InvoiceItem::create([
                    'invoice_id' => $child->id,
                    'tipe' => $parentItem->tipe,
                    'judul' => $parentItem->judul,
                    'urutan' => $parentItem->urutan,
                    'product_id' => $parentItem->product_id,
                    'subtotal' => 0,
                ]);

                foreach ($parentItem->details as $parentDetail) {
                    $proratedPrice = (int) round($parentDetail->harga * $ratio);

                    InvoiceItemDetail::create([
                        'invoice_item_id' => $childItem->id,
                        'urutan' => $parentDetail->urutan,
                        'nama' => $parentDetail->nama . " (Termin {$percent}%)",
                        'qty' => $parentDetail->qty,
                        'satuan' => $parentDetail->satuan,
                        'spesifikasi' => $parentDetail->spesifikasi,
                        'harga' => $proratedPrice,
                        'subtotal' => (int) round($proratedPrice * $parentDetail->qty),
                    ]);
                }

                $this->recalcItemSubtotal($childItem);
            }

            $this->recalcGrandTotal($child);

            return redirect()->route('invoices.show', $child->id);
        });
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->ensureInvoiceEditAccess($invoice);
        $this->ensureInvoiceItemBelongsToInvoice($invoice, $item);

        $payload = $request->validate([
            'judul' => ['required', 'string'],
            'qty' => ['nullable', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);

        $item->update([
            'judul' => $payload['judul'],
            'catatan' => $payload['catatan'] ?? null,
        ]);

        if ($item->tipe === 'bundle' && isset($payload['qty']) && $item->product_id) {
            $product = Product::with('details')
                ->where('company_id', $invoice->company_id)
                ->find($item->product_id);

            if ($product) {
                $detailsByProductDetail = $item->details->keyBy('product_detail_id');
                $newQty = (float) $payload['qty'];

                foreach ($product->details as $productDetail) {
                    $detail = $detailsByProductDetail->get($productDetail->id);
                    if (!$detail) {
                        continue;
                    }

                    $detailQty = (float) ($productDetail->qty ?? 1) * $newQty;
                    $harga = (int) ($detail->harga ?? $productDetail->harga ?? 0);

                    $detail->update([
                        'qty' => $detailQty,
                        'satuan' => $payload['satuan'] ?? $detail->satuan ?? $productDetail->satuan,
                        'subtotal' => (int) round($detailQty * $harga),
                    ]);
                }
            }
        }

        $this->recalcItemSubtotal($item->fresh('details'));

        return back();
    }

    public function deleteItem(Invoice $invoice, InvoiceItem $item)
    {
        $this->ensureInvoiceEditAccess($invoice);
        $this->ensureInvoiceItemBelongsToInvoice($invoice, $item);

        $item->delete();
        $this->recalcGrandTotal($invoice);

        return back();
    }

    public function addItemDetail(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->ensureInvoiceEditAccess($invoice);
        $this->ensureInvoiceItemBelongsToInvoice($invoice, $item);

        $payload = $request->validate([
            'nama' => ['required', 'string'],
            'qty' => ['required', 'numeric'],
            'harga' => ['required', 'numeric'],
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
            'satuan' => $request->satuan,
        ]);

        $this->recalcItemSubtotal($item);

        return back();
    }

    public function updateItemDetail(Request $request, Invoice $invoice, InvoiceItem $item, InvoiceItemDetail $detail)
    {
        $this->ensureInvoiceEditAccess($invoice);
        $this->ensureInvoiceItemBelongsToInvoice($invoice, $item);
        $this->ensureInvoiceItemDetailBelongsToItem($item, $detail);

        $payload = $request->validate([
            'nama' => ['required', 'string'],
            'qty' => ['required', 'numeric'],
            'harga' => ['required', 'numeric'],
        ]);

        $qty = (float) $payload['qty'];
        $harga = (int) $payload['harga'];

        $detail->update([
            'nama' => $payload['nama'],
            'qty' => $qty,
            'harga' => $harga,
            'subtotal' => (int) round($qty * $harga),
            'satuan' => $request->satuan,
        ]);

        $this->recalcItemSubtotal($item);

        return back();
    }

    public function deleteItemDetail(Invoice $invoice, InvoiceItem $item, InvoiceItemDetail $detail)
    {
        $this->ensureInvoiceEditAccess($invoice);
        $this->ensureInvoiceItemBelongsToInvoice($invoice, $item);
        $this->ensureInvoiceItemDetailBelongsToItem($item, $detail);

        $detail->delete();
        $this->recalcItemSubtotal($item);

        return back();
    }

    protected function createDocNumber(?int $companyId = null, ?int $userId = null): DocNumber
    {
        $now = Carbon::now();
        $companyId = $companyId ?: $this->currentCompanyId();
        $company = \App\Models\Company::find($companyId);
        $companyCode = strtoupper((string) ($company?->code ?: 'COMP'));
        $userId = $userId ?: auth()->id();
        $month = str_pad((string) $now->month, 2, '0', STR_PAD_LEFT);
        $year = $now->year;

        $prefix = "INV/{$companyCode}/{$year}/{$month}/";
        $last = DocNumber::query()
            ->where('company_id', $companyId)
            ->where('prefix', $prefix)
            ->orderByDesc('seq')
            ->first();

        $seq = $last ? $last->seq + 1 : 1;
        $userCode = 'INV' . str_pad((string) $userId, 2, '0', STR_PAD_LEFT);

        return DocNumber::create([
            'company_id' => $companyId,
            'prefix' => $prefix,
            'doc_type' => 'INV',
            'user_code' => $userCode,
            'seq' => $seq,
            'month' => (int) $month,
            'year' => $year,
            'doc_no' => $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        ]);
    }

    protected function recalcItemSubtotal(InvoiceItem $item)
    {
        $item->loadMissing('details');

        $total = 0;
        foreach ($item->details as $detail) {
            $subtotal = (int) ($detail->subtotal ?? 0);
            if ($subtotal <= 0) {
                $subtotal = (int) round(((float) ($detail->qty ?? 0)) * ((int) ($detail->harga ?? 0)));
            }
            $total += $subtotal;
        }

        $item->update(['subtotal' => $total]);
        $this->recalcGrandTotal($item->invoice);
    }

    protected function recalcGrandTotal(Invoice $invoice)
    {
        $subtotal = (int) $invoice->items()->sum('subtotal');
        $discount = (int) ($invoice->discount_amount ?? 0);
        $taxPercent = (float) ($invoice->tax_percent ?? 0);

        $taxable = $subtotal - $discount;
        if ($taxable < 0) {
            $taxable = 0;
        }

        $taxAmount = (int) round($taxable * ($taxPercent / 100));
        $grandTotal = $taxable + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->ensureInvoiceViewAccess($invoice);

        $invoice->load([
            'docNumber',
            'items.details',
            'user.company',
            'company',
            'pic',
            'penawaran.cover',
            'penawaran.company',
            'signature',
            'terms',
            'parent',
        ]);

        $kop = $this->buildInvoiceKop($invoice);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'kop'));
        $filename = 'Invoice-' . str_replace(['/', '\\'], '-', $invoice->docNumber->doc_no) . '.pdf';

        return $pdf->stream($filename);
    }

    public function saveSignature(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
            'ttd' => ['nullable', 'image', 'max:2048'],
        ]);

        $path = null;
        if ($request->hasFile('ttd')) {
            if ($invoice->signature && $invoice->signature->ttd_path) {
                Storage::disk('public')->delete($invoice->signature->ttd_path);
            }
            $path = $request->file('ttd')->store('invoices/signatures', 'public');
        } elseif ($invoice->signature) {
            $path = $invoice->signature->ttd_path;
        }

        $invoice->signature()->updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'nama' => $payload['nama'],
                'jabatan' => $payload['jabatan'],
                'kota' => $payload['kota'] ?? null,
                'tanggal' => $payload['tanggal'] ?? null,
                'ttd_path' => $path,
            ]
        );

        return back()->with('success', 'Tanda tangan disimpan.');
    }

    public function deleteSignature(Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        if ($invoice->signature) {
            if ($invoice->signature->ttd_path) {
                Storage::disk('public')->delete($invoice->signature->ttd_path);
            }
            $invoice->signature()->delete();
        }

        return back()->with('success', 'Tanda tangan dihapus.');
    }

    public function addTerm(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $payload = $request->validate([
            'isi' => ['required', 'string'],
        ]);

        $urutan = (int) ($invoice->terms()->max('urutan') ?? 0) + 1;

        $term = $invoice->terms()->create([
            'urutan' => $urutan,
            'isi' => $payload['isi'],
        ]);

        return response()->json([
            'message' => 'Term added.',
            'term' => $term,
        ]);
    }

    public function loadSignatureTemplate(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $request->validate(['template_id' => 'required|exists:invoice_signature_templates,id']);

        $template = InvoiceSignatureTemplate::findOrFail((int) $request->template_id);
        $this->ensureCompanyAccess($template);

        if ((int) $template->company_id !== (int) $invoice->company_id) {
            abort(403);
        }

        $newPath = null;
        if ($template->ttd_path) {
            $newPath = 'invoices/signatures/' . uniqid() . '_' . basename($template->ttd_path);
            Storage::disk('public')->copy($template->ttd_path, $newPath);

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
                'tanggal' => now(),
            ]
        );

        return back()->with('success', 'Signature loaded from template.');
    }

    public function loadTermTemplate(Request $request, Invoice $invoice)
    {
        $this->ensureInvoiceEditAccess($invoice);

        $request->validate(['template_id' => 'required|exists:invoice_term_templates,id']);

        $template = InvoiceTermTemplate::findOrFail((int) $request->template_id);
        $this->ensureCompanyAccess($template);

        if ((int) $template->company_id !== (int) $invoice->company_id) {
            abort(403);
        }

        $startUrutan = (int) ($invoice->terms()->max('urutan') ?? 0);

        foreach ($template->terms as $isi) {
            $startUrutan++;
            $invoice->terms()->create([
                'urutan' => $startUrutan,
                'isi' => $isi,
            ]);
        }

        return back()->with('success', 'Terms loaded from template.');
    }

    public function deleteTerm(Invoice $invoice, InvoiceTerm $term)
    {
        $this->ensureInvoiceEditAccess($invoice);

        if ((int) $term->invoice_id !== (int) $invoice->id) {
            abort(404);
        }

        $term->delete();

        return response()->json(['message' => 'Term deleted.']);
    }

    private function ensureInvoiceViewAccess(Invoice $invoice, $user = null): void
    {
        $user ??= auth()->user();

        $this->ensureCompanyAccess($invoice, 'company_id', $user);

        if ($this->isSuperadmin($user) || $user->hasRole('admin')) {
            return;
        }

        if ((int) $invoice->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function ensureInvoiceEditAccess(Invoice $invoice, $user = null): void
    {
        $user ??= auth()->user();

        $this->ensureCompanyAccess($invoice, 'company_id', $user);

        if ($this->isSuperadmin($user) || $user->hasRole('admin')) {
            return;
        }

        if ((int) $invoice->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function ensureInvoiceItemBelongsToInvoice(Invoice $invoice, InvoiceItem $item): void
    {
        if ((int) $item->invoice_id !== (int) $invoice->id) {
            abort(404);
        }
    }

    private function ensureInvoiceItemDetailBelongsToItem(InvoiceItem $item, InvoiceItemDetail $detail): void
    {
        if ((int) $detail->invoice_item_id !== (int) $item->id) {
            abort(404);
        }
    }

    private function ensurePenawaranInvoiceAccess(Penawaran $penawaran, $user = null): void
    {
        $user ??= auth()->user();

        $this->ensureCompanyAccess($penawaran, 'company_id', $user);

        if ($this->isSuperadmin($user) || $user->hasRole('admin')) {
            return;
        }

        if ((int) $penawaran->id_user !== (int) $user->id) {
            abort(403);
        }
    }

    private function buildInvoiceKop(Invoice $invoice): array
    {
        $cover = $invoice->penawaran?->cover;
        $company = $invoice->company ?? $invoice->penawaran?->company ?? $invoice->user?->company;

        $logo = $company?->logoFullPath()
            ?: $this->resolvePublicDiskPath($cover?->logo_path)
            ?: public_path('images/logo_arsol.png');

        return [
            'logo' => $logo,
            'nama' => $company?->name ?: ($cover?->perusahaan_nama ?: 'CV. ARTA SOLUSINDO'),
            'alamat' => $company?->address ?: ($cover?->perusahaan_alamat ?: 'Juwangen RT 10 RW 02 Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta 55571'),
            'telp' => $company?->phone ?: ($cover?->perusahaan_telp ?: '(0274) 5044026 / 085727868505'),
            'email' => $company?->email ?: ($cover?->perusahaan_email ?: 'cv.artasolusindo@gmail.com'),
        ];
    }

    private function resolvePublicDiskPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $publicPath = public_path('storage/' . ltrim($path, '/'));
        if (is_file($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/' . ltrim($path, '/'));
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return null;
    }
}
