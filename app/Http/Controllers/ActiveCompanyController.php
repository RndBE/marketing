<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class ActiveCompanyController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        if (!$request->user()?->hasRole('admin')) {
            abort(403);
        }

        $company = Company::query()->findOrFail((int) $request->input('company_id'));

        session(['active_company_id' => $company->id]);

        return back()->with('success', 'Perusahaan aktif diubah ke ' . $company->name . '.');
    }
}
