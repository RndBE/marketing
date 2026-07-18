<div data-global-duplicate-confirmation x-data="duplicateConfirmation"
    @open-duplicate-confirmation.window="openDialog($event.detail)"
    @keydown.escape.window="if (open) cancel()">
    <div x-cloak x-show="open" @click.self="cancel()"
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm sm:px-6"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <section x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="global-duplicate-title"
            aria-describedby="global-duplicate-description" @keydown.tab="trapFocus($event)"
            class="w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl shadow-slate-950/20"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-95 opacity-0">
            <div class="h-1 bg-indigo-500"></div>

            <div class="px-5 pb-5 pt-6 sm:px-6 sm:pb-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-100">
                        <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <rect x="8" y="8" width="10" height="10" rx="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">Konfirmasi duplikat</p>
                        <h2 id="global-duplicate-title"
                            class="mt-1 text-xl font-semibold tracking-tight text-slate-900" x-text="title"></h2>
                    </div>

                    <button type="button" @click="cancel()" :disabled="processing"
                        aria-label="Tutup konfirmasi duplikat"
                        class="-mr-1 -mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-40">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 20 20" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" d="m5 5 10 10M15 5 5 15" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3.5">
                    <p id="global-duplicate-description" class="text-sm leading-6 text-slate-700"
                        x-text="message"></p>
                    <p class="mt-2 text-xs font-medium text-indigo-700">Data asli tetap aman dan tidak akan diubah.</p>
                </div>
            </div>

            <div
                class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button x-ref="cancel" type="button" @click="cancel()" :disabled="processing"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-50 sm:w-auto">
                    Batal
                </button>
                <button data-duplicate-modal-progress x-ref="confirm" type="button" @click="confirm()"
                    :disabled="processing" :aria-busy="processing"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-80 sm:w-auto">
                    <svg x-show="!processing" aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none"
                        stroke="currentColor" stroke-width="1.8">
                        <rect x="7" y="7" width="8" height="8" rx="1.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7V5.5A1.5 1.5 0 0 0 11.5 4h-6A1.5 1.5 0 0 0 4 5.5v6A1.5 1.5 0 0 0 5.5 13H7" />
                    </svg>
                    <svg data-duplicate-modal-spinner x-cloak x-show="processing" aria-hidden="true"
                        class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor"
                            stroke-width="3" />
                        <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                    </svg>
                    <span x-show="!processing" x-text="confirmLabel"></span>
                    <span x-cloak x-show="processing">Menduplikat...</span>
                </button>
            </div>
        </section>
    </div>
</div>
