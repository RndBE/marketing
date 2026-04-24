@extends('layouts.app', ['title' => 'Perusahaan'])

@section('content')
    <div class="w-full">
        <div class="flex items-center justify-between mb-3 gap-3">
            <div>
                <h1 class="text-xl font-bold">Manajemen Perusahaan</h1>
                <p class="text-sm text-slate-500">Data perusahaan ini dipakai untuk kop PDF, logo, cap, dan konteks multi-company.</p>
            </div>
            <a href="{{ route('companies.create') }}"
                class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-800">
                Tambah Perusahaan
            </a>
        </div>

        <div class="mb-4">
            <form action="{{ route('companies.index') }}" method="GET">
                <input type="text" name="q" value="{{ $q }}"
                    class="w-full md:w-72 rounded-xl border border-slate-200 px-3 py-2 text-sm"
                    placeholder="Cari kode / nama / email / telepon...">
            </form>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Logo</th>
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Perusahaan</th>
                        <th class="px-4 py-3 text-left">Kontak</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($companies as $company)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="h-12 w-20 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden">
                                    @if ($company->logo_path)
                                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="max-h-10 w-auto">
                                    @else
                                        <span class="text-xs text-slate-400">No logo</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $company->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $company->name }}</div>
                                @if ((int) $currentCompanyId === (int) $company->id)
                                    <div class="mt-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                        Perusahaan aktif
                                    </div>
                                @endif
                                @if ($company->address)
                                    <div class="text-xs text-slate-500 mt-1">{{ \Illuminate\Support\Str::limit($company->address, 90) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div>{{ $company->email ?: '-' }}</div>
                                <div class="text-xs mt-1">{{ $company->phone ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $company->users_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('companies.edit', $company) }}"
                                        class="px-3 py-1 bg-amber-500 text-white rounded-lg text-xs hover:bg-amber-600">Edit</a>
                                    <form action="{{ route('companies.destroy', $company) }}" method="POST"
                                        onsubmit="return confirm('Hapus perusahaan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs hover:bg-red-600">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada perusahaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $companies->links() }}</div>
    </div>
@endsection
