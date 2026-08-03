@php
    $children = $termsByParent[$term->id] ?? collect();
    $prefix = $level === 0 ? '-' : str_repeat('>', $level);
@endphp

<div style="margin-left: {{ $level * 12 }}px; line-height:1.4;">
    {{ $level }} {{ $prefix }} {{ $term->isi }}
</div>

@if ($children->count())
    @foreach ($children->sort(function ($a, $b) {
        return [(int) $a->urutan, (int) $a->id]
            <=> [(int) $b->urutan, (int) $b->id];
    }) as $c)
        @include('documents.partials.term_node_pdf', [
            'term' => $c,
            'termsByParent' => $termsByParent,
            'level' => $level + 1,
        ])
    @endforeach
@endif
