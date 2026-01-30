<?php

namespace App\Http\Controllers;

use App\Models\Komponen;
use Illuminate\Http\Request;

class KomponenController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $komponen = Komponen::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            })
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return view('komponen.index', compact('komponen', 'q'));
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'kode' => 'nullable|string|max:50|unique:komponen,kode',
            'nama' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'satuan' => 'nullable|string|max:50',
            'harga' => 'required|integer|min:0',
        ]);

        Komponen::create($payload);

        return redirect()->route('komponen.index')->with('success', 'Komponen berhasil ditambahkan');
    }

    public function update(Request $request, Komponen $komponen)
    {
        $payload = $request->validate([
            'kode' => 'nullable|string|max:50|unique:komponen,kode,' . $komponen->id,
            'nama' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'satuan' => 'nullable|string|max:50',
            'harga' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);

        $komponen->update($payload);

        return redirect()->route('komponen.index')->with('success', 'Komponen berhasil diupdate');
    }

    public function destroy(Komponen $komponen)
    {
        $komponen->delete();
        return redirect()->route('komponen.index')->with('success', 'Komponen berhasil dihapus');
    }

    // API endpoint untuk get komponen data
    public function show(Komponen $komponen)
    {
        return response()->json([
            'id' => $komponen->id,
            'kode' => $komponen->kode,
            'nama' => $komponen->nama,
            'spesifikasi' => $komponen->spesifikasi,
            'satuan' => $komponen->satuan,
            'harga' => $komponen->harga,
        ]);
    }

    // API endpoint untuk list komponen aktif
    public function list()
    {
        $komponen = Komponen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'spesifikasi', 'satuan', 'harga']);

        return response()->json($komponen);
    }
}
