<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use App\Models\ApprovalStep;
use App\Models\Penawaran;
use App\Models\AlurPenawaran;
use App\Models\PenghapusanPenawaran;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function submitPenawaran($penawaranId)
    {
        $penawaran = Penawaran::findOrFail($penawaranId);
        $alur = AlurPenawaran::where('is_active', true)->with('langkah')->first();

        $approval = Approval::create([
            'module' => 'penawaran',
            'ref_id' => $penawaran->id,
            'status' => 'menunggu',
            'current_step' => 1
        ]);

        foreach ($alur->langkah->sortBy('urutan') as $step) {
            ApprovalStep::create([
                'approval_id' => $approval->id,
                'step_order' => $step->urutan,
                'step_name' => $step->nama_langkah,

                // disesuaikan saja untuk kedepannya
                'akses_approve' => [
                    'user_id' => $step->user_id ?? null,
                ],
            ]);
        }

        $penawaran->update([
            'approval_id' => $approval->id,
            'status' => 'diajukan'
        ]);

        return back();
    }

    public function processStep(Request $request)
    // {
    //     $request->validate([
    //         'approval_id' => ['required', 'exists:approvals,id'],
    //         'aksi' => ['required', 'in:approve,reject'],
    //         'catatan' => ['nullable', 'string'],
    //     ]);

    //     $approval = Approval::with('steps')->findOrFail($request->approval_id);

    //     $langkahAktif = $approval->steps()
    //         ->where('step_order', $approval->current_step)
    //         ->first();

    //     if (!$langkahAktif || $langkahAktif->status !== 'menunggu') {
    //         return back()->with('error', 'Langkah aktif tidak valid.');
    //     }

    //     if ($langkahAktif->user_id && auth()->id() != $langkahAktif->user_id) {
    //         return back()->with('error', 'Anda tidak berhak menyetujui langkah ini.');
    //     }

    //     if ($request->aksi === 'reject') {
    //         $langkahAktif->update([
    //             'status' => 'ditolak',
    //             'approved_by' => auth()->id(),
    //             'approved_at' => now(),
    //             'catatan' => $request->catatan,
    //         ]);

    //         $approval->update([
    //             'status' => 'ditolak',
    //             'approved_by' => auth()->id(),
    //             'approved_at' => now(),
    //         ]);

    //         $this->syncStatusEntitas($approval, 'ditolak');

    //         return back()->with('success', 'Approval ditolak.');
    //     }

    //     // Approve step aktif
    //     $langkahAktif->update([
    //         'status' => 'disetujui',
    //         'approved_by' => auth()->id(),
    //         'approved_at' => now(),
    //         'catatan' => $request->catatan,
    //     ]);

    //     // Cari step berikutnya
    //     $nextStep = $approval->steps()
    //         ->where('step_order', '>', $approval->current_step)
    //         ->orderBy('step_order')
    //         ->first();

    //     if ($nextStep) {
    //         $approval->update([
    //             'current_step' => $nextStep->step_order,
    //             'status' => 'menunggu'
    //         ]);
    //     } else {
    //         $approval->update([
    //             'status' => 'disetujui',
    //             'approved_by' => auth()->id(),
    //             'approved_at' => now(),
    //         ]);

    //         $this->syncStatusEntitas($approval, 'disetujui');
    //     }

    //     return back()->with('success', 'Persetujuan berhasil diproses.');
    // }
    {
        $request->validate([
            'approval_id' => ['required', 'exists:approvals,id'],
            'aksi' => ['required', 'in:approve,reject'],
            'catatan' => ['nullable', 'string'],
        ]);

        $approval = Approval::with('steps')->findOrFail($request->approval_id);

        $stepAktif = $approval->steps()
            ->where('step_order', $approval->current_step)
            ->first();

        if (!$stepAktif || $stepAktif->status !== 'menunggu') {
            return back()->with('error', 'Langkah approval tidak valid.');
        }

        // 🔒 Cek hak akses (kalau pakai user_id per step)
        if ($stepAktif->user_id && auth()->id() != $stepAktif->user_id) {
            return back()->with('error', 'Anda tidak berhak memproses langkah ini.');
        }

        // =========================
        // ❌ JIKA DITOLAK
        // =========================
        if ($request->aksi === 'reject') {

            $stepAktif->update([
                'status' => 'ditolak',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'catatan' => $request->catatan,
            ]);

            $approval->update([
                'status' => 'ditolak',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Sinkron ke penawaran
            $this->syncStatusEntitas($approval, 'ditolak');

            return back()->with('success', 'Penawaran berhasil ditolak.');
        }

        // =========================
        // ✅ JIKA DISETUJUI
        // =========================
        $stepAktif->update([
            'status' => 'disetujui',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'catatan' => $request->catatan,
        ]);

        $nextStep = $approval->steps()
            ->where('step_order', '>', $approval->current_step)
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            $approval->update([
                'current_step' => $nextStep->step_order,
                'status' => 'menunggu'
            ]);
        } else {
            // Semua step selesai
            $approval->update([
                'status' => 'disetujui',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->syncStatusEntitas($approval, 'disetujui');
        }

        return back()->with('success', 'Persetujuan berhasil diproses.');
    }


    private function syncStatusEntitas(Approval $approval, string $status)
    {
        if ($approval->module === 'penawaran' && $approval->ref_id) {
            $penawaran = Penawaran::find($approval->ref_id);
            if ($penawaran) {
                $penawaran->update([
                    'status' => $status
                ]);
            }
        }

        // =========================================
        // APPROVAL PENGHAPUSAN PENAWARAN
        // =========================================
        elseif ($approval->module === 'penghapusan') {

            $penawaran = Penawaran::find($approval->ref_id);
            if (!$penawaran) return;

            // 🔥 SIMPAN KE TABEL PENGHAPUSAN
            PenghapusanPenawaran::create([
                'nomor_penghapusan' => 'DEL-' . str_pad($penawaran->id, 6, '0', STR_PAD_LEFT),
                'tanggal_penghapusan' => now(),
                'metode' => 'hapus',
                'alasan' => 'Penghapusan disetujui melalui approval',
                'dibuat_oleh' => auth()->id(),
                'disetujui_oleh' => auth()->id(),
                'penawaran_id' => $penawaran->id,
                'approval_id' => $approval->id,
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
            ]);

            // Optional: soft delete penawaran
            // $penawaran->delete();

            // if ($status === 'disetujui') {
            //     $penawaran->update(['status' => 'dihapus']);
            // }

            // if ($status === 'ditolak') {
            //     $penawaran->update(['status' => 'aktif']);
            // }
        }

        // =========================================
        // APPROVAL PENAWARAN BIASA
        // =========================================
        if ($approval->module === 'penawaran' && $approval->ref_id) {
            $penawaran = Penawaran::find($approval->ref_id);
            if ($penawaran) {
                $penawaran->update(['status' => $status]);
            }
        }
    }
}
