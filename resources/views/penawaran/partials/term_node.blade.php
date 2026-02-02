@php
    $children = $termsByParent[$term->id] ?? collect();
    $indent = $level * 18;
@endphp

<div class="rounded-2xl border border-slate-200 px-4 py-2 term-node" draggable="true" data-term-id="{{ $term->id }}">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0 flex items-center">
            <div class="text-sm text-slate-700" style="padding-left: {{ $indent }}px;">
                - {{ $term->isi }}
            </div>
        </div>

        @if (!empty($canEdit))
            <div class="flex items-center gap-2">
                <details class="inline-block text-left">
                    <summary
                        class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                        Edit
                    </summary>
                    <div class="mt-2 w-[360px] rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
                        <form method="POST" action="{{ route('penawaran.terms.update', [$penawaran->id, $term->id]) }}"
                            class="space-y-2">
                            @csrf
                            @method('PUT')
                            <label class="block text-xs font-semibold">Isi</label>
                            <textarea name="isi" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ $term->isi }}</textarea>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </details>
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
        @endif
    </div>

    @if ($children->count())
        <div class="mt-2 space-y-2 term-list" data-parent-id="{{ $term->id }}">
            @foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $c)
                @include('penawaran.partials.term_node', [
                    'penawaran' => $penawaran,
                    'term' => $c,
                    'termsByParent' => $termsByParent,
                    'canEdit' => $canEdit,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>

    @endif
</div>
