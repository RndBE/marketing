<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranCover;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranSignature;
use App\Models\PenawaranTerm;
use App\Models\PenawaranTermTemplate;
use App\Models\PenawaranValidity;
use App\Models\Pic;
use App\Models\Product;
use App\Models\UsulanAttachment;
use App\Models\UsulanItem;
use App\Models\UsulanPenawaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PenawaranHargaController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $direction = in_array($request->query('direction'), ['incoming', 'outgoing'], true)
            ? $request->query('direction')
            : '';
        $companyId = $this->currentCompanyId($request->user());

        $usulan = UsulanPenawaran::query()
            ->with(['pic', 'company', 'targetCompany', 'creator', 'penawaran', 'prospect', 'purchaseOrder'])
            // Modul ini hanya memuat permintaan yang ditujukan ke perusahaan lain.
            // Usulan internal tanpa perusahaan tujuan tetap di modul Usulan.
            ->whereNotNull('target_company_id')
            ->visibleToCompany($companyId)
            ->when($direction === 'outgoing', fn ($q) => $q->where('company_id', $companyId))
            ->when($direction === 'incoming', fn ($q) => $q->where('target_company_id', $companyId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('penawaran_harga.index', compact('usulan', 'status', 'direction', 'companyId'));
    }

    public function create(Request $request)
    {
        $companyId = $this->currentCompanyId($request->user());
        $companies = Company::query()->where('id', '!=', $companyId)->orderBy('name')->get();
        $pics = Pic::query()->orderBy('instansi')->get();
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->with('details')
            ->get();
        $bundleProducts = $products->map(function ($p) {
            $unit = 0;
            foreach ($p->details as $d) {
                $harga = (int) ($d->harga ?? 0);
                if ($harga <= 0) {
                    $qty = (float) ($d->qty ?? 1);
                    $sub = (int) ($d->subtotal ?? 0);
                    if ($sub > 0 && $qty > 0) {
                        $harga = (int) round($sub / $qty);
                    }
                }
                $unit += $harga;
            }

            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'satuan' => $p->satuan,
                'unit_price' => $unit,
            ];
        })->values()->all();

        return view('penawaran_harga.create', compact('pics', 'products', 'bundleProducts', 'companies'));
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'judul' => 'required|string|max:255',
            'target_company_id' => 'required|integer|exists:companies,id',
            'jenis_transaksi' => 'required|in:barang,jasa,campuran',
            'pic_id' => 'nullable|exists:pics,id',
            'deskripsi' => 'nullable|string',
            'nilai_estimasi' => 'nullable|integer|min:0',
            'tanggal_dibutuhkan' => 'nullable|date',
            'status' => 'nullable|in:draft,menunggu',
            'signature_name' => 'nullable|string|max:255',
            'signature_position' => 'nullable|string|max:255',
            'signature_city' => 'nullable|string|max:120',
            'signature_date' => 'nullable|date',
            'signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
            'attachments.*' => 'nullable|file|max:10240', // max 10MB
            'attachment_types.*' => 'nullable|string',
            'item_judul.*' => 'nullable|string|max:255',
            'item_catatan.*' => 'nullable|string',
            'item_poin' => 'nullable|array',
            'item_poin.*' => 'nullable|array',
            'item_poin.*.*' => 'nullable|string|max:1000',
            'item_qty.*' => 'nullable|numeric|min:0.01',
            'item_satuan.*' => 'nullable|string|max:50',
            'item_harga.*' => 'nullable|integer|min:0',
            'item_tipe.*' => 'nullable|in:custom,bundle',
            'item_product_id.*' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($payload, $request) {
            $companyId = (int) $this->currentCompanyId($request->user());

            if ((int) $payload['target_company_id'] === $companyId) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'target_company_id' => 'Perusahaan tujuan harus berbeda dari perusahaan pengirim.',
                ]);
            }

            if (! empty($payload['pic_id'])) {
                Pic::findOrFail($payload['pic_id']);
            }

            $usulan = UsulanPenawaran::create([
                'company_id' => $companyId,
                'target_company_id' => $payload['target_company_id'],
                'judul' => $payload['judul'],
                'jenis_transaksi' => $payload['jenis_transaksi'],
                'pic_id' => $payload['pic_id'] ?? null,
                'deskripsi' => $payload['deskripsi'] ?? null,
                'nilai_estimasi' => $payload['nilai_estimasi'] ?? 0,
                'tanggal_dibutuhkan' => $payload['tanggal_dibutuhkan'] ?? null,
                'created_by' => auth()->id(),
                'status' => $payload['status'] ?? 'draft',
                'signature_name' => $payload['signature_name'] ?? $request->user()->name,
                'signature_position' => $payload['signature_position'] ?? null,
                'signature_city' => $payload['signature_city'] ?? null,
                'signature_date' => $payload['signature_date'] ?? null,
            ]);

            if ($request->hasFile('signature_file')) {
                $usulan->update([
                    'signature_path' => $request->file('signature_file')->store('usulan/ttd', 'public'),
                ]);
            }

            $usulan->sharedCompanies()->syncWithoutDetaching([(int) $payload['target_company_id']]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $types = $request->input('attachment_types', []);
                foreach ($request->file('attachments') as $i => $file) {
                    $path = $file->store('usulan/'.$usulan->id, 'local');
                    UsulanAttachment::create([
                        'usulan_id' => $usulan->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path' => $path,
                        'tipe' => $types[$i] ?? 'dokumen',
                    ]);
                }
            }

            $this->syncItemsFromRequest($usulan, $request);

            return redirect()->route('penawaran-harga.show', $usulan->id)->with('success', 'Usulan berhasil dibuat');
        });
    }

    /**
     * Kebalikan dari modul Usulan: data tanpa perusahaan tujuan adalah usulan internal
     * dan ditangani modul Usulan.
     */
    private function redirectIfBelongsToUsulan(UsulanPenawaran $usulan)
    {
        return $usulan->target_company_id === null
            ? redirect()->route('usulan.show', $usulan)
            : null;
    }

    public function show(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $companyId = $this->currentCompanyId();
        $usulan->load([
            'pic',
            'company',
            'targetCompany',
            'creator',
            'responder',
            'attachments',
            'items',
            'penawaran.docNumber',
            'penawaran.sharedCompanies',
            'penawaran.approval',
            'purchaseOrder',
            'prospect.sharedCompanies',
            'prospect',
        ]);

        $canViewLinkedProspect = $usulan->prospect
            ? ($this->isSuperadmin() || $usulan->prospect->isVisibleToCompany($companyId))
            : false;

        $isRequester = $usulan->isRequesterCompany($companyId);
        $isSupplier = $usulan->isSupplierCompany($companyId);
        $canViewLinkedPenawaran = $usulan->penawaran
            ? ($this->isSuperadmin() || $usulan->penawaran->isVisibleToCompany($companyId))
                && (! $isRequester || $usulan->penawaran_status !== 'draft')
            : false;
        $canRespond = $isSupplier && auth()->user()->hasPermission('respond-usulan');
        $canEditRequestSignature = $isRequester
            && auth()->user()->hasPermission('edit-usulan')
            && ($this->isSuperadmin() || auth()->user()->hasRole('admin') || (int) $usulan->created_by === (int) auth()->id());
        $canEditLinkedQuotation = $usulan->penawaran
            && $isSupplier
            && auth()->user()->hasPermission('respond-usulan')
            && in_array($usulan->penawaran_status, ['draft', 'revision_requested'], true);

        return view('penawaran_harga.show', compact(
            'usulan',
            'canViewLinkedProspect',
            'canViewLinkedPenawaran',
            'isRequester',
            'isSupplier',
            'canRespond',
            'canEditRequestSignature',
            'canEditLinkedQuotation'
        ));
    }

    public function downloadPdf(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);

        $usulan->load([
            'company',
            'targetCompany',
            'pic',
            'creator.roles',
            'items',
        ]);

        $documentDate = ($usulan->created_at ?? now())->copy();
        $documentNumber = $this->usulanDocumentNumber($usulan, $documentDate);

        $kop = [
            'logo' => $this->usulanCompanyDocumentLogo($usulan->company),
            'stamp' => $usulan->company?->stampFullPath(),
            'name' => $usulan->company?->name ?: 'Perusahaan Pengirim',
            'address' => $usulan->company?->address ?: '-',
            'phone' => $usulan->company?->phone ?: '-',
            'email' => $usulan->company?->email ?: '-',
        ];
        $signaturePath = $this->resolveUsulanPublicImagePath(
            $usulan->signature_path ?: $usulan->creator?->ttd
        );
        $signaturePlacement = $this->pdfSignaturePlacement($signaturePath);
        $filename = 'Permohonan-Penawaran-'.str_replace(['/', '\\'], '-', $documentNumber).'.pdf';

        $pdf = Pdf::loadView('penawaran_harga.pdf', compact(
            'usulan',
            'documentDate',
            'documentNumber',
            'kop',
            'signaturePath',
            'signaturePlacement'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    public function updateSignature(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        $payload = $request->validate([
            'signature_name' => ['required', 'string', 'max:255'],
            'signature_position' => ['nullable', 'string', 'max:255'],
            'signature_city' => ['nullable', 'string', 'max:120'],
            'signature_date' => ['nullable', 'date'],
            'signature_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('signature_file')) {
            if ($usulan->signature_path) {
                Storage::disk('public')->delete($usulan->signature_path);
            }

            $payload['signature_path'] = $request->file('signature_file')
                ->store('usulan/ttd', 'public');
        }

        unset($payload['signature_file']);
        $usulan->update($payload);

        return redirect()->route('penawaran-harga.show', $usulan)
            ->with('success', 'Tanda tangan Permohonan Penawaran berhasil disimpan.');
    }

    public function deleteSignature(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if ($usulan->signature_path) {
            Storage::disk('public')->delete($usulan->signature_path);
            $usulan->forceFill(['signature_path' => null])->save();
        }

        return redirect()->route('penawaran-harga.show', $usulan)
            ->with('success', 'File TTD Permohonan Penawaran berhasil dihapus.');
    }

    public function downloadQuotationPdf(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        abort_unless($usulan->penawaran_id, 404, 'Penawaran belum dibuat.');

        $usulan->load([
            'company',
            'targetCompany',
            'pic',
            'penawaran.company',
            'penawaran.user.roles',
            'penawaran.docNumber',
            'penawaran.items.product',
            'penawaran.items.details',
            'penawaran.terms',
            'penawaran.validity',
            'penawaran.signatures',
            'penawaran.sharedCompanies',
        ]);

        $penawaran = $usulan->penawaran;
        $companyId = $this->currentCompanyId();
        abort_unless($this->isSuperadmin() || $penawaran->isVisibleToCompany($companyId), 403);
        abort_if(
            $usulan->isRequesterCompany($companyId) && $usulan->penawaran_status === 'draft',
            403,
            'Draft Penawaran Harga belum dikirim oleh perusahaan penjual.'
        );

        $requestDate = ($usulan->created_at ?? now())->copy();
        $requestDocumentNumber = $this->usulanDocumentNumber($usulan, $requestDate);
        $quotationDate = $penawaran->tanggal_penawaran?->copy()
            ?? ($penawaran->date_created
                ? Carbon::createFromTimestamp((int) $penawaran->date_created)
                : ($penawaran->created_at ?? now())->copy());
        $quotationNumber = $penawaran->docNumber?->doc_no
            ?? ('PNW-'.str_pad((string) $penawaran->id, 6, '0', STR_PAD_LEFT));
        $supplier = $penawaran->company ?? $usulan->targetCompany;
        $signature = $penawaran->signatures->first();
        $signaturePath = $this->resolveUsulanPublicImagePath(
            $signature?->ttd_path ?: $penawaran->user?->ttd
        );
        $signaturePlacement = $this->pdfSignaturePlacement($signaturePath);
        $kop = [
            'logo' => $this->usulanCompanyDocumentLogo($supplier),
            'stamp' => $supplier?->stampFullPath(),
            'name' => $supplier?->name ?: 'Perusahaan Penjual',
            'address' => $supplier?->address ?: '-',
            'phone' => $supplier?->phone ?: '-',
            'email' => $supplier?->email ?: '-',
        ];
        $filename = 'Penawaran-Harga-Permohonan-'.str_replace(['/', '\\'], '-', $quotationNumber).'.pdf';

        $pdf = Pdf::loadView('penawaran_harga.quotation_pdf', compact(
            'usulan',
            'penawaran',
            'requestDate',
            'requestDocumentNumber',
            'quotationDate',
            'quotationNumber',
            'kop',
            'signature',
            'signaturePath',
            'signaturePlacement'
        ))->setPaper('a4', 'portrait');

        $response = $pdf->stream($filename);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    public function showQuotation(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        abort_unless($usulan->penawaran_id, 404, 'Penawaran Harga belum dibuat.');

        $usulan->load([
            'company',
            'targetCompany',
            'pic',
            'penawaran.company',
            'penawaran.user.roles',
            'penawaran.docNumber',
            'penawaran.items.product',
            'penawaran.items.details',
            'penawaran.terms',
            'penawaran.validity',
            'penawaran.signatures',
            'purchaseOrder',
        ]);

        $companyId = $this->currentCompanyId();
        $isRequester = $usulan->isRequesterCompany($companyId);
        $isSupplier = $usulan->isSupplierCompany($companyId);
        abort_if(
            $isRequester && $usulan->penawaran_status === 'draft',
            403,
            'Draft Penawaran Harga belum dikirim oleh perusahaan penjual.'
        );
        $canEditQuotation = $isSupplier
            && auth()->user()->hasPermission('respond-usulan')
            && in_array($usulan->penawaran_status, ['draft', 'revision_requested'], true);
        $requestDocumentNumber = $this->usulanDocumentNumber(
            $usulan,
            ($usulan->created_at ?? now())->copy()
        );
        $quotationNumber = $usulan->penawaran->docNumber?->doc_no
            ?? ('PNW-'.str_pad((string) $usulan->penawaran->id, 6, '0', STR_PAD_LEFT));

        return view('penawaran_harga.quotation_show', compact(
            'usulan',
            'isRequester',
            'isSupplier',
            'canEditQuotation',
            'requestDocumentNumber',
            'quotationNumber'
        ));
    }

    public function updateQuotation(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $this->ensureSupplierAccess($usulan, $request->user());
        abort_unless($usulan->penawaran_id, 404, 'Penawaran Harga belum dibuat.');
        abort_unless(in_array($usulan->penawaran_status, ['draft', 'revision_requested'], true), 422, 'Penawaran tidak dapat diedit pada status ini.');

        $usulan->load([
            'penawaran.items.details',
            'penawaran.terms',
            'penawaran.validity',
            'penawaran.signatures',
        ]);
        $penawaran = $usulan->penawaran;

        $payload = $request->validate([
            'tanggal_penawaran' => ['nullable', 'date'],
            'nama_pekerjaan' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:5000'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:tanggal_penawaran'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.judul' => ['required', 'string', 'max:255'],
            'items.*.catatan' => ['nullable', 'string', 'max:5000'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.satuan' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms' => ['nullable', 'array'],
            'terms.*' => ['nullable', 'string', 'max:3000'],
            'signature_name' => ['required', 'string', 'max:255'],
            'signature_position' => ['nullable', 'string', 'max:255'],
            'signature_city' => ['nullable', 'string', 'max:120'],
            'signature_date' => ['nullable', 'date'],
            'signature_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        return DB::transaction(function () use ($request, $payload, $penawaran, $usulan) {
            $penawaran->update([
                'tanggal_penawaran' => $payload['tanggal_penawaran'] ?? null,
                'nama_pekerjaan' => $payload['nama_pekerjaan'] ?? null,
                'catatan' => $payload['catatan'] ?? null,
                'discount_enabled' => $request->boolean('discount_enabled'),
                'discount_type' => $request->boolean('discount_enabled')
                    ? ($payload['discount_type'] ?? 'percent')
                    : null,
                'discount_value' => $request->boolean('discount_enabled')
                    ? ($payload['discount_value'] ?? 0)
                    : null,
                'tax_enabled' => $request->boolean('tax_enabled'),
                'tax_rate' => $request->boolean('tax_enabled')
                    ? ($payload['tax_rate'] ?? 11)
                    : null,
                'date_updated' => now()->timestamp,
            ]);

            foreach ($penawaran->items as $item) {
                $itemPayload = $payload['items'][$item->id] ?? null;
                if (! is_array($itemPayload)) {
                    continue;
                }

                $this->updateRequestQuotationItem($item, $itemPayload);
            }

            foreach ($penawaran->terms as $term) {
                if (array_key_exists($term->id, $payload['terms'] ?? [])) {
                    $term->update(['isi' => $payload['terms'][$term->id]]);
                }
            }

            $validUntil = $payload['valid_until'] ?? null;
            $validFrom = Carbon::parse($payload['tanggal_penawaran'] ?? now()->toDateString())->startOfDay();
            PenawaranValidity::updateOrCreate(
                ['penawaran_id' => $penawaran->id],
                [
                    'mulai' => $validFrom->toDateString(),
                    'sampai' => $validUntil,
                    'berlaku_hari' => $validUntil
                        ? max(1, (int) $validFrom->diffInDays(Carbon::parse($validUntil)->startOfDay()))
                        : null,
                    'keterangan' => $validUntil ? null : 'Masa berlaku mengikuti kesepakatan.',
                ]
            );

            $signature = $penawaran->signatures->first();
            $signatureData = [
                'nama' => $payload['signature_name'],
                'jabatan' => $payload['signature_position'] ?? null,
                'kota' => $payload['signature_city'] ?? null,
                'tanggal' => $payload['signature_date'] ?? null,
            ];

            if ($request->hasFile('signature_file')) {
                $oldSignaturePath = $signature?->ttd_path;
                $signatureData['ttd_path'] = $request->file('signature_file')->store('penawaran/ttd', 'public');
                if ($oldSignaturePath) {
                    Storage::disk('public')->delete($oldSignaturePath);
                }
            }

            if ($signature) {
                $signature->update($signatureData);
            } else {
                $signatureData['penawaran_id'] = $penawaran->id;
                $signatureData['urutan'] = 1;
                PenawaranSignature::create($signatureData);
            }

            return redirect()->route('penawaran-harga.quotation.show', $usulan)
                ->with('success', 'Penawaran Harga berhasil disimpan.');
        });
    }

    public function edit(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if (! in_array($usulan->status, ['draft', 'menunggu'])) {
            return redirect()->route('penawaran-harga.show', $usulan->id)->with('error', 'Usulan tidak bisa diedit');
        }

        $companyId = $this->currentCompanyId();
        $companies = Company::query()->where('id', '!=', $companyId)->orderBy('name')->get();
        $pics = Pic::query()->orderBy('instansi')->get();
        $usulan->load(['attachments', 'items']);
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->with('details')
            ->get();
        $bundleProducts = $products->map(function ($p) {
            $unit = 0;
            foreach ($p->details as $d) {
                $harga = (int) ($d->harga ?? 0);
                if ($harga <= 0) {
                    $qty = (float) ($d->qty ?? 1);
                    $sub = (int) ($d->subtotal ?? 0);
                    if ($sub > 0 && $qty > 0) {
                        $harga = (int) round($sub / $qty);
                    }
                }
                $unit += $harga;
            }

            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'satuan' => $p->satuan,
                'unit_price' => $unit,
            ];
        })->values()->all();

        return view('penawaran_harga.edit', compact('usulan', 'pics', 'products', 'bundleProducts', 'companies'));
    }

    public function update(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if (! in_array($usulan->status, ['draft', 'menunggu'])) {
            return redirect()->route('penawaran-harga.show', $usulan->id)->with('error', 'Usulan tidak bisa diedit');
        }

        $payload = $request->validate([
            'judul' => 'required|string|max:255',
            'target_company_id' => 'required|integer|exists:companies,id',
            'jenis_transaksi' => 'required|in:barang,jasa,campuran',
            'pic_id' => 'nullable|exists:pics,id',
            'deskripsi' => 'nullable|string',
            'nilai_estimasi' => 'nullable|integer|min:0',
            'tanggal_dibutuhkan' => 'nullable|date',
            'status' => 'nullable|in:draft,menunggu',
            'signature_name' => 'nullable|string|max:255',
            'signature_position' => 'nullable|string|max:255',
            'signature_city' => 'nullable|string|max:120',
            'signature_date' => 'nullable|date',
            'signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
            'attachments.*' => 'nullable|file|max:10240',
            'attachment_types.*' => 'nullable|string',
            'item_judul.*' => 'nullable|string|max:255',
            'item_catatan.*' => 'nullable|string',
            'item_poin' => 'nullable|array',
            'item_poin.*' => 'nullable|array',
            'item_poin.*.*' => 'nullable|string|max:1000',
            'item_qty.*' => 'nullable|numeric|min:0.01',
            'item_satuan.*' => 'nullable|string|max:50',
            'item_harga.*' => 'nullable|integer|min:0',
            'item_tipe.*' => 'nullable|in:custom,bundle',
            'item_product_id.*' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($payload, $request, $usulan) {
            if ((int) $payload['target_company_id'] === (int) $usulan->company_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'target_company_id' => 'Perusahaan tujuan harus berbeda dari perusahaan pengirim.',
                ]);
            }

            if (! empty($payload['pic_id'])) {
                Pic::findOrFail($payload['pic_id']);
            }

            $updateData = [
                'judul' => $payload['judul'],
                'target_company_id' => $payload['target_company_id'],
                'jenis_transaksi' => $payload['jenis_transaksi'],
                'pic_id' => $payload['pic_id'] ?? null,
                'deskripsi' => $payload['deskripsi'] ?? null,
                'nilai_estimasi' => $payload['nilai_estimasi'] ?? 0,
                'tanggal_dibutuhkan' => $payload['tanggal_dibutuhkan'] ?? null,
                'status' => $payload['status'] ?? 'draft',
            ];

            foreach (['signature_name', 'signature_position', 'signature_city', 'signature_date'] as $signatureField) {
                if (array_key_exists($signatureField, $payload)) {
                    $updateData[$signatureField] = $payload[$signatureField];
                }
            }

            $oldSignaturePath = $usulan->signature_path;
            if ($request->hasFile('signature_file')) {
                $updateData['signature_path'] = $request->file('signature_file')->store('usulan/ttd', 'public');
            }

            $usulan->update($updateData);

            if (isset($updateData['signature_path']) && $oldSignaturePath) {
                Storage::disk('public')->delete($oldSignaturePath);
            }

            $usulan->sharedCompanies()->sync([(int) $payload['target_company_id']]);

            // Handle new file uploads
            if ($request->hasFile('attachments')) {
                $types = $request->input('attachment_types', []);
                foreach ($request->file('attachments') as $i => $file) {
                    $path = $file->store('usulan/'.$usulan->id, 'local');
                    UsulanAttachment::create([
                        'usulan_id' => $usulan->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path' => $path,
                        'tipe' => $types[$i] ?? 'dokumen',
                    ]);
                }
            }

            $this->syncItemsFromRequest($usulan, $request);

            return redirect()->route('penawaran-harga.show', $usulan->id)->with('success', 'Usulan berhasil diupdate');
        });
    }

    public function tanggapi(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $this->ensureSupplierAccess($usulan, $request->user());

        $payload = $request->validate([
            'tanggapan' => 'required|string',
            'status' => 'required|in:ditanggapi,disetujui,ditolak',
            'penawaran_action' => 'nullable|in:none,empty,from_usulan',
        ]);

        $action = $payload['penawaran_action'] ?? 'none';

        if ($action !== 'none' && $payload['status'] !== 'disetujui') {
            return back()->with('error', 'Penawaran hanya bisa dibuat jika status Disetujui.');
        }

        if ($action === 'from_usulan' && ! $usulan->items()->exists()) {
            return back()->with('error', 'Item usulan masih kosong. Tambahkan item terlebih dahulu.');
        }

        return DB::transaction(function () use ($usulan, $payload, $action) {
            $usulan->update([
                'tanggapan' => $payload['tanggapan'],
                'status' => $payload['status'],
                'ditanggapi_oleh' => auth()->id(),
                'tanggal_ditanggapi' => now(),
            ]);

            if ($action === 'none') {
                return redirect()->route('penawaran-harga.show', $usulan->id)->with('success', 'Tanggapan berhasil disimpan');
            }

            if ($usulan->penawaran_id) {
                return redirect()->route('penawaran-harga.quotation.show', $usulan)
                    ->with('success', 'Penawaran sudah tersedia.');
            }

            $copyItems = $action === 'from_usulan';
            $penawaran = $this->createPenawaranFromUsulan($usulan, $copyItems);

            $message = $copyItems
                ? 'Penawaran berhasil dibuat dari item usulan'
                : 'Penawaran berhasil dibuat dari usulan';

            return redirect()->route('penawaran-harga.quotation.show', $usulan)->with('success', $message);
        });
    }

    public function buatPenawaran(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $this->ensureSupplierAccess($usulan, $request->user());

        if ($usulan->penawaran_id) {
            return redirect()->route('penawaran-harga.quotation.show', $usulan);
        }

        $copyItems = (bool) $request->input('copy_items', false);

        if ($copyItems && ! $usulan->items()->exists()) {
            return back()->with('error', 'Item usulan masih kosong. Tambahkan item terlebih dahulu.');
        }

        return DB::transaction(function () use ($usulan, $copyItems) {
            $penawaran = $this->createPenawaranFromUsulan($usulan, $copyItems);
            $message = $copyItems
                ? 'Penawaran berhasil dibuat dari item usulan'
                : 'Penawaran berhasil dibuat dari usulan';

            return redirect()->route('penawaran-harga.quotation.show', $usulan)->with('success', $message);
        });
    }

    public function sendQuotation(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $this->ensureSupplierAccess($usulan, $request->user());

        $usulan->loadMissing(['penawaran.approval', 'penawaran.items.details', 'company']);
        abort_unless($usulan->penawaran, 422, 'Penawaran belum dibuat.');

        if ($usulan->penawaran->calcGrandTotal() <= 0) {
            return back()->with('error', 'Nilai penawaran harus lebih dari nol sebelum dikirim.');
        }

        $usulan->penawaran->sharedCompanies()->syncWithoutDetaching([(int) $usulan->company_id]);
        $usulan->update([
            'status' => 'ditanggapi',
            'penawaran_status' => 'sent',
            'tanggal_ditanggapi' => now(),
            'ditanggapi_oleh' => $request->user()->id,
        ]);

        return back()->with('success', 'Penawaran berhasil dikirim ke '.($usulan->company?->name ?? 'perusahaan peminta').'.');
    }

    public function respondQuotation(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $this->ensureRequesterAccess($usulan, $request->user());

        abort_unless($usulan->penawaran_id && $usulan->penawaran_status === 'sent', 422);

        $payload = $request->validate([
            'action' => ['required', 'in:accepted,revision_requested,rejected'],
            'penawaran_tanggapan' => ['nullable', 'string', 'max:3000'],
        ]);

        if (in_array($payload['action'], ['revision_requested', 'rejected'], true)
            && blank($payload['penawaran_tanggapan'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'penawaran_tanggapan' => 'Catatan wajib diisi untuk permintaan revisi atau penolakan.',
            ]);
        }

        $usulan->update([
            'penawaran_status' => $payload['action'],
            'penawaran_tanggapan' => $payload['penawaran_tanggapan'] ?? null,
            'status' => $payload['action'] === 'rejected' ? 'ditolak' : 'ditanggapi',
        ]);

        $message = match ($payload['action']) {
            'accepted' => 'Penawaran disetujui. Silakan unggah Purchase Order.',
            'revision_requested' => 'Permintaan revisi penawaran berhasil dikirim.',
            'rejected' => 'Penawaran ditolak.',
        };

        return back()->with('success', $message);
    }

    private function syncItemsFromRequest(UsulanPenawaran $usulan, Request $request): void
    {
        if (! $request->has('items_present')) {
            return;
        }

        $judul = $request->input('item_judul', []);
        $catatan = $request->input('item_catatan', []);
        $points = $request->input('item_poin', []);
        $qty = $request->input('item_qty', []);
        $satuan = $request->input('item_satuan', []);
        $harga = $request->input('item_harga', []);
        $tipe = $request->input('item_tipe', []);
        $productId = $request->input('item_product_id', []);

        $usulan->items()->delete();

        $order = 1;
        foreach ($judul as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $q = isset($qty[$i]) && is_numeric($qty[$i]) ? (float) $qty[$i] : 1;
            if ($q <= 0) {
                $q = 1;
            }

            $h = isset($harga[$i]) && is_numeric($harga[$i]) ? (int) $harga[$i] : 0;
            if ($h < 0) {
                $h = 0;
            }

            $sub = (int) round($q * $h);
            $normalizedProductId = null;
            if (isset($productId[$i]) && is_numeric($productId[$i])) {
                $candidate = Product::query()->find((int) $productId[$i]);
                $normalizedProductId = $candidate?->id;
            }

            $resolvedType = ($tipe[$i] ?? 'custom') === 'bundle' && $normalizedProductId
                ? 'bundle'
                : 'custom';
            $normalizedPoints = collect($points[$i] ?? [])
                ->map(fn ($point) => trim((string) $point))
                ->filter()
                ->values();
            $resolvedNotes = $normalizedPoints->isNotEmpty()
                ? $normalizedPoints->implode("\n")
                : ($catatan[$i] ?? null);

            UsulanItem::create([
                'usulan_id' => $usulan->id,
                'product_id' => $normalizedProductId,
                'tipe' => $resolvedType,
                'urutan' => $order++,
                'judul' => $name,
                'catatan' => $resolvedNotes,
                'qty' => $q,
                'satuan' => $satuan[$i] ?? null,
                'harga' => $h,
                'subtotal' => $sub,
            ]);
        }
    }

    private function createPenawaranFromUsulan(UsulanPenawaran $usulan, bool $copyItems): Penawaran
    {
        if ($usulan->penawaran_id) {
            return Penawaran::findOrFail($usulan->penawaran_id);
        }

        $supplierCompanyId = (int) $usulan->target_company_id;
        abort_if($supplierCompanyId <= 0, 422, 'Perusahaan tujuan belum ditentukan.');

        $owner = $this->resolveCompanyUser($supplierCompanyId, auth()->id());
        $company = $owner->company;
        $docNumber = $this->createDocNumber($supplierCompanyId, (int) $owner->id);

        $penawaran = Penawaran::create([
            'company_id' => $supplierCompanyId,
            'id_pic' => $usulan->pic_id,
            'id_user' => $owner->id,
            'prospect_id' => $usulan->prospect_id,
            'doc_number_id' => $docNumber->id,
            'approval_id' => null,
            'judul' => $usulan->judul,
            'catatan' => $usulan->deskripsi,
            'instansi_tujuan' => $usulan->company?->name ?? $usulan->pic?->instansi,
            'date_created' => now()->timestamp,
            'date_updated' => now()->timestamp,
            'status' => 'draft',
        ]);

        PenawaranCover::create([
            'penawaran_id' => $penawaran->id,
            'judul_cover' => 'Dokumen Penawaran',
            'subjudul' => $penawaran->judul,
            'perusahaan_nama' => $company?->name ?? 'CV. ARTA SOLUSINDO',
            'perusahaan_alamat' => $company?->address,
            'perusahaan_email' => $company?->email,
            'perusahaan_telp' => $company?->phone,
            'logo_path' => $company?->logo_path,
        ]);

        PenawaranValidity::create([
            'penawaran_id' => $penawaran->id,
            'mulai' => now()->toDateString(),
            'sampai' => now()->addDays(30)->toDateString(),
            'berlaku_hari' => 30,
            'keterangan' => 'Penawaran berlaku 30 hari.',
        ]);

        $templates = PenawaranTermTemplate::query()
            ->where('company_id', $supplierCompanyId)
            ->whereNull('parent_id')
            ->orderBy('urutan')
            ->orderBy('id')
            ->with(['children'])
            ->get();

        foreach ($templates as $t) {
            $this->cloneTemplateTerm($penawaran->id, $t, null);
        }

        if ($copyItems) {
            $this->copyUsulanItemsToPenawaran($usulan, $penawaran);
        } else {
            $this->createBlankRequestQuotationItem($usulan, $penawaran);
        }

        $penawaran->sharedCompanies()->syncWithoutDetaching([(int) $usulan->company_id]);

        $roleNames = $owner->roles->pluck('name')->implode(', ');

        PenawaranSignature::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => 1,
            'nama' => $owner->name,
            'jabatan' => $roleNames ?: 'Staff',
            'kota' => 'Sleman',
            'tanggal' => now()->toDateString(),
            'ttd_path' => $owner->ttd,
        ]);

        $update = [
            'penawaran_id' => $penawaran->id,
            'penawaran_status' => 'draft',
        ];

        if ($usulan->status !== 'disetujui') {
            $update['status'] = 'disetujui';
        }

        if (! $usulan->tanggal_ditanggapi) {
            $update['tanggal_ditanggapi'] = now();
        }

        if (! $usulan->ditanggapi_oleh) {
            $update['ditanggapi_oleh'] = $owner->id;
        }

        $usulan->update($update);

        return $penawaran;
    }

    private function createBlankRequestQuotationItem(UsulanPenawaran $usulan, Penawaran $penawaran): void
    {
        $item = PenawaranItem::create([
            'penawaran_id' => $penawaran->id,
            'product_id' => null,
            'tipe' => 'custom',
            'urutan' => 1,
            'judul' => $usulan->judul ?: 'Item Penawaran',
            'catatan' => $usulan->deskripsi,
            'qty' => 1,
            'satuan' => 'paket',
            'subtotal' => 0,
            'markup' => 1,
        ]);

        PenawaranItemDetail::create([
            'penawaran_item_id' => $item->id,
            'product_detail_id' => null,
            'urutan' => 1,
            'nama' => $item->judul,
            'spesifikasi' => null,
            'qty' => 1,
            'satuan' => 'paket',
            'harga' => 0,
            'subtotal' => 0,
            'markup' => 1,
        ]);
    }

    private function copyUsulanItemsToPenawaran(UsulanPenawaran $usulan, Penawaran $penawaran): void
    {
        $items = $usulan->items()->orderBy('urutan')->get();
        $order = 1;

        foreach ($items as $item) {
            $qty = (float) ($item->qty ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }

            if ($item->tipe === 'bundle' && $item->product_id) {
                $product = Product::with('details')
                    ->where('company_id', $penawaran->company_id)
                    ->find($item->product_id);
                if ($product && $product->details->count()) {
                    $pItem = PenawaranItem::create([
                        'penawaran_id' => $penawaran->id,
                        'product_id' => $product->id,
                        'tipe' => 'bundle',
                        'urutan' => $order++,
                        'judul' => $item->judul ?: $product->nama,
                        'catatan' => $item->catatan,
                        'qty' => $qty,
                        'satuan' => $item->satuan ?: $product->satuan,
                        'subtotal' => 0,
                    ]);

                    $u = 1;
                    foreach ($product->details as $pd) {
                        $qtyD = (float) ($pd->qty ?? 1);
                        $hargaD = (int) ($pd->harga ?? 0);
                        $subD = (int) round($qtyD * $hargaD);

                        PenawaranItemDetail::create([
                            'penawaran_item_id' => $pItem->id,
                            'product_detail_id' => $pd->id,
                            'urutan' => $u++,
                            'nama' => $pd->nama,
                            'spesifikasi' => $pd->spesifikasi,
                            'qty' => $qtyD,
                            'satuan' => $pd->satuan,
                            'harga' => $hargaD,
                            'subtotal' => $subD,
                        ]);
                    }

                    $pItem->subtotal = $this->calcBundleSubtotal($pItem);
                    $pItem->save();

                    continue;
                }
            }

            $harga = (int) ($item->harga ?? 0);
            if ($harga < 0) {
                $harga = 0;
            }

            $subtotal = (int) round($qty * $harga);

            $pItem = PenawaranItem::create([
                'penawaran_id' => $penawaran->id,
                'product_id' => null,
                'tipe' => 'custom',
                'urutan' => $order++,
                'judul' => $item->judul,
                'catatan' => $item->catatan,
                'qty' => $qty,
                'satuan' => $item->satuan,
                'subtotal' => $subtotal,
            ]);

            PenawaranItemDetail::create([
                'penawaran_item_id' => $pItem->id,
                'product_detail_id' => null,
                'urutan' => 1,
                'nama' => $item->judul,
                'spesifikasi' => null,
                'qty' => $qty,
                'satuan' => $item->satuan,
                'harga' => $harga,
                'subtotal' => $subtotal,
            ]);
        }
    }

    private function calcBundleSubtotal(PenawaranItem $item): int
    {
        $item->loadMissing('details');
        $unit = 0;

        foreach ($item->details as $d) {
            $harga = (int) ($d->harga ?? 0);
            if ($harga <= 0) {
                $qty = (float) ($d->qty ?? 1);
                $sub = (int) ($d->subtotal ?? 0);
                if ($sub > 0 && $qty > 0) {
                    $harga = (int) round($sub / $qty);
                }
            }
            $unit += $harga;
        }

        $qtyBundle = (float) ($item->qty ?? 1);
        if ($qtyBundle <= 0) {
            $qtyBundle = 1;
        }

        return (int) round($unit * $qtyBundle);
    }

    private function updateRequestQuotationItem(PenawaranItem $item, array $payload): void
    {
        $unitPrice = max(0, (int) ($payload['unit_price'] ?? 0));
        $item->update([
            'judul' => $payload['judul'],
            'catatan' => $payload['catatan'] ?? null,
            'qty' => (float) $payload['qty'],
            'satuan' => $payload['satuan'] ?? null,
            'markup' => 1,
            'discount_enabled' => false,
            'discount_type' => null,
            'discount_value' => null,
        ]);

        $details = $item->details->values();
        if ($details->isEmpty()) {
            PenawaranItemDetail::create([
                'penawaran_item_id' => $item->id,
                'product_detail_id' => null,
                'urutan' => 1,
                'nama' => $payload['judul'],
                'spesifikasi' => null,
                'qty' => 1,
                'satuan' => $payload['satuan'] ?? null,
                'harga' => $unitPrice,
                'subtotal' => $unitPrice,
                'markup' => 1,
            ]);
        } elseif ($details->count() === 1 && $item->tipe === 'custom') {
            $details->first()->update([
                'nama' => $payload['judul'],
                'qty' => 1,
                'satuan' => $payload['satuan'] ?? null,
                'harga' => $unitPrice,
                'subtotal' => $unitPrice,
                'markup' => 1,
            ]);
        } else {
            $currentTotal = (int) $details->sum(fn ($detail) => max(0, $detail->calcSubtotal()));
            $remaining = $unitPrice;

            foreach ($details as $index => $detail) {
                $isLast = $index === $details->count() - 1;
                if ($isLast) {
                    $allocated = $remaining;
                } elseif ($currentTotal > 0) {
                    $allocated = (int) round($unitPrice * ($detail->calcSubtotal() / $currentTotal));
                    $allocated = min($allocated, $remaining);
                } else {
                    $allocated = $index === 0 ? $unitPrice : 0;
                }

                $remaining -= $allocated;
                $detailQty = max((float) ($detail->qty ?? 1), 0.01);
                $detailMarkup = max((float) ($detail->markup ?? 1), 0.01);
                $detail->update([
                    'harga' => $allocated > 0
                        ? (int) round($allocated / ($detailQty * $detailMarkup))
                        : 0,
                    'subtotal' => $allocated,
                ]);
            }
        }

        $item->unsetRelation('details');
        $item->load('details');
        $item->update(['subtotal' => $item->calcSubtotal()]);
    }

    private function createDocNumber(?int $companyId = null, ?int $userId = null): DocNumber
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;
        $companyId = $companyId ?: $this->currentCompanyId();
        $company = \App\Models\Company::find($companyId);
        $companyCode = strtoupper((string) ($company?->code ?: 'COMP'));
        $userId = $userId ?: auth()->id();

        $romawi = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        $last = DocNumber::where('company_id', $companyId)->orderByDesc('seq')->first();
        $seq = $last ? $last->seq + 1 : 1;

        $userCode = 'SPH'.str_pad((string) $userId, 2, '0', STR_PAD_LEFT);
        $docNo = str_pad($seq, 3, '0', STR_PAD_LEFT)."/{$userCode}/{$companyCode}/{$romawi[$month]}/{$year}";

        return DocNumber::create([
            'company_id' => $companyId,
            'prefix' => $userCode,
            'seq' => $seq,
            'month' => $month,
            'year' => $year,
            'doc_no' => $docNo,
        ]);
    }

    private function cloneTemplateTerm(int $penawaranId, $template, ?int $parentId): void
    {
        $new = PenawaranTerm::create([
            'penawaran_id' => $penawaranId,
            'parent_id' => $parentId,
            'urutan' => (int) ($template->urutan ?? 1),
            'judul' => $template->judul,
            'isi' => $template->isi,
        ]);

        $children = $template->children ?? collect();
        foreach ($children as $c) {
            $this->cloneTemplateTerm($penawaranId, $c, $new->id);
        }
    }

    public function deleteAttachment(UsulanAttachment $attachment)
    {
        $usulan = $attachment->usulan;
        $this->ensureUsulanEditAccess($usulan);

        if ($usulan->status !== 'draft') {
            return back()->with('error', 'Tidak bisa hapus attachment');
        }

        Storage::disk('local')->delete($attachment->path);
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Attachment dihapus');
    }

    public function downloadAttachment(UsulanPenawaran $usulan, UsulanAttachment $attachment)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        abort_unless((int) $attachment->usulan_id === (int) $usulan->id, 404);

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($attachment->path)) {
                return $disk->download($attachment->path, $attachment->nama_file);
            }
        }

        abort(404);
    }

    private function resolveUsulanPublicImagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalizedPath = preg_replace('#^(public|storage)/#', '', ltrim($path, '/\\'));

        foreach ([
            Storage::disk('public')->path($normalizedPath),
            public_path('storage/'.$normalizedPath),
            public_path($normalizedPath),
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Pusatkan massa visual goresan TTD, bukan sekadar kotak file gambarnya.
     * Setiap file bisa memiliki ruang transparan dan bentuk goresan berbeda.
     *
     * @return array{left: float, top: float, width: float, height: float}|null
     */
    private function pdfSignaturePlacement(?string $imagePath): ?array
    {
        if (! $imagePath || ! is_file($imagePath)) {
            return null;
        }

        $imageInfo = @getimagesize($imagePath);
        $sourceWidth = (int) ($imageInfo[0] ?? 0);
        $sourceHeight = (int) ($imageInfo[1] ?? 0);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            return null;
        }

        // Kotak TTD mengikuti dokumen Penawaran Harga biasa (220x100) supaya cap dan
        // TTD pada PDF Permohonan serta Penawaran Harga khusus terlihat sama.
        $containerWidth = 220.0;
        $containerHeight = 100.0;
        // Sasarannya coretan yang terlihat, bukan kanvasnya: gambar TTD hasil potong
        // biasanya punya ruang kosong di sekeliling coretan, dan porsinya berbeda-beda
        // tiap file. Menskala dari ukuran kanvas membuat coretan tampil besar-kecil
        // tak menentu -- itulah yang bikin TTD terlihat kelewat besar pada satu file
        // dan kelewat kecil pada file lain.
        $inkTargetWidth = 100.0;
        $inkTargetHeight = 100.0;

        $ink = $this->signatureInkBounds($imagePath, $sourceWidth, $sourceHeight);

        if ($ink === null) {
            // Gambar tanpa transparansi (mis. hasil pindai berlatar putih) atau tanpa
            // coretan yang terdeteksi: seluruh kanvas adalah isinya, jadi kanvas itu
            // sendiri yang diskala -- persis perilaku penawaran biasa.
            $scale = min($inkTargetWidth / $sourceWidth, $containerHeight / $sourceHeight);
            $displayWidth = $sourceWidth * $scale;
            $displayHeight = $sourceHeight * $scale;

            return [
                'left' => round(max(0, ($containerWidth - $displayWidth) / 2), 2),
                'top' => round(max(0, $containerHeight - $displayHeight), 2),
                'width' => round($displayWidth, 2),
                'height' => round($displayHeight, 2),
            ];
        }

        $scale = min($inkTargetWidth / $ink['width'], $inkTargetHeight / $ink['height']);
        $displayWidth = $sourceWidth * $scale;
        $displayHeight = $sourceHeight * $scale;

        // Coretan diletakkan di tengah kotak secara horizontal dan rapat ke dasarnya,
        // meniru bottom:0 pada penawaran biasa. Ruang kosong kanvas boleh menjulur
        // keluar kotak -- bagian itu transparan, jadi tidak menutupi apa pun.
        $left = ($containerWidth / 2) - (($ink['centerX'] - 0) * $scale);
        $top = $containerHeight - ($ink['bottom'] * $scale);

        return [
            'left' => round($left, 2),
            'top' => round($top, 2),
            'width' => round($displayWidth, 2),
            'height' => round($displayHeight, 2),
        ];
    }

    /**
     * Kotak dan titik berat coretan TTD dalam piksel sumber.
     *
     * Mengembalikan null bila gambarnya tidak punya piksel transparan sama sekali
     * (seluruh kanvas dianggap isi) atau bila tidak ada coretan yang terdeteksi.
     *
     * @return array{width: float, height: float, centerX: float, bottom: float}|null
     */
    private function signatureInkBounds(string $imagePath, int $sourceWidth, int $sourceHeight): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $contents = @file_get_contents($imagePath);
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;
        if ($image === false) {
            return null;
        }

        $stepX = max(1, (int) ceil($sourceWidth / 360));
        $stepY = max(1, (int) ceil($sourceHeight / 360));
        $hasTransparency = false;
        $weightTotal = 0.0;
        $weightedX = 0.0;
        $minX = $maxX = $minY = $maxY = null;

        for ($y = 0; $y < $sourceHeight; $y += $stepY) {
            for ($x = 0; $x < $sourceWidth; $x += $stepX) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $alpha = (int) ($color['alpha'] ?? 0);
                if ($alpha > 8) {
                    $hasTransparency = true;
                }

                $opacity = (127 - $alpha) / 127;
                $luminance = (
                    (0.2126 * (int) $color['red'])
                    + (0.7152 * (int) $color['green'])
                    + (0.0722 * (int) $color['blue'])
                ) / 255;
                $inkWeight = $opacity * max(0, (1 - $luminance) - 0.04);

                if ($inkWeight <= 0) {
                    continue;
                }

                $weightTotal += $inkWeight;
                $weightedX += $x * $inkWeight;
                $minX = $minX === null ? $x : min($minX, $x);
                $maxX = $maxX === null ? $x : max($maxX, $x);
                $minY = $minY === null ? $y : min($minY, $y);
                $maxY = $maxY === null ? $y : max($maxY, $y);
            }
        }

        imagedestroy($image);

        if (! $hasTransparency || $minX === null || $weightTotal <= 0) {
            return null;
        }

        // Pemindaian melompat beberapa piksel, jadi tepi coretan bisa terlewat sebanyak
        // satu langkah; dilebarkan agar coretannya tidak terpotong.
        $minX = max(0, $minX - $stepX);
        $maxX = min($sourceWidth - 1, $maxX + $stepX);
        $minY = max(0, $minY - $stepY);
        $maxY = min($sourceHeight - 1, $maxY + $stepY);

        return [
            'width' => (float) max(1, $maxX - $minX + 1),
            'height' => (float) max(1, $maxY - $minY + 1),
            'centerX' => $weightedX / $weightTotal,
            'bottom' => (float) ($maxY + 1),
        ];
    }

    private function usulanDocumentNumber(UsulanPenawaran $usulan, Carbon $documentDate): string
    {
        $companyCode = strtoupper((string) ($usulan->company?->code ?: 'COMP'));
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return sprintf(
            '%03d/PP-%s/%s/%d',
            $usulan->id,
            $companyCode,
            $romanMonths[(int) $documentDate->month],
            $documentDate->year
        );
    }

    private function usulanCompanyDocumentLogo(?Company $company): ?string
    {
        $uploadedLogo = $company?->logoFullPath();
        if ($uploadedLogo) {
            return $uploadedLogo;
        }

        $fallback = match (strtoupper((string) $company?->code)) {
            'AS', 'ARSOL' => public_path('images/logo_arsol.png'),
            'ATC', 'BE' => public_path('images/logo_be.png'),
            default => null,
        };

        return $fallback && is_file($fallback) ? $fallback : null;
    }

    public function destroy(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if (! in_array($usulan->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Usulan tidak bisa dihapus');
        }

        // Delete attachments
        foreach ($usulan->attachments as $att) {
            Storage::disk('local')->delete($att->path);
            Storage::disk('public')->delete($att->path);
        }

        if ($usulan->signature_path) {
            Storage::disk('public')->delete($usulan->signature_path);
        }

        $usulan->delete();

        return redirect()->route('penawaran-harga.index')->with('success', 'Usulan dihapus');
    }

    public function updateVisibility(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToUsulan($usulan)) {
            return $redirect;
        }

        abort_unless($this->isSuperadmin($request->user()), 403);

        $usulan->sharedCompanies()->sync([]);

        return back()->with('success', 'Usulan otomatis visible ke semua perusahaan.');
    }

    private function ensureUsulanViewAccess(UsulanPenawaran $usulan, $user = null): void
    {
        $user ??= auth()->user();
        $companyId = $this->currentCompanyId($user);

        if (! $this->isSuperadmin($user) && ! $usulan->isVisibleToCompany($companyId)) {
            abort(403);
        }
    }

    private function ensureUsulanEditAccess(UsulanPenawaran $usulan, $user = null): void
    {
        $user ??= auth()->user();

        $this->ensureCompanyAccess($usulan, 'company_id', $user);

        if ($this->isSuperadmin($user) || $user->hasRole('admin') || (int) $usulan->created_by === (int) $user->id) {
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

    private function ensureSupplierAccess(UsulanPenawaran $usulan, $user = null): void
    {
        $user ??= auth()->user();

        if ($this->isSuperadmin($user) || $usulan->isSupplierCompany($this->currentCompanyId($user))) {
            return;
        }

        abort(403);
    }
}
