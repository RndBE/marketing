<?php

namespace App\Http\Controllers;

use App\Models\UsulanPenawaran;
use App\Models\UsulanAttachment;
use App\Models\Pic;
use App\Models\Penawaran;
use App\Models\DocNumber;
use App\Models\PenawaranCover;
use App\Models\PenawaranValidity;
use App\Models\PenawaranTerm;
use App\Models\PenawaranTermTemplate;
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
        return view('usulan.create', compact('pics'));
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

            return redirect()->route('usulan.show', $usulan->id)->with('success', 'Usulan berhasil dibuat');
        });
    }

    public function show(UsulanPenawaran $usulan)
    {
        $usulan->load(['pic', 'creator', 'responder', 'attachments', 'penawaran']);
        return view('usulan.show', compact('usulan'));
    }

    public function edit(UsulanPenawaran $usulan)
    {
        if ($usulan->status !== 'draft') {
            return redirect()->route('usulan.show', $usulan->id)->with('error', 'Hanya usulan draft yang bisa diedit');
        }

        $pics = Pic::orderBy('instansi')->get();
        $usulan->load('attachments');
        return view('usulan.edit', compact('usulan', 'pics'));
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

            return redirect()->route('usulan.show', $usulan->id)->with('success', 'Usulan berhasil diupdate');
        });
    }

    public function tanggapi(Request $request, UsulanPenawaran $usulan)
    {
        $payload = $request->validate([
            'tanggapan' => 'required|string',
            'status' => 'required|in:ditanggapi,disetujui,ditolak',
        ]);

        $usulan->update([
            'tanggapan' => $payload['tanggapan'],
            'status' => $payload['status'],
            'ditanggapi_oleh' => auth()->id(),
            'tanggal_ditanggapi' => now(),
        ]);

        return redirect()->route('usulan.show', $usulan->id)->with('success', 'Tanggapan berhasil disimpan');
    }

    public function buatPenawaran(UsulanPenawaran $usulan)
    {
        if ($usulan->penawaran_id) {
            return redirect()->route('penawaran.show', $usulan->penawaran_id);
        }

        return DB::transaction(function () use ($usulan) {
            //
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


            //
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

            // Auto Create Signature
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

            $usulan->update([
                'penawaran_id' => $penawaran->id,
                'status' => 'disetujui',
                'tanggal_ditanggapi' => now(),
                'ditanggapi_oleh' => auth()->id(),
            ]);

            return redirect()->route('penawaran.show', $penawaran->id)->with('success', 'Penawaran berhasil dibuat dari usulan');
        });
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
