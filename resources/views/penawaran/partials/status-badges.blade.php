@props(['row'])

@php
    $status = $row->approval?->status ?? 'draft';
    $module = $row->approval?->module ?? '';
    $step = (int) ($row->approval?->current_step ?? 0);

    [$label, $classes] = match (true) {
        $status === 'menunggu' && $module === 'penawaran' => ["Menunggu persetujuan - Tahap {$step}", 'bg-amber-100 text-amber-800'],
        $status === 'disetujui' && $module === 'penawaran' => ['Disetujui', 'bg-emerald-100 text-emerald-800'],
        $status === 'ditolak' && $module === 'penawaran' => ['Ditolak', 'bg-rose-100 text-rose-800'],
        $status === 'menunggu' && $module === 'penghapusan' => ["Menunggu penghapusan - Tahap {$step}", 'bg-sky-100 text-sky-800'],
        $status === 'disetujui' && $module === 'penghapusan' => ['Penghapusan disetujui', 'bg-rose-100 text-rose-800'],
        $status === 'dihapus' => ['Dihapus', 'bg-slate-200 text-slate-700'],
        default => ['Draft', 'bg-slate-100 text-slate-700'],
    };
@endphp

<div class="flex flex-wrap gap-1.5">
    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $classes }}">{{ $label }}</span>
    @if ($row->is_goal)
        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">Goal</span>
    @endif
</div>
