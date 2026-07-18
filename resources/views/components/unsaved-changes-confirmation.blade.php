<div data-global-unsaved-confirmation x-data="unsavedChangesConfirmation"
    @open-unsaved-changes-confirmation.window="openDialog($event.detail)"
    @keydown.escape.window="cancel()"
    x-cloak>
    <div x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[80] bg-slate-950/50 backdrop-blur-[2px]"
        @click="cancel()"></div>

    <div x-show="open"
        class="fixed inset-0 z-[90] flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="unsaved-changes-title"
        aria-describedby="unsaved-changes-description"
        @keydown.tab.prevent="trapFocus($event)">
        <div x-ref="dialog"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
            class="w-full max-w-md overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-2xl">
            <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <i class="ri-error-warning-line text-xl"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Periksa perubahan</p>
                        <h2 id="unsaved-changes-title" class="mt-1 text-lg font-semibold text-slate-950"
                            x-text="title">Perubahan belum disimpan</h2>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4">
                <p id="unsaved-changes-description" class="text-sm leading-6 text-slate-600" x-text="message"></p>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                <button x-ref="cancel" type="button" @click="cancel()"
                    class="inline-flex w-full justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:w-auto">
                    Tetap di halaman
                </button>
                <button type="button" @click="confirm()"
                    class="inline-flex w-full justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 sm:w-auto">
                    Keluar tanpa menyimpan
                </button>
            </div>
        </div>
    </div>
</div>
