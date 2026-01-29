@extends('layouts.app', ['title' => 'Data PIC'])

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-lg font-semibold">Daftar PIC</h1>
            <a href="{{ route('pics.create') }}"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Tambah PIC
            </a>
        </div>

        <div class="overflow-x-auto">
            <div class="rounded-xl overflow-hidden border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2 text-left">Nama</th>
                            <th class="px-3 py-2 text-left">Jabatan</th>
                            <th class="px-3 py-2 text-left">Instansi</th>
                            <th class="px-3 py-2 text-left">Email</th>
                            <th class="px-3 py-2 text-left">HP</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class=" divide-y divide-slate-100">
                        @foreach ($data as $pic)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $pic->nama }}</td>
                                <td class="px-3 py-2">{{ $pic->jabatan }}</td>
                                <td class="px-3 py-2">{{ $pic->instansi }}</td>
                                <td class="px-3 py-2">{{ $pic->email }}</td>
                                <td class="px-3 py-2">{{ $pic->no_hp }}</td>
                                <td class="px-3 py-2 text-right space-x-2">
                                    <a href="{{ route('pics.edit', $pic->id) }}"
                                        class="border border-green-600 px-3 py-1 text-green-600 rounded-xl text-sm">Edit</a>

                                    <form action="{{ route('pics.destroy', $pic->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Hapus PIC ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="border border-red-600 px-3 py-1 text-red-600 rounded-xl text-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
