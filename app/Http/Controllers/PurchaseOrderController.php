<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderTerm;
use App\Models\UsulanPenawaran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $direction = in_array($request->query('direction'), ['incoming', 'outgoing'], true)
            ? $request->query('direction')
            : '';
        $companyId = $this->currentCompanyId($request->user());

        $data = PurchaseOrder::query()
            ->with(['user', 'company', 'supplierCompany', 'usulan'])
            ->when($companyId, function ($query) use ($companyId) {
                $query->where(function ($nested) use ($companyId) {
                    $nested->where('company_id', $companyId)
                        ->orWhere('supplier_company_id', $companyId);
                });
            })
            ->when($direction === 'outgoing', fn ($query) => $query
                ->where('company_id', $companyId)
                ->where('sumber', '!=', 'pelanggan_luar'))
            ->when($direction === 'incoming', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('supplier_company_id', $companyId)
                ->orWhere(fn ($external) => $external
                    ->where('company_id', $companyId)
                    ->where('sumber', 'pelanggan_luar'))))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('nomor_po', 'like', "%{$q}%")
                        ->orWhere('judul', 'like', "%{$q}%")
                        ->orWhere('supplier_nama', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('purchase_orders.index', compact('data', 'q', 'direction', 'companyId'));
    }

    public function create(Request $request)
    {
        $usulan = null;

        if ($request->filled('usulan_id')) {
            $usulan = UsulanPenawaran::query()
                ->with(['company', 'targetCompany', 'penawaran.items.details', 'purchaseOrder'])
                ->findOrFail($request->integer('usulan_id'));
            $this->ensureRequesterAccess($usulan, $request->user());
            abort_unless($usulan->penawaran_status === 'accepted', 422, 'Penawaran belum disetujui.');
            abort_if($usulan->purchaseOrder, 422, 'Purchase Order untuk permintaan ini sudah dibuat.');
        }

        return view('purchase_orders.create', compact('usulan'));
    }

    public function store(Request $request)
    {
        $companyId = (int) $this->currentCompanyId($request->user());
        $usulan = $request->filled('usulan_id')
            ? UsulanPenawaran::query()->with(['company', 'targetCompany', 'penawaran', 'purchaseOrder'])->findOrFail($request->integer('usulan_id'))
            : null;

        if ($usulan) {
            $this->ensureRequesterAccess($usulan, $request->user());
            abort_unless($usulan->penawaran_status === 'accepted', 422);
            abort_if($usulan->purchaseOrder, 422);
        }

        // PO pelanggan luar hanya boleh dibuat tanpa kaitan permintaan harga, karena
        // permintaan harga selalu berasal dari perusahaan di dalam sistem.
        $isExternalCustomer = ! $usulan && $request->input('sumber') === 'pelanggan_luar';

        $payload = $request->validate([
            'usulan_id' => ['nullable', 'integer', 'exists:usulan_penawaran,id'],
            'sumber' => ['nullable', 'in:internal,pelanggan_luar'],
            'nomor_po' => ['nullable', 'string', 'max:50', 'unique:purchase_orders,nomor_po,NULL,id,company_id,'.$companyId],
            'judul' => [$isExternalCustomer ? 'nullable' : 'required', 'string', 'max:255'],
            'pembeli_nama' => [$isExternalCustomer ? 'required' : 'nullable', 'string', 'max:255'],
            'pembeli_alamat' => ['nullable', 'string'],
            'supplier_nama' => [$isExternalCustomer ? 'nullable' : 'required', 'string', 'max:255'],
            'supplier_alamat' => ['nullable', 'string'],
            'tgl_po' => [$isExternalCustomer ? 'nullable' : 'required', 'date'],
            'status' => [$isExternalCustomer ? 'nullable' : 'required', 'in:draft,submitted,approved,cancelled'],
            'jenis_transaksi' => [$isExternalCustomer ? 'nullable' : 'required', 'in:barang,jasa,campuran'],
            'total' => ['required', 'numeric', 'min:1'],
            'catatan' => ['nullable', 'string'],
            // PO pelanggan luar berangkat dari dokumen yang diterima, jadi filenya wajib.
            'po_file' => [$usulan || $isExternalCustomer ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        unset($payload['po_file']);
        $payload['user_id'] = $request->user()->id;
        $payload['company_id'] = $companyId;
        $payload['sumber'] = $isExternalCustomer ? 'pelanggan_luar' : 'internal';

        if ($isExternalCustomer) {
            // Perusahaan pemilik data adalah penjualnya; tidak ada perusahaan pemasok
            // dan tidak ada pihak lain yang perlu menyetujui, jadi langsung aktif.
            $payload['supplier_company_id'] = null;
            $payload['supplier_nama'] = $request->user()->company?->name ?? $payload['supplier_nama'] ?? '-';
            $payload['supplier_alamat'] = $request->user()->company?->address;
            $payload['status'] = 'approved';
            // Isian selain file, pelanggan, dan nilai boleh dilewati: judul diambil dari
            // nama berkas PO yang diunggah, sisanya memakai nilai wajar.
            $payload['judul'] = ($payload['judul'] ?? null)
                ?: Str::limit(pathinfo($request->file('po_file')->getClientOriginalName(), PATHINFO_FILENAME), 250, '');
            $payload['tgl_po'] = $payload['tgl_po'] ?? now()->toDateString();
            $payload['jenis_transaksi'] = $payload['jenis_transaksi'] ?? 'barang';
        } else {
            $payload['pembeli_nama'] = null;
            $payload['pembeli_alamat'] = null;
        }

        if ($usulan) {
            $payload['supplier_company_id'] = $usulan->target_company_id;
            $payload['usulan_id'] = $usulan->id;
            $payload['penawaran_id'] = $usulan->penawaran_id;
            $payload['supplier_nama'] = $usulan->targetCompany?->name ?? $payload['supplier_nama'];
            $payload['supplier_alamat'] = $usulan->targetCompany?->address ?? ($payload['supplier_alamat'] ?? null);
            $payload['jenis_transaksi'] = $usulan->jenis_transaksi;
            $payload['status'] = 'submitted';
        }

        $po = PurchaseOrder::create($payload);

        if (empty($po->nomor_po)) {
            $po->nomor_po = $this->generateNumber($po);
            $po->save();
        }

        if ($request->hasFile('po_file')) {
            $po->po_file_path = $request->file('po_file')->store('purchase-orders/'.$po->id, 'local');
            $po->save();
        }

        return redirect()->route('purchase-orders.show', $po)
            ->with('success', $usulan ? 'Purchase Order berhasil dikirim ke perusahaan penjual.' : 'Purchase Order berhasil dibuat.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensurePurchaseOrderAccess($purchaseOrder, $request->user());
        $purchaseOrder->load([
            'user', 'company', 'supplierCompany', 'usulan', 'penawaran.docNumber', 'penawaran.items.details', 'verifier',
            'terms.paymentVerifier',
        ]);
        $companyId = $this->currentCompanyId($request->user());
        $isBuyer = $purchaseOrder->isBuyerCompany($companyId);
        $isSeller = $purchaseOrder->isSellerCompany($companyId);
        $isLegacy = $purchaseOrder->supplier_company_id === null && $isBuyer;
        $quotationTotal = $purchaseOrder->penawaran?->calcGrandTotal();
        $poDifference = $quotationTotal !== null ? (float) $purchaseOrder->total - $quotationTotal : null;

        return view('purchase_orders.show', compact('purchaseOrder', 'isBuyer', 'isSeller', 'isLegacy', 'quotationTotal', 'poDifference'))
            ->with('po', $purchaseOrder);
    }

    public function verify(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureSellerAccess($purchaseOrder, $request->user());
        abort_unless(in_array($purchaseOrder->status, ['submitted', 'rejected'], true), 422);

        $payload = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'verification_notes' => ['nullable', 'string', 'max:3000'],
            'default_term_count' => ['nullable', 'integer', 'min:1', 'max:24'],
            'first_due_date' => ['nullable', 'date'],
        ]);

        if ($payload['decision'] === 'rejected' && blank($payload['verification_notes'] ?? null)) {
            throw ValidationException::withMessages([
                'verification_notes' => 'Alasan penolakan PO wajib diisi.',
            ]);
        }

        $purchaseOrder->loadMissing('penawaran.items.details');
        $quotationTotal = $purchaseOrder->penawaran?->calcGrandTotal();
        if ($payload['decision'] === 'approved'
            && $quotationTotal !== null
            && abs((float) $purchaseOrder->total - $quotationTotal) >= 0.01
            && blank($payload['verification_notes'] ?? null)) {
            throw ValidationException::withMessages([
                'verification_notes' => 'Nilai PO berbeda dari penawaran. Tambahkan catatan persetujuan selisih nilai.',
            ]);
        }

        DB::transaction(function () use ($purchaseOrder, $payload, $request) {
            $purchaseOrder->update([
                'status' => $payload['decision'],
                'verification_notes' => $payload['verification_notes'] ?? null,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            if ($payload['decision'] === 'approved' && ! $purchaseOrder->terms()->exists()) {
                $this->createDefaultTerms(
                    $purchaseOrder,
                    (int) ($payload['default_term_count'] ?? 5),
                    Carbon::parse($payload['first_due_date'] ?? now()->addMonth()->toDateString())
                );
            }
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', $payload['decision'] === 'approved'
            ? 'PO disetujui dan jadwal termin berhasil dibuat.'
            : 'PO ditolak dan dikembalikan kepada pembeli.');
    }

    public function downloadDocument(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensurePurchaseOrderAccess($purchaseOrder, $request->user());
        abort_unless($purchaseOrder->po_file_path, 404);

        return $this->downloadPrivateDocument($purchaseOrder->po_file_path, 'PO-'.$purchaseOrder->nomor_po);
    }

    public function storeTerm(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->ensureBillingAccess($purchaseOrder, $request->user());
        $this->ensureTermsAreActive($purchaseOrder);
        $payload = $request->validate([
            'tanggal_jatuh_tempo' => ['required', 'date'],
            'nilai_tagihan' => ['required', 'numeric', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $payload['nilai_tagihan'] = $this->roundToRupiah($payload['nilai_tagihan']);
        $this->ensureScheduleFitsPurchaseOrder($purchaseOrder, $payload['nilai_tagihan']);
        $purchaseOrder->terms()->create([
            ...$payload,
            'pembayaran_ke' => ((int) $purchaseOrder->terms()->max('pembayaran_ke')) + 1,
            'status' => 'draft',
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Termin pembayaran berhasil ditambahkan.');
    }

    public function updateBilling(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term)
    {
        $this->ensureBillingAccess($purchaseOrder, $request->user());
        $this->ensureTermsAreActive($purchaseOrder);
        $this->ensureTermBelongsToPurchaseOrder($purchaseOrder, $term);

        $payload = $request->validate([
            'tanggal_jatuh_tempo' => ['required', 'date'],
            'nilai_tagihan' => ['required', 'numeric', 'min:1'],
            'nomor_invoice' => ['nullable', 'string', 'max:100'],
            'tanggal_invoice' => ['nullable', 'date'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'nomor_faktur' => ['nullable', 'string', 'max:100'],
            'faktur_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload['nilai_tagihan'] = $this->roundToRupiah($payload['nilai_tagihan']);

        if ($term->payment_verification_status !== 'none'
            && $payload['nilai_tagihan'] !== (float) $term->nilai_tagihan) {
            throw ValidationException::withMessages([
                'nilai_tagihan' => 'Nilai tagihan tidak dapat diubah setelah pembayaran dicatat.',
            ]);
        }

        $this->ensureScheduleFitsPurchaseOrder($purchaseOrder, $payload['nilai_tagihan'], $term);
        $this->storeTermFiles($request, $purchaseOrder, $term, $payload, [
            'invoice_file' => 'invoice_path',
            'faktur_file' => 'faktur_path',
        ]);
        $term->update($payload);
        $term->syncStatus();

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Invoice/faktur termin ke-'.$term->pembayaran_ke.' berhasil diperbarui.');
    }

    public function updatePayment(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term)
    {
        // Pembayaran dan PPh dicatat penjual, sama seperti invoice dan faktur. Pembeli
        // hanya melihat. PO lama tanpa perusahaan penjual tetap ditangani pembeli.
        $this->ensureBillingAccess($purchaseOrder, $request->user());
        $this->ensureTermsAreActive($purchaseOrder);
        $this->ensureTermBelongsToPurchaseOrder($purchaseOrder, $term);
        abort_unless(filled($term->nomor_invoice) || filled($term->invoice_path), 422, 'Invoice belum diterbitkan.');

        $payload = $request->validate([
            'tanggal_bayar' => ['required', 'date'],
            'nilai_dibayar' => ['required', 'numeric', 'min:0'],
            'bukti_bayar_file' => ['required_without:bukti_bayar_path', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'jenis_pph' => ['nullable', 'in:none,pph_21,pph_22,pph_23,pph_4_2,other'],
            'nilai_pph' => ['nullable', 'numeric', 'min:0'],
            'bukti_potong_pph_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $nilaiDibayar = $this->roundToRupiah($payload['nilai_dibayar']);
        $nilaiPph = $this->roundToRupiah($payload['nilai_pph'] ?? 0);
        $jenisPph = $payload['jenis_pph'] ?? 'none';
        $this->validatePaymentValues($term, $nilaiDibayar, $nilaiPph, $jenisPph);

        if ($nilaiPph > 0 && ! $request->hasFile('bukti_potong_pph_file') && ! $term->bukti_potong_pph_path) {
            throw ValidationException::withMessages([
                'bukti_potong_pph_file' => 'Bukti potong wajib diunggah jika terdapat potongan PPh.',
            ]);
        }

        $payload['nilai_dibayar'] = $nilaiDibayar;
        $payload['nilai_pph'] = $nilaiPph;
        $payload['jenis_pph'] = $jenisPph === 'none' ? null : $jenisPph;
        // Tidak ada langkah verifikasi lagi karena pencatatnya adalah penjual sendiri.
        // Kolomnya tetap diisi sebagai jejak siapa yang mencatat dan kapan.
        $payload['payment_verification_status'] = 'verified';
        $payload['payment_verification_notes'] = null;
        $payload['payment_verified_by'] = $request->user()->id;
        $payload['payment_verified_at'] = now();
        $this->storeTermFiles($request, $purchaseOrder, $term, $payload, [
            'bukti_bayar_file' => 'bukti_bayar_path',
            'bukti_potong_pph_file' => 'bukti_potong_pph_path',
        ]);
        $term->update($payload);
        $term->syncStatus();

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Pembayaran termin ke-'.$term->pembayaran_ke.' berhasil dicatat.');
    }

    public function updateTerm(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term)
    {
        $this->ensureBillingAccess($purchaseOrder, $request->user());
        $this->ensureTermBelongsToPurchaseOrder($purchaseOrder, $term);
        $payload = $request->validate([
            'tanggal_jatuh_tempo' => ['required', 'date'],
            'nilai_tagihan' => ['required', 'numeric', 'min:1'],
            'nomor_invoice' => ['nullable', 'string', 'max:100'],
            'tanggal_invoice' => ['nullable', 'date'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'nomor_faktur' => ['nullable', 'string', 'max:100'],
            'faktur_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'tanggal_bayar' => ['nullable', 'date'],
            'nilai_dibayar' => ['nullable', 'numeric', 'min:0'],
            'bukti_bayar_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'jenis_pph' => ['nullable', 'in:none,pph_21,pph_22,pph_23,pph_4_2,other'],
            'nilai_pph' => ['nullable', 'numeric', 'min:0'],
            'bukti_potong_pph_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);
        $payload['nilai_tagihan'] = $this->roundToRupiah($payload['nilai_tagihan']);
        $this->ensureScheduleFitsPurchaseOrder($purchaseOrder, $payload['nilai_tagihan'], $term);
        $nilaiDibayar = $this->roundToRupiah($payload['nilai_dibayar'] ?? 0);
        $nilaiPph = $this->roundToRupiah($payload['nilai_pph'] ?? 0);
        $jenisPph = $payload['jenis_pph'] ?? 'none';
        $this->validatePaymentValues($term, $nilaiDibayar, $nilaiPph, $jenisPph, (float) $payload['nilai_tagihan']);
        $payload['nilai_dibayar'] = $nilaiDibayar;
        $payload['nilai_pph'] = $nilaiPph;
        $payload['jenis_pph'] = $jenisPph === 'none' ? null : $jenisPph;
        $payload['payment_verification_status'] = 'verified';
        $this->storeTermFiles($request, $purchaseOrder, $term, $payload, [
            'invoice_file' => 'invoice_path', 'faktur_file' => 'faktur_path',
            'bukti_bayar_file' => 'bukti_bayar_path', 'bukti_potong_pph_file' => 'bukti_potong_pph_path',
        ]);
        $term->update($payload);
        $term->syncStatus();

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Termin ke-'.$term->pembayaran_ke.' berhasil diperbarui.');
    }

    public function downloadTermDocument(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term, string $document)
    {
        $this->ensurePurchaseOrderAccess($purchaseOrder, $request->user());
        $this->ensureTermBelongsToPurchaseOrder($purchaseOrder, $term);
        [$path, $label] = match ($document) {
            'invoice' => [$term->invoice_path, 'Invoice'],
            'faktur' => [$term->faktur_path, 'Faktur-Pajak'],
            'bukti-bayar' => [$term->bukti_bayar_path, 'Bukti-Bayar'],
            'bukti-potong-pph' => [$term->bukti_potong_pph_path, 'Bukti-Potong-PPh'],
            default => abort(404),
        };
        abort_unless($path, 404);

        return $this->downloadPrivateDocument($path, $label.'-PO-'.$purchaseOrder->nomor_po.'-Termin-'.$term->pembayaran_ke);
    }

    public function destroyTerm(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term)
    {
        $this->ensureBillingAccess($purchaseOrder, $request->user());
        $this->ensureTermBelongsToPurchaseOrder($purchaseOrder, $term);
        // Termin mana pun boleh dihapus, bukan hanya yang terakhir -- pelunasan sering
        // terjadi lebih cepat sehingga termin di tengah jadwal ikut batal. Yang tetap
        // dijaga: termin yang sudah punya dokumen atau pembayaran tidak boleh hilang,
        // karena itu berarti membuang catatan yang sudah terjadi.
        $hasActivity = (float) $term->nilai_dibayar > 0 || (float) $term->nilai_pph > 0
            || collect([$term->invoice_path, $term->faktur_path, $term->bukti_bayar_path, $term->bukti_potong_pph_path])->filter()->isNotEmpty();
        if ($hasActivity) {
            throw ValidationException::withMessages([
                'termin' => 'Termin yang sudah memiliki dokumen atau pembayaran tidak dapat dihapus.',
            ]);
        }

        $deletedNumber = (int) $term->pembayaran_ke;

        DB::transaction(function () use ($purchaseOrder, $term, $deletedNumber) {
            $term->delete();
            // Nomor dirapatkan supaya tidak berlubang. Digeser menaik satu per satu:
            // setiap nomor tujuan sudah kosong saat gilirannya tiba, jadi batasan unik
            // (purchase_order_id, pembayaran_ke) tidak pernah bentrok.
            $purchaseOrder->terms()
                ->where('pembayaran_ke', '>', $deletedNumber)
                ->orderBy('pembayaran_ke')
                ->get()
                ->each(fn (PurchaseOrderTerm $next) => $next->forceFill([
                    'pembayaran_ke' => (int) $next->pembayaran_ke - 1,
                ])->save());
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Termin pembayaran berhasil dihapus.');
    }

    private function createDefaultTerms(PurchaseOrder $purchaseOrder, int $count, Carbon $firstDueDate): void
    {
        // Dibagi dalam rupiah utuh, bukan sen. Pembulatan sisa pembagian ditaruh di
        // termin terakhir supaya jumlah seluruh termin tetap sama persis dengan nilai PO.
        $total = (int) round((float) $purchaseOrder->total);
        $base = intdiv($total, $count);
        $remainder = $total - ($base * $count);
        for ($index = 1; $index <= $count; $index++) {
            $purchaseOrder->terms()->create([
                'pembayaran_ke' => $index,
                'tanggal_jatuh_tempo' => $firstDueDate->copy()->addMonthsNoOverflow($index - 1),
                'nilai_tagihan' => $base + ($index === $count ? $remainder : 0),
                'status' => 'draft',
            ]);
        }
    }

    /**
     * Nilai uang termin disimpan dalam rupiah utuh -- tidak ada pecahan sen.
     */
    private function roundToRupiah($value): float
    {
        return (float) round((float) $value);
    }

    private function storeTermFiles(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term, array &$payload, array $mappings): void
    {
        foreach ($mappings as $requestField => $modelField) {
            unset($payload[$requestField]);
            if (! $request->hasFile($requestField)) {
                continue;
            }
            $oldPath = $term->{$modelField};
            $payload[$modelField] = $request->file($requestField)
                ->store('purchase-orders/'.$purchaseOrder->id.'/terms/'.$term->id, 'local');
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        }
    }

    private function validatePaymentValues(PurchaseOrderTerm $term, float $paid, float $pph, string $jenisPph, ?float $bill = null): void
    {
        if ($paid + $pph > ($bill ?? (float) $term->nilai_tagihan)) {
            throw ValidationException::withMessages(['nilai_dibayar' => 'Nilai dibayar ditambah potongan PPh tidak boleh melebihi nilai tagihan.']);
        }
        if ($jenisPph === 'none' && $pph > 0) {
            throw ValidationException::withMessages(['jenis_pph' => 'Pilih jenis PPh jika nilai potongan PPh diisi.']);
        }
    }

    private function generateNumber(PurchaseOrder $po): string
    {
        $date = $po->tgl_po?->format('Ymd') ?? now()->format('Ymd');

        return 'PO-'.$date.'-'.str_pad((string) $po->id, 4, '0', STR_PAD_LEFT);
    }

    private function ensurePurchaseOrderAccess(PurchaseOrder $purchaseOrder, $user = null): void
    {
        $user ??= auth()->user();
        $companyId = $this->currentCompanyId($user);
        if ($this->isSuperadmin($user) || $purchaseOrder->isBuyerCompany($companyId) || $purchaseOrder->isSellerCompany($companyId)) {
            return;
        }
        abort(403);
    }

    private function ensureRequesterAccess(UsulanPenawaran $usulan, $user = null): void
    {
        $user ??= auth()->user();
        if ($this->isSuperadmin($user) || $usulan->isRequesterCompany($this->currentCompanyId($user))) {
            return;
        }
        abort(403);
    }

    private function ensureBuyerAccess(PurchaseOrder $purchaseOrder, $user = null): void
    {
        $user ??= auth()->user();
        if ($this->isSuperadmin($user) || $purchaseOrder->isBuyerCompany($this->currentCompanyId($user))) {
            return;
        }
        abort(403);
    }

    private function ensureSellerAccess(PurchaseOrder $purchaseOrder, $user = null): void
    {
        $user ??= auth()->user();
        if ($this->isSuperadmin($user) || $purchaseOrder->isSellerCompany($this->currentCompanyId($user))) {
            return;
        }
        abort(403);
    }

    private function ensureBillingAccess(PurchaseOrder $purchaseOrder, $user = null): void
    {
        // PO pelanggan luar dan PO antar perusahaan sama-sama dikerjakan penjual.
        // Hanya PO pembelian ke pemasok luar yang ditangani pembelinya sendiri.
        if ($purchaseOrder->isExternalCustomerOrder() || $purchaseOrder->supplier_company_id !== null) {
            $this->ensureSellerAccess($purchaseOrder, $user);

            return;
        }
        $this->ensureBuyerAccess($purchaseOrder, $user);
    }

    private function ensureTermsAreActive(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->supplier_company_id !== null) {
            abort_unless($purchaseOrder->status === 'approved', 422, 'Termin hanya aktif setelah PO disetujui penjual.');
        }
    }

    private function ensureTermBelongsToPurchaseOrder(PurchaseOrder $purchaseOrder, PurchaseOrderTerm $term): void
    {
        abort_unless((int) $term->purchase_order_id === (int) $purchaseOrder->id, 404);
    }

    private function ensureScheduleFitsPurchaseOrder(PurchaseOrder $purchaseOrder, float $value, ?PurchaseOrderTerm $excluded = null): void
    {
        if ((float) $purchaseOrder->total <= 0) {
            throw ValidationException::withMessages(['nilai_tagihan' => 'Nilai total PO harus lebih dari nol sebelum membuat termin.']);
        }
        $scheduled = (float) $purchaseOrder->terms()
            ->when($excluded, fn ($query) => $query->where('id', '!=', $excluded->id))
            ->sum('nilai_tagihan');
        if ($scheduled + $value > (float) $purchaseOrder->total) {
            throw ValidationException::withMessages(['nilai_tagihan' => 'Total seluruh termin tidak boleh melebihi nilai PO.']);
        }
    }

    private function downloadPrivateDocument(string $path, string $filename)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);

        return $disk->download($path, $safeFilename.($extension ? '.'.$extension : ''));
    }
}
