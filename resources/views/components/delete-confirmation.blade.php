<div data-global-delete-confirmation data-delete-theme="permanent" x-data="deleteConfirmation"
    @open-delete-confirmation.window="openDialog($event.detail)"
    @keydown.escape.window="if (open) cancel()">
    <div x-cloak x-show="open" @click.self="cancel()"
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm sm:px-6"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <section x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="global-delete-title"
            aria-describedby="global-delete-description" @keydown.tab="trapFocus($event)"
            class="w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl shadow-slate-950/20"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-95 opacity-0">
            <div class="h-1 bg-rose-500"></div>

            <div class="px-5 pb-5 pt-6 sm:px-6 sm:pb-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-inset ring-rose-100">
                        <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 6V4.75h6V6m-9 0h12m-10.5 0 .72 12.25h7.56L16.5 6M10 10v4.5m4-4.5v4.5" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-rose-600">Tindakan permanen</p>
                        <h2 id="global-delete-title" class="mt-1 text-xl font-semibold tracking-tight text-slate-900"
                            x-text="title"></h2>
                    </div>

                    <button type="button" @click="cancel()" aria-label="Tutup konfirmasi hapus"
                        class="-mr-1 -mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 20 20" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" d="m5 5 10 10M15 5 5 15" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3.5">
                    <p id="global-delete-description" class="text-sm leading-6 text-slate-700"
                        x-text="message"></p>
                    <p class="mt-2 flex items-center gap-2 text-xs font-medium text-rose-700">
                        <svg aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <circle cx="10" cy="10" r="7.5" />
                            <path stroke-linecap="round" d="M10 6.5v4M10 13.5h.01" />
                        </svg>
                        Data yang sudah dihapus tidak dapat dipulihkan kembali.
                    </p>
                </div>
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button x-ref="cancel" type="button" @click="cancel()"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 sm:w-auto">
                    Batal
                </button>
                <button x-ref="confirm" type="button" @click="confirm()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-600/20 transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 sm:w-auto">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 5V3.75h6V5m-8 0h10m-8.75 0 .65 10.25h6.2L13.75 5M8.5 8v4.5m3-4.5v4.5" />
                    </svg>
                    <span x-text="confirmLabel"></span>
                </button>
            </div>
        </section>
    </div>
</div>
