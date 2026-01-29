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
        $data = AlurPenawaran::with('langkah')->get();
        $users = User::orderBy('name')->get();
        return view('alurpenawaran.index', compact('data', 'users'));
    }

    public function store(Request $request)
    {
        $alur = AlurPenawaran::create([
            'nama' => $request->nama,
            'berlaku_untuk' => $request->berlaku_untuk,
            'status' => $request->status,
            'dibuat_oleh' => auth()->id(),
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ]);

        foreach ($request->langkah as $i => $step) {
            LangkahAlurPenawaran::create([
                'alur_penawaran_id' => $alur->id,
                'no_langkah' => $i,
                'nama_langkah' => $step['nama_langkah'],
                'user_id' => $step['user_id'] ?? null,
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
            LangkahAlurPenawaran::updateOrCreate(
                ['id' => $step['id'] ?? null],
                [
                    'alur_penawaran_id' => $alur->id,
                    'no_langkah' => $i + 1,
                    'nama_langkah' => $step['nama_langkah'],
                    'user_id' => $step['user_id'] ?? null,
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
        AlurPenawaran::findOrFail($id)->delete();
        return back();
    }
}
