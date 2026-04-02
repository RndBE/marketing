@extends('layouts.app', ['title' => 'Lead / Prospek'])

@section('content')
    @php
        $statusSearchOptions = collect($statusOptions)
            ->map(fn($label, $value) => ['id' => (string) $value, 'label' => $label])
            ->values()
            ->all();
    @endphp

    <div class="mb-5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('prospects.export-excel', request()->only(['q', 'status'])) }}"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                Export Excel
            </a>
        </div>

        @if (auth()->user()->hasPermission('create-prospect'))
            <a href="{{ route('prospects.create') }}"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Tambah Prospek
            </a>
        @endif
    </div>

    <form method="GET" class="mb-4 rounded-2xl border border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input name="q" value="{{ $q }}"
                placeholder="Cari judul prospek, instansi, PIC, produk, sumber lead, catatan..."
                class="md:col-span-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">

            <div x-data="searchableSelect({
                options: @js($statusSearchOptions),
                selectedId: @js((string) $status),
                placeholder: 'Cari status...',
                emptyText: 'Status tidak ditemukan.'
            })" class="relative">
                <input type="hidden" name="status" :value="selectedId">

                <div class="relative">
                    <input type="text" x-model="query" :placeholder="placeholder" @focus="openOptions()"
                        @click="openOptions()" @input="onInput()" @keydown.arrow-down.prevent="moveHighlight(1)"
                        @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                        @keydown.escape.prevent="open = false" @blur="setTimeout(() => closeOptions(), 120)"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-20 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                    <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                        <button type="button" x-show="selectedId || query" @click="clear()"
                            class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                            <i class="ri-close-line text-base"></i>
                        </button>
                        <i class="ri-arrow-down-s-line text-lg text-slate-400"></i>
                    </div>
                </div>

                <div x-show="open" x-cloak
                    class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                    <div class="max-h-64 overflow-y-auto py-1">
                        <button type="button" @mousedown.prevent="clear()"
                            class="flex w-full items-start px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50">
                            Semua Status
                        </button>

                        <template x-if="filteredOptions.length === 0">
                            <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
                        </template>

                        <template x-for="(option, index) in filteredOptions" :key="option.id">
                            <button type="button" @mousedown.prevent="choose(option)"
                                @mouseenter="highlightedIndex = index"
                                :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                                class="flex w-full items-start px-3 py-2 text-left text-sm hover:bg-slate-50">
                                <span x-text="option.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-2 mt-3">
            <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Cari
            </button>
            <a href="{{ route('prospects.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Lead</th>
                        <th class="px-4 py-3 text-left font-semibold">Kontak</th>
                        <th class="px-4 py-3 text-left font-semibold">Pipeline</th>
                        <th class="px-4 py-3 text-left font-semibold">Follow Up</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC Kantor</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($prospects as $prospect)
                        @php
                            $isOverdue =
                                $prospect->next_follow_up_at &&
                                $prospect->next_follow_up_at->isPast() &&
                                $prospect->hasil_akhir === 'in_progress';
                            $canDelete = auth()->user()->hasPermission('delete-prospect')
                                && ((int) $prospect->created_by === (int) auth()->id()
                                    || (int) $prospect->assigned_to === (int) auth()->id()
                                    || auth()->user()->hasPermission('view-all-prospect'));
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold">{{ $prospect->display_title }}</div>
                                <div class="text-sm text-slate-600 mt-1">{{ $prospect->display_instansi }}</div>
                                <div class="text-xs text-slate-500 mt-1">
                                    {{ $prospect->tanggal_lead?->format('d M Y') ?? '-' }}
                                    @if ($prospect->sumber_lead)
                                        • {{ $prospect->sumber_lead }}
                                    @endif
                                </div>
                                @if ($prospect->produk)
                                    <div class="text-xs text-slate-500 mt-1">{{ $prospect->produk }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium">{{ $prospect->display_pic_name }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $prospect->jabatan_pic ?: '-' }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $prospect->display_phone }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $prospect->display_email }}</div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-semibold bg-{{ $prospect->status_color }}-100 text-{{ $prospect->status_color }}-700">
                                        {{ $prospect->status_label }}
                                    </span>
                                    <span
                                        class="px-2 py-1 rounded text-xs font-semibold bg-{{ $prospect->outcome_color }}-100 text-{{ $prospect->outcome_color }}-700">
                                        {{ $prospect->outcome_label }}
                                    </span>
                                </div>
                                <div class="text-sm font-medium mt-2">Rp
                                    {{ number_format((int) $prospect->potensi_nilai, 0, ',', '.') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $prospect->updates_count }} update progress
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="text-xs text-slate-500">Last</div>
                                <div class="font-medium">{{ $prospect->last_follow_up_at?->format('d M Y') ?? '-' }}</div>
                                <div class="text-xs text-slate-500 mt-2">Next</div>
                                <div class="font-medium {{ $isOverdue ? 'text-rose-600' : '' }}">
                                    {{ $prospect->next_follow_up_at?->format('d M Y') ?? '-' }}
                                </div>
                                @if ($isOverdue)
                                    <div class="text-xs text-rose-600 mt-1">Follow up lewat jadwal</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium">{{ $prospect->assignedTo?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $prospect->creator?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right align-top whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('prospects.show', $prospect) }}"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                        Detail
                                    </a>
                                    @if ($prospect->penawarans_count > 0)
                                        <a href="{{ route('prospects.show', $prospect) }}"
                                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                            {{ $prospect->penawarans_count }} Penawaran
                                        </a>
                                    @endif
                                    @if ($canDelete)
                                        <form method="POST" action="{{ route('prospects.destroy', $prospect) }}"
                                            onsubmit="return confirm('Hapus prospek ini? Progress dan lampiran progress akan ikut terhapus, tapi penawaran dan usulan hanya dilepas dari prospek ini.')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                Belum ada data prospek.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($prospects->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $prospects->links() }}
            </div>
        @endif
    </div>
@endsection
