{{-- Sengaja bukan `relative`: panel di bawah dianggap milik header, bukan milik tombol ini. --}}
<div x-data="notificationBell({
        items: @js($layoutNotifications ?? []),
        unreadCount: {{ (int) ($layoutUnreadNotifications ?? 0) }},
        pollUrl: '{{ route('notifications.index') }}',
        readAllUrl: '{{ route('notifications.read-all') }}',
    })" @keydown.escape.window="panelOpen = false">

    <button type="button" @click="panelOpen = !panelOpen" :aria-expanded="panelOpen"
        aria-label="Notifikasi"
        class="relative rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
        <i class="ri-notification-3-line text-xl leading-none"></i>
        <span x-cloak x-show="unreadCount > 0"
            class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white ring-2 ring-white"
            x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
    </button>

    <div x-cloak x-show="panelOpen" @click.away="panelOpen = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 top-full z-50 mt-3 w-80 origin-top-right rounded-xl border border-slate-100 bg-white shadow-lg sm:w-96">

        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-800">Notifikasi</p>
            <button type="button" x-show="unreadCount > 0" @click="markAllRead()"
                class="text-xs font-semibold text-sky-600 hover:text-sky-700">
                Tandai sudah dibaca
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="items.length === 0">
                <div class="px-4 py-8 text-center">
                    <i class="ri-notification-off-line text-2xl text-slate-300"></i>
                    <p class="mt-2 text-sm text-slate-500">Belum ada notifikasi.</p>
                </div>
            </template>

            <template x-for="item in items" :key="item.id">
                <a :href="item.url"
                    class="flex gap-3 border-b border-slate-50 px-4 py-3 transition last:border-b-0 hover:bg-slate-50"
                    :class="item.dibaca ? '' : 'bg-sky-50/60'">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                        :class="item.dibaca ? 'bg-transparent' : 'bg-sky-500'"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-800" x-text="item.judul"></span>
                        <span class="mt-0.5 block text-xs leading-relaxed text-slate-600" x-text="item.pesan"></span>
                        <span class="mt-1 block text-[11px] text-slate-400" x-text="item.waktu"></span>
                    </span>
                </a>
            </template>
        </div>
    </div>
</div>
