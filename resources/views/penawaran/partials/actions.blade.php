@props([
    'row',
    'canEdit' => false,
    'canDelete' => false,
    'canDuplicate' => false,
    'canRequestDelete' => false,
])

@php
    $status = $row->approval?->status ?? 'draft';
    $module = $row->approval?->module ?? '';
    $currentStep = (int) ($row->approval?->current_step ?? 0);
    $canDeleteDirectly = $canDelete
        && ($status === 'draft' || ($status === 'menunggu' && $module === 'penawaran' && $currentStep <= 1));
    $canSubmitDelete = $canRequestDelete
        && $status === 'menunggu'
        && $module === 'penawaran'
        && $currentStep >= 2;
    $hasSecondaryActions = $canEdit || $canDeleteDirectly || $canSubmitDelete || $canDuplicate;
@endphp

<div class="flex items-start justify-end gap-2" x-data="floatingActionMenu"
    @keydown.escape.window="close()" @resize.window="close()" @scroll.window="close()">
    <a data-compact-link-loading href="{{ route('penawaran.show', $row) }}"
        class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
        Detail
    </a>

    @if ($hasSecondaryActions)
        <div class="min-w-0">
            <button x-ref="trigger" type="button" @click="toggle()" :aria-expanded="open"
                aria-label="Buka aksi penawaran"
                class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-bold leading-none text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                <span aria-hidden="true">&middot;&middot;&middot;</span>
            </button>

            <template x-teleport="body">
                <div x-ref="menu" data-action-menu data-mobile-action-popover data-desktop-action-menu
                    x-cloak x-show="open" :style="menuStyle" @click.outside="close()"
                    x-transition:enter="transform transition duration-150 ease-out"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100"
                    x-transition:leave="transform transition duration-100 ease-in"
                    x-transition:leave-start="scale-100 opacity-100"
                    x-transition:leave-end="scale-95 opacity-0"
                    class="fixed z-50 flex min-w-40 max-w-[calc(100vw-1rem)] origin-top-right flex-col gap-1 rounded-xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                    @if ($canEdit)
                        <a data-action-edit-penawaran href="{{ route('penawaran.edit', $row) }}"
                            class="rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            Edit
                        </a>
                    @endif

                    @if ($canDeleteDirectly)
                        <form method="POST" action="{{ route('penawaran.destroy', $row) }}"
                            data-confirm-title="Hapus Penawaran?"
                            data-confirm-delete="Penawaran ini akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.">
                            @csrf
                            @method('DELETE')
                            <button data-action-delete-penawaran type="submit"
                                class="w-full rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                Hapus
                            </button>
                        </form>
                    @elseif ($canSubmitDelete)
                        <form method="POST" action="{{ route('penawaran.request.delete', $row) }}"
                            data-confirm-title="Ajukan Penghapusan Penawaran?"
                            data-confirm-action="Pengajuan penghapusan akan dikirim untuk persetujuan. Penawaran belum dihapus sampai disetujui."
                            data-confirm-label="Ajukan Hapus">
                            @csrf
                            <button data-action-request-delete-penawaran type="submit"
                                class="w-full rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                Ajukan Hapus
                            </button>
                        </form>
                    @endif

                    @if ($canDuplicate)
                        <form method="POST" action="{{ route('penawaran.duplicate', $row) }}"
                            data-duplicate-submit
                            data-confirm-title="Duplikat Penawaran?"
                            data-confirm-duplicate="Salinan baru akan dibuat dari penawaran ini. Data penawaran asli tidak berubah.">
                            @csrf
                            <button data-action-duplicate-penawaran data-duplicate-button type="submit"
                                class="inline-flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-wait disabled:opacity-70">
                                <svg data-duplicate-spinner aria-hidden="true" class="hidden h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-width="3" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                                </svg>
                                <span data-duplicate-label data-idle-label="Duplikat"
                                    data-loading-label="Menduplikat...">Duplikat</span>
                            </button>
                        </form>
                    @endif
                </div>
            </template>
        </div>
    @endif
</div>
