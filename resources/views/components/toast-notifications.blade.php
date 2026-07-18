@php
    $initialToasts = [];

    if (session('success')) {
        $initialToasts[] = ['type' => 'success', 'message' => session('success')];
    }

    if (session('error')) {
        $initialToasts[] = ['type' => 'error', 'message' => session('error')];
    }

    $statusMessages = [
        'profile-updated' => 'Profil berhasil diperbarui.',
        'password-updated' => 'Kata sandi berhasil diperbarui.',
        'verification-link-sent' => 'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
    ];

    if (isset($statusMessages[session('status')])) {
        $initialToasts[] = ['type' => 'success', 'message' => $statusMessages[session('status')]];
    }
@endphp

<div data-global-toast-notifications x-data="toastNotifications(@js($initialToasts))"
    @show-toast.window="notify($event.detail)" aria-live="polite" aria-relevant="additions text"
    class="pointer-events-none fixed inset-x-3 top-3 z-[120] flex flex-col gap-3 sm:left-auto sm:right-4 sm:top-4 sm:w-full sm:max-w-sm">
    <template x-for="toast in toasts" :key="toast.id">
        <article x-cloak :role="toast.type === 'error' ? 'alert' : 'status'"
            @mouseenter="pause(toast.id)" @mouseleave="resume(toast.id)"
            class="pointer-events-auto overflow-hidden rounded-2xl border bg-white shadow-xl shadow-slate-950/10"
            :class="{
                'border-emerald-200': toast.type === 'success',
                'border-rose-200': toast.type === 'error',
                'border-amber-200': toast.type === 'warning',
                'border-sky-200': toast.type === 'info'
            }"
            x-transition:enter="transform transition duration-200 ease-out"
            x-transition:enter-start="-translate-y-2 opacity-0 sm:translate-x-3 sm:translate-y-0"
            x-transition:enter-end="translate-x-0 translate-y-0 opacity-100"
            x-transition:leave="transform transition duration-150 ease-in"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-y-2 opacity-0 sm:translate-x-3 sm:translate-y-0">
            <div class="flex items-start gap-3 px-4 py-3.5"
                :class="{
                    'bg-emerald-50': toast.type === 'success',
                    'bg-rose-50': toast.type === 'error',
                    'bg-amber-50': toast.type === 'warning',
                    'bg-sky-50': toast.type === 'info'
                }">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                    :class="{
                        'bg-emerald-100 text-emerald-700': toast.type === 'success',
                        'bg-rose-100 text-rose-700': toast.type === 'error',
                        'bg-amber-100 text-amber-700': toast.type === 'warning',
                        'bg-sky-100 text-sky-700': toast.type === 'info'
                    }">
                    <svg x-show="toast.type === 'success'" aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.5 10 3 3 6-6" />
                    </svg>
                    <svg x-show="toast.type === 'error'" aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20"
                        fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="10" cy="10" r="7" />
                        <path stroke-linecap="round" d="m7.5 7.5 5 5m0-5-5 5" />
                    </svg>
                    <svg x-show="toast.type === 'warning'" aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20"
                        fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linejoin="round" d="M9.1 3.7 2.8 14.5A1 1 0 0 0 3.7 16h12.6a1 1 0 0 0 .9-1.5L10.9 3.7a1 1 0 0 0-1.8 0Z" />
                        <path stroke-linecap="round" d="M10 7v4m0 2.5h.01" />
                    </svg>
                    <svg x-show="toast.type === 'info'" aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20"
                        fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="10" cy="10" r="7" />
                        <path stroke-linecap="round" d="M10 9v4m0-6.5h.01" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-900" x-text="toast.title"></p>
                    <p class="mt-0.5 break-words text-sm leading-5 text-slate-600" x-text="toast.message"></p>
                </div>

                <button type="button" @click="remove(toast.id)" aria-label="Tutup notifikasi"
                    class="-mr-1 -mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/70 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" d="m6 6 8 8m0-8-8 8" />
                    </svg>
                </button>
            </div>
        </article>
    </template>
</div>
