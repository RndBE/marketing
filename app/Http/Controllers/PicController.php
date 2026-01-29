<?php

namespace App\Http\Controllers;

use App\Models\Pic;
use Illuminate\Http\Request;

class PicController extends Controller
{
    public function index()
    {
        $data = Pic::orderBy('nama')->paginate(15);
        return view('pics.index', compact('data'));
    }

    public function create()
    {
        return view('pics.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'instansi' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
        ]);

        Pic::create($payload);

        return redirect()->route('pics.index')->with('success', 'PIC berhasil ditambahkan');
    }

    public function edit(Pic $pic)
    {
        return view('pics.edit', compact('pic'));
    }

    public function update(Request $request, Pic $pic)
    {
        $payload = $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'instansi' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
        ]);

        $pic->update($payload);

        return redirect()->route('pics.index')->with('success', 'PIC berhasil diperbarui');
    }

    public function destroy(Pic $pic)
    {
        if ($pic->penawaran()->exists()) {
            return back()->withErrors('PIC tidak bisa dihapus karena sudah dipakai di penawaran.');
        }

        $pic->delete();

        return redirect()->route('pics.index')->with('success', 'PIC berhasil dihapus');
    }
}
