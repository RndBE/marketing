<?php

namespace App\Http\Controllers;

use App\Models\UsulanPenawaran;
use App\Models\UsulanAttachment;
use App\Models\UsulanItem;
use App\Models\Pic;
use App\Models\Product;
use App\Models\Penawaran;
use App\Models\DocNumber;
use App\Models\PenawaranCover;
use App\Models\PenawaranValidity;
use App\Models\PenawaranTerm;
use App\Models\PenawaranTermTemplate;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\AlurPenawaran;
use App\Models\Approval;
use App\Models\ApprovalStep;
use App\Models\PenawaranSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UsulanPenawaranController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $companyId = $this->currentCompanyId($request->user());

        $usulan = UsulanPenawaran::query()
            // Usulan internal saja. Permintaan yang ditujukan ke perusahaan lain
            // ditangani modul Penawaran Harga.
            ->whereNull('target_company_id')
            ->with(['pic', 'creator', 'penawaran', 'prospect'])
            ->visibleToCompany($companyId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('usulan.index', compact('usulan', 'status'));
    }

    public function create()
    {
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
        return view('usulan.create', compact('pics', 'products', 'bundleProducts'));
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'judul' => 'required|string|max:255',
            'pic_id' => 'nullable|exists:pics,id',
            'deskripsi' => 'nullable|string',
            'nilai_estimasi' => 'nullable|integer|min:0',
            'tanggal_dibutuhkan' => 'nullable|date',
            'status' => 'nullable|in:draft,menunggu',
            'attachments.*' => 'nullable|file|max:10240', // max 10MB
            'attachment_types.*' => 'nullable|string',
            'item_judul.*' => 'nullable|string|max:255',
            'item_catatan.*' => 'nullable|string',
            'item_qty.*' => 'nullable|numeric|min:0.01',
            'item_satuan.*' => 'nullable|string|max:50',
            'item_harga.*' => 'nullable|integer|min:0',
            'item_tipe.*' => 'nullable|in:custom,bundle',
            'item_product_id.*' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($payload, $request) {
            $companyId = (int) $this->currentCompanyId($request->user());

            if (!empty($payload['pic_id'])) {
                Pic::findOrFail($payload['pic_id']);
            }

            $usulan = UsulanPenawaran::create([
                'company_id' => $companyId,
                'judul' => $payload['judul'],
                'pic_id' => $payload['pic_id'] ?? null,
                'deskripsi' => $payload['deskripsi'] ?? null,
                'nilai_estimasi' => $payload['nilai_estimasi'] ?? 0,
                'tanggal_dibutuhkan' => $payload['tanggal_dibutuhkan'] ?? null,
                'created_by' => auth()->id(),
                'status' => $payload['status'] ?? 'draft',
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $types = $request->input('attachment_types', []);
                foreach ($request->file('attachments') as $i => $file) {
                    $path = $file->store('usulan/' . $usulan->id, 'public');
                    UsulanAttachment::create([
                        'usulan_id' => $usulan->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path' => $path,
                        'tipe' => $types[$i] ?? 'dokumen',
                    ]);
                }
            }

            $this->syncItemsFromRequest($usulan, $request);

            return redirect()->route('usulan.show', $usulan->id)->with('success', 'Usulan berhasil dibuat');
        });
    }

    /**
     * Modul ini hanya untuk usulan internal. Data yang punya perusahaan tujuan milik
     * modul Penawaran Harga -- dialihkan, bukan ditolak, supaya tautan dan penanda
     * lama yang menunjuk /usulan/{id} tetap sampai ke halaman yang benar.
     */
    private function redirectIfBelongsToPenawaranHarga(UsulanPenawaran $usulan)
    {
        return $usulan->target_company_id !== null
            ? redirect()->route('penawaran-harga.show', $usulan)
            : null;
    }

    public function show(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToPenawaranHarga($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);
        $companyId = $this->currentCompanyId();
        $usulan->load([
            'pic',
            'company',
            'creator',
            'responder',
            'attachments',
            'items',
            'penawaran.docNumber',
            'penawaran.sharedCompanies',
            'prospect.sharedCompanies',
            'prospect',
        ]);

        $canViewLinkedProspect = $usulan->prospect
            ? ($this->isSuperadmin() || $usulan->prospect->isVisibleToCompany($companyId))
            : false;

        $canViewLinkedPenawaran = $usulan->penawaran
            ? ($this->isSuperadmin() || $usulan->penawaran->isVisibleToCompany($companyId))
            : false;

        return view('usulan.show', compact('usulan', 'canViewLinkedProspect', 'canViewLinkedPenawaran'));
    }

    public function edit(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToPenawaranHarga($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if (!in_array($usulan->status, ['draft', 'menunggu'])) {
            return redirect()->route('usulan.show', $usulan->id)->with('error', 'Usulan tidak bisa diedit');
        }

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
        return view('usulan.edit', compact('usulan', 'pics', 'products', 'bundleProducts'));
    }

    public function update(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToPenawaranHarga($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if (!in_array($usulan->status, ['draft', 'menunggu'])) {
            return redirect()->route('usulan.show', $usulan->id)->with('error', 'Usulan tidak bisa diedit');
        }

        $payload = $request->validate([
            'judul' => 'required|string|max:255',
            'pic_id' => 'nullable|exists:pics,id',
            'deskripsi' => 'nullable|string',
            'nilai_estimasi' => 'nullable|integer|min:0',
            'tanggal_dibutuhkan' => 'nullable|date',
            'status' => 'nullable|in:draft,menunggu',
            'attachments.*' => 'nullable|file|max:10240',
            'attachment_types.*' => 'nullable|string',
            'item_judul.*' => 'nullable|string|max:255',
            'item_catatan.*' => 'nullable|string',
            'item_qty.*' => 'nullable|numeric|min:0.01',
            'item_satuan.*' => 'nullable|string|max:50',
            'item_harga.*' => 'nullable|integer|min:0',
            'item_tipe.*' => 'nullable|in:custom,bundle',
            'item_product_id.*' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($payload, $request, $usulan) {
            if (!empty($payload['pic_id'])) {
                Pic::findOrFail($payload['pic_id']);
            }

            $usulan->update([
                'judul' => $payload['judul'],
                'pic_id' => $payload['pic_id'] ?? null,
                'deskripsi' => $payload['deskripsi'] ?? null,
                'nilai_estimasi' => $payload['nilai_estimasi'] ?? 0,
                'tanggal_dibutuhkan' => $payload['tanggal_dibutuhkan'] ?? null,
                'status' => $payload['status'] ?? 'draft',
            ]);

            // Handle new file uploads
            if ($request->hasFile('attachments')) {
                $types = $request->input('attachment_types', []);
                foreach ($request->file('attachments') as $i => $file) {
                    $path = $file->store('usulan/' . $usulan->id, 'public');
                    UsulanAttachment::create([
                        'usulan_id' => $usulan->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path' => $path,
                        'tipe' => $types[$i] ?? 'dokumen',
                    ]);
                }
            }

            $this->syncItemsFromRequest($usulan, $request);

            return redirect()->route('usulan.show', $usulan->id)->with('success', 'Usulan berhasil diupdate');
        });
    }

    public function tanggapi(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToPenawaranHarga($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanViewAccess($usulan);

        $payload = $request->validate([
            'tanggapan' => 'required|string',
            'status' => 'required|in:ditanggapi,disetujui,ditolak',
            'penawaran_action' => 'nullable|in:none,empty,from_usulan',
        ]);

        $action = $payload['penawaran_action'] ?? 'none';

        if ($action !== 'none' && $payload['status'] !== 'disetujui') {
            return back()->with('error', 'Penawaran hanya bisa dibuat jika status Disetujui.');
        }

        if ($action === 'from_usulan' && !$usulan->items()->exists()) {
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
                return redirect()->route('usulan.show', $usulan->id)->with('success', 'Tanggapan berhasil disimpan');
            }

            if ($usulan->penawaran_id) {
                return redirect()->route('penawaran.show', $usulan->penawaran_id)
                    ->with('success', 'Penawaran sudah tersedia.');
            }

            $copyItems = $action === 'from_usulan';
            $penawaran = $this->createPenawaranFromUsulan($usulan, $copyItems);

            $message = $copyItems
                ? 'Penawaran berhasil dibuat dari item usulan'
                : 'Penawaran berhasil dibuat dari usulan';

            return redirect()->route('penawaran.show', $penawaran->id)->with('success', $message);
        });
    }

    public function deleteAttachment(UsulanAttachment $attachment)
    {
        $usulan = $attachment->usulan;
        $this->ensureUsulanEditAccess($usulan);

        if ($usulan->status !== 'draft') {
            return back()->with('error', 'Tidak bisa hapus attachment');
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Attachment dihapus');
    }

    public function destroy(UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToPenawaranHarga($usulan)) {
            return $redirect;
        }

        $this->ensureUsulanEditAccess($usulan);

        if (!in_array($usulan->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Usulan tidak bisa dihapus');
        }

        // Delete attachments
        foreach ($usulan->attachments as $att) {
            Storage::disk('public')->delete($att->path);
        }

        $usulan->delete();

        return redirect()->route('usulan.index')->with('success', 'Usulan dihapus');
    }

    public function updateVisibility(Request $request, UsulanPenawaran $usulan)
    {
        if ($redirect = $this->redirectIfBelongsToPenawaranHarga($usulan)) {
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

        if (!$this->isSuperadmin($user) && !$usulan->isVisibleToCompany($companyId)) {
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
}
