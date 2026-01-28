@extends('layouts.app', ['title' => 'Terms Template'])

@section('content')
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold">Template Keterangan</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="font-semibold mb-3">Tambah Keterangan</div>
                <form method="POST" action="{{ route('term_templates.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold mb-1">Parent (opsional)</label>
                        <select name="parent_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                            <option value="">Jadikan utama</option>
                            @foreach ($options as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Aktif</label>
                            <select name="is_active"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <option value="1" selected>Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">Isi</label>
                        <textarea name="isi" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>

                    <button
                        class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Tambah
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <form method="GET" class="flex gap-2">
                    <input name="q" value="{{ $q }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Cari isi/judul...">
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Cari
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="font-semibold mb-3">Daftar (Tree)</div>

                <div class="space-y-2">
                    @forelse ($roots as $t)
                        @include('term_templates.partials.node', [
                            't' => $t,
                            'termsByParent' => $termsByParent,
                            'level' => 0,
                        ])
                    @empty
                        <div class="text-sm text-slate-500">Belum ada template.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
