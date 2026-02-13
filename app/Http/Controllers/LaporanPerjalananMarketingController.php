<?php

namespace App\Http\Controllers;

use App\Models\LaporanPerjalananMarketing;
use App\Models\LaporanPerjalananMarketingAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LaporanPerjalananMarketingController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $user = $request->user();
        $canViewAll = $user->hasPermission('view-all-marketing-report');

        $reports = LaporanPerjalananMarketing::query()
            ->with(['creator'])
            ->withCount('attachments')
            ->when(!$canViewAll, fn($query) => $query->where('created_by', $user->id))
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('nomor_laporan', 'like', '%' . $q . '%')
                        ->orWhere('tempat_pertemuan', 'like', '%' . $q . '%')
                        ->orWhere('instansi', 'like', '%' . $q . '%')
                        ->orWhere('pihak_ditemui', 'like', '%' . $q . '%')
                        ->orWhere('topik_pembahasan', 'like', '%' . $q . '%');
                });
            })
            ->orderByDesc('tanggal_pertemuan')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('marketing_reports.index', compact('reports', 'q', 'status'));
    }

    public function create()
    {
        return view('marketing_reports.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'tanggal_pertemuan' => ['required', 'date'],
            'waktu_pertemuan' => ['nullable', 'date_format:H:i'],
            'tempat_pertemuan' => ['required', 'string', 'max:255'],
            'instansi' => ['nullable', 'string', 'max:255'],
            'pihak_ditemui' => ['required', 'string'],
            'peserta_internal' => ['nullable', 'string'],
            'topik_pembahasan' => ['required', 'string'],
            'hasil_pertemuan' => ['nullable', 'string'],
            'rencana_tindak_lanjut' => ['nullable', 'string'],
            'target_tindak_lanjut' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,follow_up,selesai'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
        ]);

        $payload['created_by'] = $request->user()->id;
        $payload['updated_by'] = $request->user()->id;

        $report = DB::transaction(function () use ($payload, $request) {
            $report = LaporanPerjalananMarketing::create($payload);

            if (empty($report->nomor_laporan)) {
                $report->nomor_laporan = $this->generateNumber($report);
                $report->save();
            }

            $this->storeUploadedAttachments($request, $report);

            return $report;
        });

        return redirect()->route('marketing-reports.show', $report->id)
            ->with('success', 'Laporan perjalanan marketing berhasil dibuat.');
    }

    public function show(Request $request, LaporanPerjalananMarketing $marketingReport)
    {
        $this->ensureViewAccess($request->user(), $marketingReport);

        $marketingReport->load(['creator', 'updater', 'attachments']);

        return view('marketing_reports.show', ['report' => $marketingReport]);
    }

    public function edit(Request $request, LaporanPerjalananMarketing $marketingReport)
    {
        $this->ensureOwnerAccess($request->user(), $marketingReport);

        $marketingReport->load(['attachments']);

        return view('marketing_reports.edit', ['report' => $marketingReport]);
    }

    public function update(Request $request, LaporanPerjalananMarketing $marketingReport)
    {
        $this->ensureOwnerAccess($request->user(), $marketingReport);

        $payload = $request->validate([
            'tanggal_pertemuan' => ['required', 'date'],
            'waktu_pertemuan' => ['nullable', 'date_format:H:i'],
            'tempat_pertemuan' => ['required', 'string', 'max:255'],
            'instansi' => ['nullable', 'string', 'max:255'],
            'pihak_ditemui' => ['required', 'string'],
            'peserta_internal' => ['nullable', 'string'],
            'topik_pembahasan' => ['required', 'string'],
            'hasil_pertemuan' => ['nullable', 'string'],
            'rencana_tindak_lanjut' => ['nullable', 'string'],
            'target_tindak_lanjut' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,follow_up,selesai'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
            'delete_attachment_ids' => ['nullable', 'array'],
            'delete_attachment_ids.*' => ['integer', 'exists:laporan_perjalanan_marketing_attachments,id'],
        ]);

        $payload['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($request, $marketingReport, $payload) {
            $marketingReport->update($payload);

            $deleteIds = $request->input('delete_attachment_ids', []);
            if (!empty($deleteIds)) {
                $this->deleteAttachmentsByIds($marketingReport, $deleteIds);
            }

            $this->storeUploadedAttachments($request, $marketingReport);
        });

        return redirect()->route('marketing-reports.show', $marketingReport->id)
            ->with('success', 'Laporan perjalanan marketing berhasil diperbarui.');
    }

    public function destroy(Request $request, LaporanPerjalananMarketing $marketingReport)
    {
        if (!$request->user()->hasPermission('delete-marketing-report')) {
            abort(403);
        }

        $this->ensureOwnerAccess($request->user(), $marketingReport);

        DB::transaction(function () use ($marketingReport) {
            $this->deleteAttachmentsByIds($marketingReport, $marketingReport->attachments()->pluck('id')->all());
            $marketingReport->delete();
        });

        return redirect()->route('marketing-reports.index')
            ->with('success', 'Laporan perjalanan marketing berhasil dihapus.');
    }

    public function deleteAttachment(
        Request $request,
        LaporanPerjalananMarketing $marketingReport,
        LaporanPerjalananMarketingAttachment $attachment
    ) {
        $this->ensureOwnerAccess($request->user(), $marketingReport);

        if ((int) $attachment->laporan_id !== (int) $marketingReport->id) {
            abort(404);
        }

        DB::transaction(function () use ($attachment) {
            if (!empty($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        });

        return redirect()->route('marketing-reports.show', $marketingReport->id)
            ->with('success', 'Lampiran berhasil dihapus.');
    }

    private function ensureViewAccess($user, LaporanPerjalananMarketing $report): void
    {
        $canViewAll = $user->hasPermission('view-all-marketing-report');
        if (!$canViewAll && (int) $report->created_by !== (int) $user->id) {
            abort(403);
        }
    }

    private function ensureOwnerAccess($user, LaporanPerjalananMarketing $report): void
    {
        $canViewAll = $user->hasPermission('view-all-marketing-report');
        if (!$canViewAll && (int) $report->created_by !== (int) $user->id) {
            abort(403);
        }
    }

    private function generateNumber(LaporanPerjalananMarketing $report): string
    {
        $date = $report->tanggal_pertemuan?->format('Ymd') ?? now()->format('Ymd');
        return 'LPM-' . $date . '-' . str_pad((string) $report->id, 4, '0', STR_PAD_LEFT);
    }

    private function storeUploadedAttachments(Request $request, LaporanPerjalananMarketing $report): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('marketing-reports/' . $report->id, 'public');

            LaporanPerjalananMarketingAttachment::create([
                'laporan_id' => $report->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }
    }

    private function deleteAttachmentsByIds(LaporanPerjalananMarketing $report, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $attachments = $report->attachments()
            ->whereIn('id', $ids)
            ->get();

        foreach ($attachments as $attachment) {
            if (!empty($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }
    }
}
