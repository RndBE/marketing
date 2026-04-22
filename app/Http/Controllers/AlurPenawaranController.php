<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlurPenawaran;
use App\Models\User;
use App\Models\LangkahAlurPenawaran;

class AlurPenawaranController extends Controller
{
    public function index()
    {
        $companyId = $this->currentCompanyId();

        $data = AlurPenawaran::with('langkah')
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->get();
        $users = $this->companyUsersQuery()->orderBy('name')->get();
        return view('alurpenawaran.index', compact('data', 'users'));
    }

    public function store(Request $request)
    {
        $companyId = $this->currentCompanyId();

        $alur = AlurPenawaran::create([
            'company_id' => $companyId,
            'nama' => $request->nama,
            'berlaku_untuk' => $request->berlaku_untuk,
            'status' => $request->status,
            'dibuat_oleh' => auth()->id(),
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ]);

        foreach ($request->langkah as $i => $step) {
            $user = $this->ensureUserBelongsToCompany($step['user_id'] ?? null, $companyId);

            LangkahAlurPenawaran::create([
                'alur_penawaran_id' => $alur->id,
                'no_langkah' => $i,
                'nama_langkah' => $step['nama_langkah'],
                'user_id' => $user?->id,
                'harus_semua' => $step['harus_semua'] ?? 0,
                'kondisi' => $step['kondisi'] ?? null,
                'dibuat_pada' => now(),
                'diubah_pada' => now(),
            ]);
        }

        return back()->with('success', 'Alur penawaran berhasil dibuat');
    }

    public function update(Request $request, $id)
    {
        $alur = AlurPenawaran::findOrFail($id);
        $this->ensureCompanyAccess($alur);

        $alur->update([
            'nama' => $request->nama,
            'status' => $request->status,
            'diubah_pada' => now(),
        ]);

        $steps = $request->input('steps', []);
        $incomingIds = collect($steps)->pluck('id')->filter()->values();

        LangkahAlurPenawaran::where('alur_penawaran_id', $alur->id)
            ->when($incomingIds->count() > 0, fn($q) => $q->whereNotIn('id', $incomingIds))
            ->when($incomingIds->count() === 0, fn($q) => $q)
            ->delete();

        foreach ($steps as $i => $step) {
            $user = $this->ensureUserBelongsToCompany($step['user_id'] ?? null, $alur->company_id);

            LangkahAlurPenawaran::updateOrCreate(
                ['id' => $step['id'] ?? null],
                [
                    'alur_penawaran_id' => $alur->id,
                    'no_langkah' => $i + 1,
                    'nama_langkah' => $step['nama_langkah'],
                    'user_id' => $user?->id,
                    'harus_semua' => $step['harus_semua'] ?? 0,
                    'diubah_pada' => now(),
                    'dibuat_pada' => now(),
                ]
            );
        }

        return back()->with('success', 'Alur berhasil diperbarui');
    }

    public function destroy($id)
    {
        $alur = AlurPenawaran::findOrFail($id);
        $this->ensureCompanyAccess($alur);
        $alur->delete();
        return back();
    }
}
