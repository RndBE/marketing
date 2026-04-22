<?php

namespace App\Http\Controllers;

use App\Models\LeadReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadReportController extends Controller
{
    /**
     * Dashboard: list all lead reports with date filter.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q'         => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $q        = trim((string) ($filters['q'] ?? ''));
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to'] ?? null;
        $companyId = $this->currentCompanyId();

        $reports = LeadReport::query()
            ->with('uploader:id,name')
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                       ->orWhere('original_filename', 'like', "%{$q}%")
                       ->orWhere('content', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom, fn($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('lead_reports.index', compact('reports', 'q', 'dateFrom', 'dateTo'));
    }

    /**
     * Show a single lead report with rendered markdown.
     */
    public function show(LeadReport $leadReport)
    {
        if (!$this->isSuperadmin()) {
            $this->ensureCompanyAccess($leadReport);
        }

        $leadReport->load('uploader:id,name');
        $renderedContent = Str::markdown($leadReport->content);

        return view('lead_reports.show', compact('leadReport', 'renderedContent'));
    }

    /**
     * Show upload form (superadmin only).
     */
    public function create()
    {
        if (!$this->isSuperadmin()) {
            abort(403, 'Hanya admin yang dapat mengupload report.');
        }

        return view('lead_reports.create');
    }

    /**
     * Handle .md file upload (superadmin only).
     */
    public function store(Request $request)
    {
        if (!$this->isSuperadmin()) {
            abort(403, 'Hanya admin yang dapat mengupload report.');
        }

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'md_file'     => ['required', 'file', 'max:10240'], // max 10MB
            'report_date' => ['nullable', 'date'],
        ]);

        $file = $request->file('md_file');

        // Validate .md extension
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'md') {
            return back()->withErrors(['md_file' => 'File harus berformat .md (Markdown).'])->withInput();
        }

        // Read file content
        $content = file_get_contents($file->getRealPath());

        // Store the original file
        $storedPath = $file->store('lead_reports', 'local');

        LeadReport::create([
            'title'             => $request->input('title'),
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $storedPath,
            'content'           => $content,
            'uploaded_by'       => auth()->id(),
            'company_id'        => $this->currentCompanyId(),
            'report_date'       => $request->input('report_date'),
        ]);

        return redirect()->route('lead-reports.index')
            ->with('success', 'Lead Report berhasil diupload.');
    }

    /**
     * Delete a lead report (superadmin only).
     */
    public function destroy(LeadReport $leadReport)
    {
        if (!$this->isSuperadmin()) {
            abort(403, 'Hanya admin yang dapat menghapus report.');
        }

        // Delete the stored file
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        if ($disk->exists($leadReport->file_path)) {
            $disk->delete($leadReport->file_path);
        }

        $leadReport->delete();

        return redirect()->route('lead-reports.index')
            ->with('success', 'Lead Report berhasil dihapus.');
    }

    /**
     * Download the original .md file.
     */
    public function download(LeadReport $leadReport)
    {
        if (!$this->isSuperadmin()) {
            $this->ensureCompanyAccess($leadReport);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        if (!$disk->exists($leadReport->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return $disk->download($leadReport->file_path, $leadReport->original_filename);
    }
}
