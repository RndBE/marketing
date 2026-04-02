<?php

namespace App\Http\Controllers;

use App\Models\Pic;
use Illuminate\Http\Request;

class PicController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sort = trim((string) $request->query('sort', 'latest'));
        $allowedSorts = ['latest', 'oldest', 'name_asc'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $data = Pic::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('nama', 'like', '%' . $q . '%')
                        ->orWhere('honorific', 'like', '%' . $q . '%')
                        ->orWhere('jabatan', 'like', '%' . $q . '%')
                        ->orWhere('instansi', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%')
                        ->orWhere('no_hp', 'like', '%' . $q . '%')
                        ->orWhere('alamat', 'like', '%' . $q . '%');
                });
            })
            ->when($sort === 'latest', fn($query) => $query->orderByDesc('id'))
            ->when($sort === 'oldest', fn($query) => $query->orderBy('id'))
            ->when($sort === 'name_asc', fn($query) => $query->orderBy('nama'))
            ->paginate(15)
            ->withQueryString();

        if ($request->boolean('_partial')) {
            return view('pics._table', compact('data'));
        }

        return view('pics.index', compact('data', 'q', 'sort'));
    }

    public function create()
    {
        return view('pics.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'honorific' => 'nullable|in:Bapak,Ibu',
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
            'honorific' => 'nullable|in:Bapak,Ibu',
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

        if ($pic->prospects()->exists()) {
            return back()->withErrors('PIC tidak bisa dihapus karena masih dipakai di prospek.');
        }

        $pic->delete();

        return redirect()->route('pics.index')->with('success', 'PIC berhasil dihapus');
    }
}
