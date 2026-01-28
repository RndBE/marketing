@php
    $children = $termsByParent[$t->id] ?? collect();
    $indent = $level * 18;
@endphp

<div class="rounded-2xl border border-slate-200 px-4 py-2">
    <div class="flex items-start justify-between ">
        <div class="min-w-0">
            <div class="text-sm text-slate-700">
                - {{ $t->isi }}
            </div>
            @if ($t->judul)
                <div class="text-xs text-slate-500 mt-1">{{ $t->judul }}</div>
            @endif
            <div class="text-xs text-slate-400 mt-1">
                urutan: {{ $t->urutan }} • aktif: {{ $t->is_active ? 'ya' : 'tidak' }}{!! $t->group_name ? ' • group: ' . $t->group_name : '' !!}
            </div>
        </div>

        <div class="shrink-0 flex gap-2">
            <a href="{{ route('term_templates.edit', $t->id) }}"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                Edit
            </a>

            <form method="POST" action="{{ route('term_templates.destroy', $t->id) }}"
                onsubmit="return confirm('Hapus template ini? (child ikut kehapus)')">
                @csrf
                @method('DELETE')
                <button
                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    @if ($children->count())
        <div class="mt-2 space-y-2">
            @foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $c)
                @include('term_templates.partials.node', [
                    't' => $c,
                    'termsByParent' => $termsByParent,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
