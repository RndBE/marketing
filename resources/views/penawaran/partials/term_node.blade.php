@php
    $children = $termsByParent[$term->id] ?? collect();
    $indent = $level * 18;
@endphp

<div class="rounded-2xl border border-slate-200 px-4 py-2">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0 flex items-center">
            <div class="text-sm text-slate-700" style="padding-left: {{ $indent }}px;">
                - {{ $term->isi }}
            </div>
        </div>

        <form method="POST" action="{{ route('penawaran.terms.delete', [$penawaran->id, $term->id]) }}"
            onsubmit="return confirm('Hapus keterangan?')">
            @csrf
            @method('DELETE')
            <button
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                Hapus
            </button>
        </form>
    </div>

    @if ($children->count())
        <div class="mt-2 space-y-2">
            @foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $c)
                @include('penawaran.partials.term_node', [
                    'penawaran' => $penawaran,
                    'term' => $c,
                    'termsByParent' => $termsByParent,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
