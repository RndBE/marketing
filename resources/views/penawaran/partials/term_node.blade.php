@php
    $children = $termsByParent[$term->id] ?? collect();
    $indent = $level * 18;
@endphp

<div class="rounded-2xl border border-slate-200 px-4 py-2 term-node" draggable="true" data-term-id="{{ $term->id }}">

    {{-- VIEW MODE --}}
    <div class="term-view-{{ $term->id }} flex items-center justify-between gap-3">
        <div class="min-w-0 flex items-center">
            <div class="text-sm text-slate-700" style="padding-left: {{ $indent }}px;">
                - {{ $term->isi }}
            </div>
        </div>

        @if (!empty($canEdit))
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="termInlineEdit({{ $term->id }})"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                    Edit
                </button>
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

    {{-- EDIT MODE --}}
    @if (!empty($canEdit))
        <div class="term-edit-{{ $term->id }} hidden">
            <form method="POST" id="term-form-{{ $term->id }}"
                action="{{ route('penawaran.terms.update', [$penawaran->id, $term->id]) }}">
                @csrf
                @method('PUT')
                <div class="flex items-start gap-2" style="padding-left: {{ $indent }}px;">
                    <textarea name="isi" rows="2"
                        class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">{{ $term->isi }}</textarea>
                    <div class="flex flex-col gap-1 shrink-0">
                        <button type="submit"
                            class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                            Simpan
                        </button>
                        <button type="button" onclick="termInlineCancel({{ $term->id }})"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                            Batal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

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
