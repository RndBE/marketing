<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSignatureTemplate;
use App\Models\InvoiceTermTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->currentCompanyId($request->user());

        $signatures = InvoiceSignatureTemplate::query()
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->latest()
            ->get();
        $terms = InvoiceTermTemplate::query()
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->latest()
            ->get();
        return view('templates.index', compact('signatures', 'terms'));
    }

    public function storeSignature(Request $request)
    {
        $payload = $request->validate([
            'template_name' => 'required|string|max:255',
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'kota' => 'nullable|string|max:255',
            'ttd' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('ttd')) {
            $payload['ttd_path'] = $request->file('ttd')->store('templates/signatures', 'public');
        }

        InvoiceSignatureTemplate::create(array_merge($payload, [
            'company_id' => $this->currentCompanyId($request->user()),
        ]));

        return back()->with('success', 'Template Tanda Tangan berhasil dibuat.');
    }

    public function deleteSignature(InvoiceSignatureTemplate $template)
    {
        $this->ensureCompanyAccess($template);

        if ($template->ttd_path) {
            Storage::disk('public')->delete($template->ttd_path);
        }
        $template->delete();
        return back()->with('success', 'Template dihapus.');
    }

    public function storeTerm(Request $request)
    {
        $payload = $request->validate([
            'template_name' => 'required|string|max:255',
            'terms' => 'required|array',
            'terms.*' => 'required|string',
        ]);

        InvoiceTermTemplate::create(array_merge($payload, [
            'company_id' => $this->currentCompanyId($request->user()),
        ]));

        return back()->with('success', 'Template Terms berhasil dibuat.');
    }

    public function deleteTerm(InvoiceTermTemplate $template)
    {
        $this->ensureCompanyAccess($template);
        $template->delete();
        return back()->with('success', 'Template dihapus.');
    }
}
