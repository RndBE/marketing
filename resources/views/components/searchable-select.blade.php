@props([
    'name' => null,
    'inputId' => null,
    'options' => [],
    'selectedId' => '',
    'placeholder' => 'Pilih opsi',
    'searchPlaceholder' => 'Cari...',
    'emptyText' => 'Tidak ada hasil.',
    'clearLabel' => null,
])

@php
    $normalizedOptions = collect($options)
        ->map(fn ($option) => [
            'id' => (string) ($option['id'] ?? ''),
            'label' => (string) ($option['label'] ?? ''),
        ])
        ->values()
        ->all();
@endphp

<div x-data="searchableDropdown({
        options: @js($normalizedOptions),
        selectedId: @js((string) $selectedId),
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
        emptyText: @js($emptyText),
        clearLabel: @js((string) ($clearLabel ?? ''))
    })" @click.outside="closePanel()" @keydown.escape.prevent="closePanel()"
    {{ $attributes->merge(['class' => 'relative']) }}>
    <input type="hidden" @if ($name) name="{{ $name }}" @endif @if ($inputId) id="{{ $inputId }}" @endif
        :value="selectedId">

    <button type="button" x-ref="trigger" @click="toggle()" @keydown.arrow-down.prevent="moveHighlight(1)"
        @keydown.arrow-up.prevent="moveHighlight(-1)"
        class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        <span class="truncate" :class="selectedId ? 'text-slate-900' : 'text-slate-400'" x-text="triggerLabel"></span>
        <i class="ri-expand-up-down-line shrink-0 text-base text-slate-400"></i>
    </button>

    <div x-show="open" x-cloak
        class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
        <div class="border-b border-slate-100 p-2">
            <input type="text" x-ref="search" x-model="query" :placeholder="searchPlaceholder" autocomplete="off"
                @input="onSearch()" @keydown.arrow-down.prevent="moveHighlight(1)"
                @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                @keydown.tab="closePanel()"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        </div>

        <div class="max-h-64 overflow-y-auto py-1">
            @if ($clearLabel)
                <button type="button" x-show="selectedId && query.trim() === ''" @mousedown.prevent="clear()"
                    class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50">
                    {{ $clearLabel }}
                </button>
            @endif

            <template x-if="filteredOptions.length === 0">
                <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
            </template>

            <template x-for="(option, index) in filteredOptions" :key="option.id">
                <button type="button" @mousedown.prevent="choose(option)" @mouseenter="highlightedIndex = index"
                    :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50">
                    <span x-text="option.label"></span>
                    <i class="ri-check-line shrink-0 text-base text-slate-900" x-show="option.id === selectedId"></i>
                </button>
            </template>
        </div>
    </div>
</div>
