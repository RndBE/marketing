@extends('layouts.app', ['title' => 'Edit Term Template'])

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <div class="text-xs text-slate-500">Edit</div>
            <h1 class="text-xl font-semibold">Term #{{ $template->id }}</h1>
        </div>
        <a href="{{ route('term_templates.index') }}"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
            Kembali
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <form method="POST" action="{{ route('term_templates.update', $template->id) }}" class="space-y-3">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Parent</label>
                    <select name="parent_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        <option value="">Jadikan utama</option>
                        @foreach ($options as $opt)
                            <option value="{{ $opt['id'] }}"
                                {{ (int) ($template->parent_id ?? 0) === (int) $opt['id'] ? 'selected' : '' }}>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Urutan</label>
                    <input name="urutan" value="{{ $template->urutan }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Aktif</label>
                    <select name="is_active" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        <option value="1" {{ $template->is_active ? 'selected' : '' }}>Ya</option>
                        <option value="0" {{ !$template->is_active ? 'selected' : '' }}>Tidak</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Group (opsional)</label>
                    <input name="group_name" value="{{ $template->group_name }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Judul (opsional)</label>
                    <input name="judul" value="{{ $template->judul }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Isi</label>
                <textarea name="isi" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ $template->isi }}</textarea>
            </div>

            <div class="flex justify-end">
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection
