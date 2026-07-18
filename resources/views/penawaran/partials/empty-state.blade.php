<div @if ($hasActiveFilters) data-filter-empty-state @else data-empty-state @endif
    class="flex flex-col items-center justify-center px-4 py-12 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M9.5 3a6.5 6.5 0 104.16 11.5L19 19.84M19 15v4.84h-4.84" />
        </svg>
    </div>

    @if ($hasActiveFilters)
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Tidak ada penawaran yang sesuai filter</h3>
        <p class="mt-1 max-w-sm text-sm text-slate-500">Coba ubah kata pencarian, periode, perusahaan, atau status yang dipilih.</p>
        <a data-clear-empty-filter href="{{ route('penawaran.index') }}"
            class="mt-4 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            Hapus semua filter
        </a>
    @else
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada penawaran</h3>
        <p class="mt-1 max-w-sm text-sm text-slate-500">Data penawaran akan muncul di sini setelah dokumen pertama dibuat.</p>
    @endif
</div>
