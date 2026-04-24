<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->withCount('users')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $currentCompanyId = $this->currentCompanyId($request->user());

        return view('companies.index', compact('companies', 'q', 'currentCompanyId'));
    }

    public function create()
    {
        return view('companies.create', ['company' => new Company()]);
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);

        if ($request->hasFile('logo')) {
            $payload['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
        }

        if ($request->hasFile('stamp')) {
            $payload['stamp_path'] = $request->file('stamp')->store('companies/stamps', 'public');
        }

        Company::query()->create($payload);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil dibuat.');
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $payload = $this->validatePayload($request, $company);

        if ($request->boolean('remove_logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $payload['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $payload['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
        }

        if ($request->boolean('remove_stamp')) {
            if ($company->stamp_path) {
                Storage::disk('public')->delete($company->stamp_path);
            }
            $payload['stamp_path'] = null;
        }

        if ($request->hasFile('stamp')) {
            if ($company->stamp_path) {
                Storage::disk('public')->delete($company->stamp_path);
            }
            $payload['stamp_path'] = $request->file('stamp')->store('companies/stamps', 'public');
        }

        $company->update($payload);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil diperbarui.');
    }

    public function destroy(Company $company)
    {
        if (Company::query()->count() <= 1) {
            return back()->withErrors(['company' => 'Minimal harus ada satu perusahaan di sistem.']);
        }

        $usage = $this->findUsage($company);
        if ($usage !== []) {
            return back()->withErrors([
                'company' => 'Perusahaan tidak bisa dihapus karena masih dipakai di: ' . implode(', ', $usage) . '.',
            ]);
        }

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        if ($company->stamp_path) {
            Storage::disk('public')->delete($company->stamp_path);
        }

        $company->delete();

        if ((int) session('active_company_id', 0) === (int) $company->id) {
            session()->forget('active_company_id');
        }

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil dihapus.');
    }

    private function validatePayload(Request $request, ?Company $company = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('companies', 'code')->ignore($company?->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'stamp' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_stamp' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));
        $validated['name'] = trim((string) $validated['name']);

        return $validated;
    }

    private function findUsage(Company $company): array
    {
        $tables = [
            'users' => 'user',
            'penawaran' => 'penawaran',
            'invoices' => 'invoice',
            'purchase_orders' => 'purchase order',
            'prospects' => 'prospek',
            'usulan_penawaran' => 'usulan',
            'laporan_perjalanan_marketing' => 'laporan marketing',
            'lead_reports' => 'lead report',
            'products' => 'price list',
            'komponen' => 'komponen',
            'pics' => 'PIC',
            'alur_penawaran' => 'alur penawaran',
            'penawaran_term_templates' => 'template term penawaran',
            'invoice_term_templates' => 'template term invoice',
            'invoice_signature_templates' => 'template tanda tangan invoice',
            'doc_numbers' => 'nomor dokumen',
            'audit_logs' => 'audit log',
        ];

        $usedIn = [];

        foreach ($tables as $table => $label) {
            if (DB::table($table)->where('company_id', $company->id)->exists()) {
                $usedIn[] = $label;
            }
        }

        return $usedIn;
    }
}
