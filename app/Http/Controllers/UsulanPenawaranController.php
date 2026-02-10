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

        $usulan = UsulanPenawaran::query()
            ->with(['pic', 'creator', 'penawaran'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('usulan.index', compact('usulan', 'status'));
    }

    public function create()
    {
        $pics = Pic::orderBy('instansi')->get();
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
            $usulan = UsulanPenawaran::create([
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

    public function show(UsulanPenawaran $usulan)
    {
        $usulan->load(['pic', 'creator', 'responder', 'attachments', 'items', 'penawaran']);
        return view('usulan.show', compact('usulan'));
    }

    public function edit(UsulanPenawaran $usulan)
    {
        if ($usulan->status !== 'draft') {
            return redirect()->route('usulan.show', $usulan->id)->with('error', 'Hanya usulan draft yang bisa diedit');
        }

        $pics = Pic::orderBy('instansi')->get();
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
        if ($usulan->status !== 'draft') {
            return redirect()->route('usulan.show', $usulan->id)->with('error', 'Hanya usulan draft yang bisa diedit');
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

    public function buatPenawaran(Request $request, UsulanPenawaran $usulan)
    {
        if ($usulan->penawaran_id) {
            return redirect()->route('penawaran.show', $usulan->penawaran_id);
        }

        $copyItems = (bool) $request->input('copy_items', false);

        if ($copyItems && !$usulan->items()->exists()) {
            return back()->with('error', 'Item usulan masih kosong. Tambahkan item terlebih dahulu.');
        }

        return DB::transaction(function () use ($usulan, $copyItems) {
            $penawaran = $this->createPenawaranFromUsulan($usulan, $copyItems);
            $message = $copyItems
                ? 'Penawaran berhasil dibuat dari item usulan'
                : 'Penawaran berhasil dibuat dari usulan';

            return redirect()->route('penawaran.show', $penawaran->id)->with('success', $message);
        });
    }

    private function syncItemsFromRequest(UsulanPenawaran $usulan, Request $request): void
    {
        if (!$request->has('items_present')) {
            return;
        }

        $judul = $request->input('item_judul', []);
        $catatan = $request->input('item_catatan', []);
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

            UsulanItem::create([
                'usulan_id' => $usulan->id,
                'product_id' => isset($productId[$i]) && is_numeric($productId[$i]) ? (int) $productId[$i] : null,
                'tipe' => ($tipe[$i] ?? 'custom') === 'bundle' ? 'bundle' : 'custom',
                'urutan' => $order++,
                'judul' => $name,
                'catatan' => $catatan[$i] ?? null,
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

        $docNumber = $this->createDocNumber();

        $penawaran = Penawaran::create([
            'id_pic' => $usulan->pic_id,
            'id_user' => auth()->id(),
            'doc_number_id' => $docNumber->id,
            'approval_id' => null,
            'judul' => $usulan->judul,
            'catatan' => $usulan->deskripsi,
            'instansi_tujuan' => $usulan->pic?->instansi,
            'date_created' => now()->timestamp,
            'date_updated' => now()->timestamp,
            'status' => 'draft',
        ]);

        PenawaranCover::create([
            'penawaran_id' => $penawaran->id,
            'judul_cover' => 'Dokumen Penawaran',
            'subjudul' => $penawaran->judul,
            'perusahaan_nama' => config('app.name', 'CV Arta Solusindo'),
        ]);

        PenawaranValidity::create([
            'penawaran_id' => $penawaran->id,
            'mulai' => now()->toDateString(),
            'sampai' => now()->addDays(30)->toDateString(),
            'berlaku_hari' => 30,
            'keterangan' => 'Penawaran berlaku 30 hari.'
        ]);

        $templates = PenawaranTermTemplate::query()
            ->whereNull('parent_id')
            ->orderBy('urutan')
            ->orderBy('id')
            ->with(['children'])
            ->get();

        foreach ($templates as $t) {
            $this->cloneTemplateTerm($penawaran->id, $t, null);
        }

        $alur = AlurPenawaran::where('berlaku_untuk', 'penawaran')
            ->where('status', 'aktif')
            ->with(['langkah' => fn($q) => $q->orderBy('no_langkah')])
            ->first();

        if ($alur && $alur->langkah->isNotEmpty()) {
            $firstStep = $alur->langkah->first()->no_langkah;

            $approval = Approval::create([
                'status' => 'menunggu',
                'current_step' => $firstStep,
                'module' => 'penawaran',
                'ref_id' => $penawaran->id
            ]);

            foreach ($alur->langkah as $step) {
                $approverId = $step->user_id ?? $penawaran->id_user;
                ApprovalStep::create([
                    'approval_id' => $approval->id,
                    'step_order' => $step->no_langkah,
                    'step_name' => $step->nama_langkah,
                    'user_id' => $step->user_id,
                    'harus_semua' => $step->harus_semua,
                    'status' => 'menunggu',
                    'akses_approve' => [
                        'user_id' => (int) $approverId,
                        'ref_penawaran' => (int) $penawaran->id
                    ],
                ]);
            }

            $penawaran->update([
                'approval_id' => $approval->id,
            ]);
        }

        if ($copyItems) {
            $this->copyUsulanItemsToPenawaran($usulan, $penawaran);
        }

        $user = auth()->user();
        $roleNames = $user->roles->pluck('name')->implode(', ');

        PenawaranSignature::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => 1,
            'nama' => $user->name,
            'jabatan' => $roleNames ?: 'Staff',
            'kota' => 'Sleman',
            'tanggal' => now()->toDateString(),
            'ttd_path' => $user->ttd,
        ]);

        $update = [
            'penawaran_id' => $penawaran->id,
        ];

        if ($usulan->status !== 'disetujui') {
            $update['status'] = 'disetujui';
        }

        if (!$usulan->tanggal_ditanggapi) {
            $update['tanggal_ditanggapi'] = now();
        }

        if (!$usulan->ditanggapi_oleh) {
            $update['ditanggapi_oleh'] = auth()->id();
        }

        $usulan->update($update);

        return $penawaran;
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
                $product = Product::with('details')->find($item->product_id);
                if ($product && $product->details->count()) {
                    $pItem = PenawaranItem::create([
                        'penawaran_id' => $penawaran->id,
                        'product_id' => $product->id,
                        'tipe' => 'bundle',
                        'urutan' => $order++,
                        'judul' => $item->judul ?: $product->nama,
                        'catatan' => null,
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
                'catatan' => null,
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

    private function createDocNumber(): DocNumber
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;

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
            12 => 'XII'
        ];

        $last = DocNumber::orderByDesc('seq')->first();
        $seq = $last ? $last->seq + 1 : 1;

        $userCode = 'SPH' . str_pad(auth()->id(), 2, '0', STR_PAD_LEFT);
        $docNo = str_pad($seq, 3, '0', STR_PAD_LEFT) . "/{$userCode}/AS/{$romawi[$month]}/{$year}";

        return DocNumber::create([
            'prefix' => $userCode,
            'seq' => $seq,
            'month' => $month,
            'year' => $year,
            'doc_no' => $docNo
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

        if ($usulan->status !== 'draft') {
            return back()->with('error', 'Tidak bisa hapus attachment');
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Attachment dihapus');
    }

    public function destroy(UsulanPenawaran $usulan)
    {
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
}
