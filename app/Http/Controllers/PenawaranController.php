<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\DocNumber;
use App\Models\Penawaran;
use App\Models\PenawaranAttachment;
use App\Models\PenawaranCover;
use App\Models\PenawaranItem;
use App\Models\PenawaranItemDetail;
use App\Models\PenawaranSignature;
use App\Models\PenawaranTerm;
use App\Models\PenawaranTermTemplate;
use App\Models\PenawaranValidity;
use App\Models\Pic;
use App\Models\Product;
use App\Models\ProductDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PenawaranController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $data = Penawaran::query()
            ->with(['docNumber'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('judul', 'like', "%{$q}%")
                    ->orWhere('instansi', 'like', "%{$q}%")
                    ->orWhereHas('docNumber', fn($qq) => $qq->where('doc_no', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('penawaran.index', compact('data', 'q'));
    }

    public function create()
    {
        $pics = Pic::orderBy('nama')->get(['id', 'nama', 'instansi']);
        return view('penawaran.create', compact('pics'));
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'judul' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'id_pic' => ['nullable', 'exists:pics,id'],
        ]);

        return DB::transaction(function () use ($payload) {
            $docNumber = $this->createDocNumber(auth()->id());

            $penawaran = Penawaran::create([
                'id_pic' => $payload['id_pic'] ?? null,
                'id_user' => auth()->id() ?? 1,
                'doc_number_id' => $docNumber->id,
                'approval_id' => null,
                'date_created' => now()->timestamp,
                'date_updated' => now()->timestamp,
                'judul' => $payload['judul'] ?? null,
                'catatan' => $payload['catatan'] ?? null
            ]);

            PenawaranCover::create([
                'penawaran_id' => $penawaran->id,
                'judul_cover' => 'Dokumen Penawaran',
                'subjudul' => $penawaran->judul,
                'perusahaan_nama' => config('app.name')
            ]);

            PenawaranValidity::create([
                'penawaran_id' => $penawaran->id,
                'mulai' => now()->toDateString(),
                'sampai' => now()->addDays(30)->toDateString(),
                'berlaku_hari' => 30,
                'keterangan' => 'Penawaran berlaku 30 hari.'
            ]);

            DB::transaction(function () use ($penawaran) {
                $templates = PenawaranTermTemplate::query()
                    ->whereNull('parent_id')
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->with(['children'])
                    ->get();
                foreach ($templates as $t) {
                    $this->cloneTemplateTerm($penawaran->id, $t, null);
                }
            });

            return redirect()->route('penawaran.show', $penawaran->id);
        });
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

    public function show(Penawaran $penawaran)
    {
        $penawaran->load([
            'docNumber',
            'cover',
            'validity',
            'terms' => function ($q) {
                $q->orderByRaw('COALESCE(parent_id, 0), urutan, id');
            },
            'signatures',
            'attachments',
            'items.details'
        ]);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        return view('penawaran.show', compact('penawaran', 'products'));
    }


    public function edit(Penawaran $penawaran)
    {
        $penawaran->load(['cover', 'validity']);
        $pics = Pic::orderBy('nama')->get();
        return view('penawaran.edit', compact('penawaran', 'pics'));
    }

    public function update(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'judul' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'id_pic' => ['nullable', 'exists:pics,id']
        ]);

        $penawaran->update([
            'judul' => $payload['judul'] ?? null,
            'id_pic' => $payload['id_pic'] ?? null,
            'catatan' => $payload['catatan'] ?? null,
            'date_updated' => now()->timestamp
        ]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function destroy(Penawaran $penawaran)
    {
        $penawaran->delete();
        return redirect()->route('penawaran.index');
    }

    public function upsertCover(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'judul_cover' => ['nullable', 'string', 'max:255'],
            'subjudul' => ['nullable', 'string', 'max:255'],
            'perusahaan_nama' => ['nullable', 'string', 'max:255'],
            'perusahaan_alamat' => ['nullable', 'string'],
            'perusahaan_email' => ['nullable', 'string', 'max:255'],
            'perusahaan_telp' => ['nullable', 'string', 'max:100'],
            'intro_text' => ['nullable', 'string'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048']
        ]);

        return DB::transaction(function () use ($payload, $penawaran, $request) {
            $data = $payload;

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('penawaran/logo', 'public');
                $data['logo_path'] = $path;
            }

            PenawaranCover::updateOrCreate(
                ['penawaran_id' => $penawaran->id],
                $data
            );

            $penawaran->update(['date_updated' => now()->timestamp]);

            return redirect()->route('penawaran.show', $penawaran->id);
        });
    }

    public function upsertValidity(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'mulai' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'berlaku_hari' => ['nullable', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string']
        ]);

        PenawaranValidity::updateOrCreate(
            ['penawaran_id' => $penawaran->id],
            $payload
        );

        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }



    public function addBundle(Request $request, Penawaran $penawaran)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'numeric', 'min:0.01'],
            'judul' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);
        $product = Product::with('details')->findOrFail($request->product_id);

        $item = PenawaranItem::create([
            'penawaran_id' => $penawaran->id,
            'product_id' => $product->id,
            'tipe' => 'bundle',
            'judul' => $request->judul ?: ($product->nama ?? 'Bundle'),
            'catatan' => $request->catatan,
            'qty' => (float) ($request->qty ?: 1),
            'subtotal' => 0,
        ]);

        $urutan = 1;
        foreach ($product->details as $pd) {
            $qtyD = (float) ($pd->qty ?? 1);
            $hargaD = (int) ($pd->harga ?? 0);
            $subD = (int) round($qtyD * $hargaD);

            PenawaranItemDetail::create([
                'penawaran_item_id' => $item->id,
                'urutan' => $urutan++,
                'nama' => $pd->nama,
                'spesifikasi' => $pd->spesifikasi,
                'qty' => $qtyD,
                'satuan' => $pd->satuan,
                'harga' => $hargaD,
                'subtotal' => $subD,
            ]);
        }

        $this->recalcItemSubtotal($item);

        return back()->with('success', 'Bundle ditambahkan.');
    }

    public function addCustomItem(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string']
        ]);

        $urutan = (int) PenawaranItem::where('penawaran_id', $penawaran->id)->max('urutan');
        $urutan = $urutan > 0 ? $urutan + 1 : 1;

        PenawaranItem::create([
            'penawaran_id' => $penawaran->id,
            'tipe' => 'custom',
            'product_id' => null,
            'urutan' => $urutan,
            'judul' => $payload['judul'],
            'catatan' => $payload['catatan'] ?? null,
            'subtotal' => 0
        ]);

        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function deleteItem(Penawaran $penawaran, PenawaranItem $item)
    {
        if ((int) $item->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }

        $item->delete();
        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function addItemDetail(Request $request, Penawaran $penawaran, PenawaranItem $item)
    {
        if ((int) $item->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }

        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0']
        ]);

        return DB::transaction(function () use ($payload, $penawaran, $item) {
            $urutan = (int) PenawaranItemDetail::where('penawaran_item_id', $item->id)->max('urutan');
            $urutan = $urutan > 0 ? $urutan + 1 : 1;

            $qty = (float) $payload['qty'];
            $harga = (int) $payload['harga'];
            $subtotal = (int) round($qty * $harga);

            PenawaranItemDetail::create([
                'penawaran_item_id' => $item->id,
                'product_detail_id' => null,
                'urutan' => $urutan,
                'nama' => $payload['nama'],
                'spesifikasi' => $payload['spesifikasi'] ?? null,
                'qty' => $qty,
                'satuan' => $payload['satuan'] ?? null,
                'harga' => $harga,
                'subtotal' => $subtotal
            ]);

            $this->recalcItemSubtotal($item);
            $penawaran->update(['date_updated' => now()->timestamp]);

            return redirect()->route('penawaran.show', $penawaran->id);
        });
    }

    public function updateItemDetail(Request $request, Penawaran $penawaran, PenawaranItem $item, PenawaranItemDetail $detail)
    {
        if ((int) $item->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }
        if ((int) $detail->penawaran_item_id !== (int) $item->id) {
            abort(404);
        }

        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'spesifikasi' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0']
        ]);

        return DB::transaction(function () use ($payload, $penawaran, $item, $detail) {
            $qty = (float) $payload['qty'];
            $harga = (int) $payload['harga'];
            $subtotal = (int) round($qty * $harga);

            $detail->update([
                'nama' => $payload['nama'],
                'spesifikasi' => $payload['spesifikasi'] ?? null,
                'qty' => $qty,
                'satuan' => $payload['satuan'] ?? null,
                'harga' => $harga,
                'subtotal' => $subtotal
            ]);

            $this->recalcItemSubtotal($item);
            $penawaran->update(['date_updated' => now()->timestamp]);

            return redirect()->route('penawaran.show', $penawaran->id);
        });
    }

    public function deleteItemDetail(Penawaran $penawaran, PenawaranItem $item, PenawaranItemDetail $detail)
    {
        if ((int) $item->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }
        if ((int) $detail->penawaran_item_id !== (int) $item->id) {
            abort(404);
        }

        return DB::transaction(function () use ($penawaran, $item, $detail) {
            $detail->delete();
            $this->recalcItemSubtotal($item);
            $penawaran->update(['date_updated' => now()->timestamp]);
            return redirect()->route('penawaran.show', $penawaran->id);
        });
    }

    public function addTerm(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'judul' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = $payload['parent_id'] ?? null;

        if ($parentId) {
            $parent = PenawaranTerm::query()
                ->where('id', $parentId)
                ->where('penawaran_id', $penawaran->id)
                ->firstOrFail();

            $parentId = (int) $parent->id;
        }

        $q = PenawaranTerm::query()->where('penawaran_id', $penawaran->id);

        if ($parentId) {
            $q->where('parent_id', $parentId);
        } else {
            $q->whereNull('parent_id');
        }

        $urutan = (int) $q->max('urutan');
        $urutan = $urutan > 0 ? $urutan + 1 : 1;

        PenawaranTerm::create([
            'penawaran_id' => $penawaran->id,
            'parent_id' => $parentId,
            'judul' => $payload['judul'] ?? null,
            'isi' => $payload['isi'],
            'urutan' => $urutan,
        ]);

        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }


    public function deleteTerm(Penawaran $penawaran, PenawaranTerm $term)
    {
        if ((int) $term->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }

        $term->delete();
        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function addSignature(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'kota' => ['nullable', 'string', 'max:120'],
            'tanggal' => ['nullable', 'date'],
            'ttd' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048']
        ]);

        $urutan = (int) PenawaranSignature::where('penawaran_id', $penawaran->id)->max('urutan');
        $urutan = $urutan > 0 ? $urutan + 1 : 1;

        $data = [
            'penawaran_id' => $penawaran->id,
            'urutan' => $urutan,
            'nama' => $payload['nama'],
            'jabatan' => $payload['jabatan'] ?? null,
            'kota' => $payload['kota'] ?? null,
            'tanggal' => $payload['tanggal'] ?? null
        ];

        if ($request->hasFile('ttd')) {
            $path = $request->file('ttd')->store('penawaran/ttd', 'public');
            $data['ttd_path'] = $path;
        }

        PenawaranSignature::create($data);
        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function deleteSignature(Penawaran $penawaran, PenawaranSignature $signature)
    {
        if ((int) $signature->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }

        if ($signature->ttd_path) {
            Storage::disk('public')->delete($signature->ttd_path);
        }

        $signature->delete();
        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function addAttachment(Request $request, Penawaran $penawaran)
    {
        $payload = $request->validate([
            'judul' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240']
        ]);

        $urutan = (int) PenawaranAttachment::where('penawaran_id', $penawaran->id)->max('urutan');
        $urutan = $urutan > 0 ? $urutan + 1 : 1;

        $file = $request->file('file');
        $path = $file->store('penawaran/lampiran', 'public');

        PenawaranAttachment::create([
            'penawaran_id' => $penawaran->id,
            'urutan' => $urutan,
            'judul' => $payload['judul'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize()
        ]);

        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function deleteAttachment(Penawaran $penawaran, PenawaranAttachment $attachment)
    {
        if ((int) $attachment->penawaran_id !== (int) $penawaran->id) {
            abort(404);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        $penawaran->update(['date_updated' => now()->timestamp]);

        return redirect()->route('penawaran.show', $penawaran->id);
    }

    public function downloadPdf(Penawaran $penawaran)
    {
        $penawaran->load([
            'docNumber',
            'cover',
            'validity',
            'terms' => function ($q) {
                $q->orderBy('parent_id')
                    ->orderBy('urutan')
                    ->orderBy('id');
            },
            'signatures',
            'attachments',
            'items.details'
        ]);
        $docNo = $penawaran->docNumber?->doc_no ?? ('PNW-' . str_pad((string) $penawaran->id, 6, '0', STR_PAD_LEFT));

        $total = 0;
        foreach ($penawaran->items as $it) {
            $total += (int) $it->subtotal;
        }

        $cover = $penawaran->cover;

        $logo = null;
        if ($cover?->logo_path) {
            $p1 = public_path('storage/' . ltrim($cover->logo_path, '/'));
            $p2 = storage_path('app/public/' . ltrim($cover->logo_path, '/'));
            $logo = is_file($p1) ? $p1 : (is_file($p2) ? $p2 : null);
        }
        if (!$logo) {
            $logo = public_path('images/logo_arsol.png');
        }

        $kop = [
            'logo' => $logo,
            'nama' => 'CV. ARTA SOLUSINDO',
            'alamat' => $cover?->perusahaan_alamat ?? 'Juwangen RT 10 RW 02 Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta 55571',
            'telp' => $cover?->perusahaan_telp ?? '(0274) 5044026 / 085727868505',
            'email' => $cover?->perusahaan_email ?? 'cv.artasolusindo@gmail.com',
        ];

        $pdf = Pdf::loadView('documents.penawaran_full', [
            'penawaran' => $penawaran,
            'docNo' => $docNo,
            'total' => $total,
            'kop' => $kop
        ])->setPaper('a4', 'portrait');
        return $pdf->download($penawaran->judul . '.pdf');
    }

    private function toRoman(int $month): string
    {
        return [
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
        ][$month];
    }




    private function createDocNumber(): DocNumber
    {
        return DB::transaction(function () {

            $now = Carbon::now();
            $month = $now->month;
            $year  = $now->year;

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


            $last = DocNumber::orderByDesc('seq')
                ->first();
            $seq = $last ? $last->seq + 1 : 1;

            $userCode = 'SPH' . str_pad(auth()->id(), 2, '0', STR_PAD_LEFT);

            $docNo = str_pad($seq, 3, '0', STR_PAD_LEFT)
                . "/{$userCode}/AS/{$romawi[$month]}/{$year}";

            return DocNumber::create([
                'prefix' => $userCode,
                'seq'    => $seq,
                'month'  => $month,
                'year'   => $year,
                'doc_no' => $docNo
            ]);
        });
    }

    public function upsertKeterangan(Request $request, Penawaran $penawaran)
    {
        $data = $request->validate([
            'instansi_tujuan' => 'nullable|string|max:255',
            'nama_pekerjaan' => 'nullable|string|max:255',
            'lokasi_pekerjaan' => 'nullable|string|max:255',
            'tanggal_penawaran' => 'nullable|date',
        ]);

        $penawaran->update($data);

        return back()->with('success', 'Keterangan penawaran disimpan.');
    }

    private function calcUnitPriceBundle($item): int
    {
        $unit = 0;

        foreach ($item->details as $d) {
            $harga = (int) ($d->harga ?? 0);
            if ($harga <= 0) {
                $qty = (float) ($d->qty ?? 1);
                $sub = (int) ($d->subtotal ?? 0);
                if ($sub > 0 && $qty > 0) $harga = (int) round($sub / $qty);
            }
            $unit += $harga;
        }

        return $unit;
    }

    private function recalcItemSubtotal($item): void
    {
        $item->loadMissing('details');

        $qtyBundle = (float) ($item->qty ?? 1);
        if ($qtyBundle <= 0) $qtyBundle = 1;

        if ($item->tipe === 'bundle') {
            $unit = $this->calcUnitPriceBundle($item);
            $item->subtotal = (int) round($unit * $qtyBundle);
        } else {
            $total = 0;
            foreach ($item->details as $d) {
                $sub = (int) ($d->subtotal ?? 0);
                if ($sub <= 0) {
                    $q = (float) ($d->qty ?? 0);
                    $h = (int) ($d->harga ?? 0);
                    $sub = (int) round($q * $h);
                }
                $total += $sub;
            }
            $item->subtotal = $total > 0 ? $total : (int) ($item->subtotal ?? 0);
        }

        $item->save();
    }

    public function upsertPricing(Request $request, Penawaran $penawaran)
    {
        $data = $request->validate([
            'discount_enabled' => 'nullable|boolean',
            'discount_type' => 'nullable|string|in:percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_enabled' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $discountEnabled = (bool) ($request->input('discount_enabled') == 1);
        $taxEnabled = (bool) ($request->input('tax_enabled') == 1);

        $penawaran->discount_enabled = $discountEnabled;

        if ($discountEnabled) {
            $penawaran->discount_type = $data['discount_type'] ?? 'percent';
            $penawaran->discount_value = $data['discount_value'] ?? 0;
        } else {
            $penawaran->discount_type = null;
            $penawaran->discount_value = null;
        }

        $penawaran->tax_enabled = $taxEnabled;

        if ($taxEnabled) {
            $penawaran->tax_rate = $data['tax_rate'] ?? 11;
        } else {
            $penawaran->tax_rate = null;
        }

        $penawaran->save();

        return back()->with('success', 'Diskon & pajak tersimpan');
    }
}
