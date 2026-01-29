@php
    $children = $termsByParent[$term->id] ?? collect();
    $prefix = $level === 0 ? '-' : str_repeat('>', $level);
@endphp

<div style="margin-left: {{ $level * 12 }}px; line-height:1.4;">
    {{ $level }} {{ $prefix }} {{ $term->isi }}
</div>

@if ($children->count())
    @foreach ($children->sortBy(fn($x) => $x->urutan . '-' . $x->id) as $c)
        @include('documents.partials.term_node_pdf', [
            'term' => $c,
            'termsByParent' => $termsByParent,
            'level' => $level + 1,
        ])
    @endforeach
@endif
