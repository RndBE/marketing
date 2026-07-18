@props([
    'scope',
    'menuLabel' => 'Buka menu aksi',
    'hasMenu' => true,
])

<div data-table-actions data-action-scope="{{ $scope }}"
    class="flex items-start justify-end gap-2"
    x-data="floatingActionMenu"
    @keydown.escape.window="close()"
    @resize.window="close()"
    @scroll.window="close()">
    {{ $primary }}

    @if ($hasMenu)
        <button x-ref="trigger" type="button" @click="toggle()" :aria-expanded="open"
            aria-label="{{ $menuLabel }}"
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-bold leading-none text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
            <span aria-hidden="true">&middot;&middot;&middot;</span>
        </button>

        <template x-teleport="body">
            <div x-ref="menu" data-floating-table-action-menu
                x-cloak x-show="open" :style="menuStyle" @click.outside="close()"
                x-transition:enter="transform transition duration-150 ease-out"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transform transition duration-100 ease-in"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="fixed z-50 flex min-w-40 max-w-[calc(100vw-1rem)] origin-top-right flex-col gap-1 rounded-xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                {{ $slot }}
            </div>
        </template>
    @endif
</div>
