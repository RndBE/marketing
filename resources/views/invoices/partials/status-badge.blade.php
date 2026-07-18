@php
    $statusStyle = match ($status) {
        'sent' => ['label' => 'Sent', 'class' => 'bg-blue-100 text-blue-700'],
        'paid' => ['label' => 'Paid', 'class' => 'bg-green-100 text-green-700'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-red-100 text-red-700'],
        'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600'],
        default => ['label' => ucfirst((string) $status), 'class' => 'bg-slate-100 text-slate-600'],
    };
@endphp

<span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle['class'] }}">
    {{ $statusStyle['label'] }}
</span>
