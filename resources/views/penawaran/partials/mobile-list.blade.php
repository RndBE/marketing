<div data-penawaran-mobile-list class="space-y-3 md:hidden">
    @forelse($visibleRows as $row)
        @php
            $presentation = $rowPresentation[$row->id];
        @endphp
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <a data-penawaran-detail-link href="{{ route('penawaran.show', $row) }}"
                        class="text-xs font-semibold text-slate-500 underline-offset-4 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        {{ $presentation['docNo'] }}
                    </a>
                    <h2 class="mt-1 break-words font-semibold text-slate-900">
                        <a data-penawaran-detail-link href="{{ route('penawaran.show', $row) }}"
                            class="underline-offset-4 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            {{ $row->judul ?? '-' }}
                        </a>
                    </h2>
                </div>
                <time class="shrink-0 text-xs text-slate-500">{{ $row->updated_at?->format('d M Y') }}</time>
            </div>

            <div class="mt-3 text-sm text-slate-700">
                <div class="font-medium">
                    {{ trim(($row->pic?->honorific ? $row->pic->honorific . ' ' : '') . ($row->pic?->nama ?? '-')) }}
                </div>
                @if ($row->pic?->instansi)
                    <div class="mt-0.5 text-xs text-slate-500">{{ $row->pic->instansi }}</div>
                @endif
            </div>

            <div class="mt-3">
                @include('penawaran.partials.status-badges', ['row' => $row])
            </div>
            <div class="mt-4 border-t border-slate-100 pt-3">
                @include('penawaran.partials.actions', [
                    'row' => $row,
                    'canEdit' => $presentation['canEdit'],
                    'canDelete' => $presentation['canDelete'],
                    'canDuplicate' => $presentation['canDuplicate'],
                    'canRequestDelete' => $presentation['canRequestDelete'],
                ])
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            @include('penawaran.partials.empty-state', ['hasActiveFilters' => $hasActiveFilters])
        </div>
    @endforelse
</div>
