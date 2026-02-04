<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSignatureTemplate;
use App\Models\InvoiceTermTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index()
    {
        $signatures = InvoiceSignatureTemplate::latest()->get();
        $terms = InvoiceTermTemplate::latest()->get();
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

        InvoiceSignatureTemplate::create($payload);

        return back()->with('success', 'Template Tanda Tangan berhasil dibuat.');
    }

    public function deleteSignature(InvoiceSignatureTemplate $template)
    {
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

        InvoiceTermTemplate::create($payload);

        return back()->with('success', 'Template Terms berhasil dibuat.');
    }

    public function deleteTerm(InvoiceTermTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template dihapus.');
    }
}
