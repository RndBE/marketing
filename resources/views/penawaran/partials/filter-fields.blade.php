@php
    $isMobile = $mode === 'mobile';
    $fieldClass = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10';
@endphp

@if ($statusFilter !== 'all')
    <input type="hidden" name="status" value="{{ $statusFilter }}">
@endif
@if ($sortFilter !== 'newest')
    <input type="hidden" name="sort" value="{{ $sortFilter }}">
@endif
@if ($perPage !== 15)
    <input type="hidden" name="per_page" value="{{ $perPage }}">
@endif

<div class="{{ $isMobile ? 'space-y-4' : 'grid grid-cols-1 gap-2 sm:grid-cols-2 xl:flex xl:flex-wrap' }}">
    <div class="{{ $isMobile ? '' : 'xl:min-w-[260px] xl:flex-1' }}">
        @if ($isMobile)
            <label for="{{ $prefix }}-search-input" class="mb-1.5 block text-xs font-semibold text-slate-600">Pencarian</label>
        @endif
        <input id="{{ $prefix }}-search-input" data-filter-search name="q" value="{{ $q ?? '' }}"
            placeholder="Cari judul, instansi, atau no. dokumen..."
            class="{{ $fieldClass }}" autocomplete="off">
    </div>

    @if ($canViewAll)
        <div class="{{ $isMobile ? '' : 'xl:min-w-[190px] xl:w-auto' }}">
            @if ($isMobile)
                <label for="{{ $prefix }}-company-filter" class="mb-1.5 block text-xs font-semibold text-slate-600">Perusahaan</label>
            @endif
            <select id="{{ $prefix }}-company-filter" data-filter-company name="company_id"
                @if (!$isMobile) onchange="this.form.requestSubmit()" @endif
                class="{{ $fieldClass }}">
                <option value="">Semua Perusahaan</option>
                @foreach ($filterCompanies as $company)
                    <option value="{{ $company->id }}" @selected((int) $companyFilterId === (int) $company->id)>
                        {{ $company->code }} - {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="{{ $isMobile ? '' : 'sm:col-span-2 xl:w-auto' }}">
        @if ($isMobile)
            <span class="mb-1.5 block text-xs font-semibold text-slate-600">Rentang tanggal</span>
        @endif
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <label for="{{ $prefix }}-date-from" class="sr-only">Tanggal mulai</label>
            <input type="date" name="date_from" id="{{ $prefix }}-date-from" data-filter-date-from value="{{ $dateFrom }}"
                class="min-w-0 {{ $fieldClass }}">
            <span class="text-sm text-slate-400">-</span>
            <label for="{{ $prefix }}-date-to" class="sr-only">Tanggal akhir</label>
            <input type="date" name="date_to" id="{{ $prefix }}-date-to" data-filter-date-to value="{{ $dateTo }}"
                class="min-w-0 {{ $fieldClass }}">
        </div>
    </div>

    @unless ($isMobile)
        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Cari</button>
        <a href="{{ route('penawaran.index') }}"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold transition hover:bg-slate-50">Reset</a>
    @endunless
</div>
