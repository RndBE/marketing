@extends('layouts.app', ['title' => 'Daftar Penawaran'])

@section('content')
    @php
        $filterQuery = array_filter([
            'q' => $q ?: null,
            'company_id' => $companyFilterId ?: null,
            'date_from' => request()->filled('date_from') ? $dateFrom : null,
            'date_to' => request()->filled('date_to') ? $dateTo : null,
            'status' => $statusFilter !== 'all' ? $statusFilter : null,
            'sort' => $sortFilter !== 'newest' ? $sortFilter : null,
            'per_page' => $perPage !== 15 ? $perPage : null,
        ], fn ($value) => $value !== null && $value !== '');

        $filterUrl = function (array $overrides = [], array $remove = []) use ($filterQuery) {
            $query = array_merge($filterQuery, $overrides);
            foreach ($remove as $key) {
                unset($query[$key]);
            }
            unset($query['page']);

            return route('penawaran.index', array_filter($query, fn ($value) => $value !== null && $value !== ''));
        };

        $scopeQuery = array_filter([
            'q' => $q ?: null,
            'company_id' => $companyFilterId ?: null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ], fn ($value) => filled($value));

        $statusTabs = [
            'all' => 'Semua',
            'waiting' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'goal' => 'Goal',
        ];
        $statusLabels = $statusTabs;
        $sortLabels = [
            'newest' => 'Terbaru',
            'oldest' => 'Terlama',
            'title' => 'Judul A-Z',
            'status' => 'Status',
        ];
        $y = date('Y');
        $m = date('m');
        $activeChips = [];

        if ($q !== '') {
            $activeChips[] = ['key' => 'q', 'label' => 'Pencarian: "'.$q.'"', 'href' => $filterUrl([], ['q'])];
        }
        if ($companyFilterId) {
            $selectedCompany = $filterCompanies->firstWhere('id', $companyFilterId);
            $activeChips[] = [
                'key' => 'company_id',
                'label' => 'Perusahaan: '.($selectedCompany?->code ?? $selectedCompany?->name ?? '-'),
                'href' => $filterUrl([], ['company_id']),
            ];
        }
        if (request()->filled('date_from') || request()->filled('date_to')) {
            $activeChips[] = [
                'key' => 'date',
                'label' => 'Periode: '
                    .($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Awal')
                    .' - '
                    .($dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Akhir'),
                'href' => $filterUrl([], ['date_from', 'date_to']),
            ];
        }
        if ($statusFilter !== 'all') {
            $activeChips[] = ['key' => 'status', 'label' => 'Status: '.$statusLabels[$statusFilter], 'href' => $filterUrl([], ['status'])];
        }
        if ($sortFilter !== 'newest') {
            $activeChips[] = ['key' => 'sort', 'label' => 'Urutkan: '.$sortLabels[$sortFilter], 'href' => $filterUrl([], ['sort'])];
        }
        if ($perPage !== 15) {
            $activeChips[] = ['key' => 'per_page', 'label' => $perPage.' per halaman', 'href' => $filterUrl([], ['per_page'])];
        }

        $periodSummary = request()->filled('date_from') || request()->filled('date_to')
            ? (($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Awal')
                .' - '
                .($dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Akhir'))
            : 'Semua periode';
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold">Daftar Penawaran</h1>
        </div>

        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
            <a id="btn-export-excel"
               data-download-loading
               data-loading-label="Menyiapkan..."
               href="{{ route('penawaran.export-excel', array_filter(['q' => $q, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'company_id' => $companyFilterId], fn($value) => filled($value))) }}"
               class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 sm:w-auto sm:shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Excel
            </a>
        </div>
    </div>

    <div x-data="{ mobileFilterOpen: false }" @keydown.escape.window="mobileFilterOpen = false">
        <button data-mobile-filter-trigger type="button" @click="mobileFilterOpen = true"
            :aria-expanded="mobileFilterOpen" aria-controls="mobile-penawaran-filter"
            class="mb-4 flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm md:hidden">
            <span class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10m-7 6h4" />
                    </svg>
                </span>
                <span>
                    <span class="block text-sm font-semibold text-slate-900">Filter &amp; Pencarian</span>
                    <span class="block text-xs text-slate-500">Cari dokumen atau atur periode</span>
                </span>
            </span>
            @if (count($activeChips) > 0)
                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-bold text-white">{{ count($activeChips) }}</span>
            @else
                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            @endif
        </button>

        <form method="GET" data-penawaran-filter-form data-filter-mode="desktop" class="mb-4 hidden md:block">
            @include('penawaran.partials.filter-fields', ['mode' => 'desktop', 'prefix' => 'desktop'])
        </form>

        <button data-mobile-filter-backdrop type="button" x-cloak x-show="mobileFilterOpen"
            @click="mobileFilterOpen = false" aria-label="Tutup filter"
            x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 bg-slate-950/45 backdrop-blur-[1px] md:hidden"></button>

        <section id="mobile-penawaran-filter" data-mobile-filter-drawer x-cloak x-show="mobileFilterOpen"
            role="dialog" aria-modal="true" aria-labelledby="mobile-filter-title"
            x-transition:enter="transform transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transform transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="fixed inset-x-0 bottom-0 z-[60] max-h-[88vh] overflow-y-auto rounded-t-3xl bg-white shadow-2xl md:hidden">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
                <div>
                    <h2 id="mobile-filter-title" class="font-semibold text-slate-900">Filter penawaran</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Atur pencarian sebelum diterapkan</p>
                </div>
                <button type="button" @click="mobileFilterOpen = false" aria-label="Tutup filter"
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="GET" data-penawaran-filter-form data-filter-mode="mobile" class="p-5">
                @include('penawaran.partials.filter-fields', ['mode' => 'mobile', 'prefix' => 'mobile'])
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <a href="{{ route('penawaran.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                        Reset
                    </a>
                    <button data-filter-apply type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
    (function () {
        var forms = Array.from(document.querySelectorAll('[data-penawaran-filter-form]'));
        var btnExport = document.getElementById('btn-export-excel');
        var exportBase = "{{ route('penawaran.export-excel') }}";

        function syncExportBtn(form) {
            var q = form.querySelector('[data-filter-search]')?.value || '';
            var from = form.querySelector('[data-filter-date-from]')?.value || '';
            var to = form.querySelector('[data-filter-date-to]')?.value || '';
            var company = form.querySelector('[data-filter-company]');
            var params = new URLSearchParams();
            if (q)    params.set('q', q);
            if (from) params.set('date_from', from);
            if (to)   params.set('date_to', to);
            if (company && company.value) params.set('company_id', company.value);
            btnExport.href = exportBase + (params.toString() ? '?' + params.toString() : '');
        }

        // Date range shortcuts
        window.setRange = function(from, to) {
            var preferredMode = window.innerWidth < 768 ? 'mobile' : 'desktop';
            var form = forms.find(function(candidate) {
                return candidate.dataset.filterMode === preferredMode;
            }) || forms[0];

            form.querySelector('[data-filter-date-from]').value = from;
            form.querySelector('[data-filter-date-to]').value = to;
            syncExportBtn(form);
            form.requestSubmit();
        };

        forms.forEach(function(form) {
            form.addEventListener('input', function() {
                syncExportBtn(form);
            });
        });

        var initialForm = forms.find(function(form) {
            return form.dataset.filterMode === 'desktop';
        }) || forms[0];
        if (initialForm) syncExportBtn(initialForm);
    })();
    </script>

    <div data-compact-period-shortcuts class="mb-4 hidden flex-wrap items-center gap-2 md:flex">
        <span class="text-xs text-slate-500">Cepat:</span>

        <button type="button" onclick="setRange('{{ $y }}-01-01','{{ $y }}-12-31')"
            class="rounded-lg border px-3 py-1 text-xs font-semibold transition-colors {{ $dateFrom === "{$y}-01-01" && $dateTo === "{$y}-12-31" ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
            Tahun {{ $y }}
        </button>

        @for($yr = (int) $y - 1; $yr >= (int) $y - 2; $yr--)
            <button type="button" onclick="setRange('{{ $yr }}-01-01','{{ $yr }}-12-31')"
                class="rounded-lg border px-3 py-1 text-xs font-semibold transition-colors {{ $dateFrom === "{$yr}-01-01" && $dateTo === "{$yr}-12-31" ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ $yr }}
            </button>
        @endfor

        <button type="button" onclick="setRange('{{ $y }}-{{ $m }}-01','{{ $y }}-{{ $m }}-31')"
            class="rounded-lg border px-3 py-1 text-xs font-semibold transition-colors {{ $dateFrom === "{$y}-{$m}-01" ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
            Bulan Ini
        </button>

        <button type="button" onclick="setRange('2000-01-01','2099-12-31')"
            class="rounded-lg border px-3 py-1 text-xs font-semibold transition-colors {{ $dateTo === '2099-12-31' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
            Semua Waktu
        </button>
    </div>

    @if ($activeChips)
        <div data-active-filter-chips class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-slate-500">Filter aktif:</span>
            @foreach ($activeChips as $chip)
                <a data-filter-chip="{{ $chip['key'] }}" href="{{ $chip['href'] }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    aria-label="Hapus filter {{ $chip['label'] }}">
                    <span>{{ $chip['label'] }}</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            @endforeach
            <a data-clear-all-filters href="{{ route('penawaran.index') }}"
                class="px-2 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700">Hapus semua</a>
        </div>
    @endif

    <div data-filter-results-summary
        class="mb-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <p>
                Menampilkan
                <span class="font-semibold text-slate-900">{{ $data->total() > 0 ? $data->firstItem().'-'.$data->lastItem() : '0' }}</span>
                dari
                <span class="font-semibold text-slate-900">{{ $data->total() }}</span>
                penawaran
            </p>
            <p class="text-xs text-slate-500">
                {{ count($activeChips) > 0 ? count($activeChips).' filter aktif' : 'Tanpa filter' }}
                <span class="mx-1 text-slate-300">|</span>
                {{ $periodSummary }}
            </p>
        </div>
    </div>

    {{-- Dashboard Ringkasan --}}
    @if ($jumlahDisetujui > 0 || $jumlahGoal > 0)
        <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Penawaran Disetujui --}}
            <a data-kpi-link="approved" href="{{ route('penawaran.index', array_merge($scopeQuery, ['status' => 'approved'])) }}"
                class="block rounded-2xl border border-green-200 bg-green-50 p-4 transition hover:-translate-y-0.5 hover:border-green-300 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500/30">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-green-100 shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-xs font-semibold text-green-700 uppercase tracking-wide">Disetujui</div>
                </div>
                <div class="text-2xl font-bold text-green-900">{{ $jumlahDisetujui }}
                    <span class="text-sm font-normal text-green-700">penawaran</span>
                </div>
                <div class="mt-1 text-sm font-semibold text-green-800">
                    Rp {{ number_format($totalDisetujui, 0, ',', '.') }}
                </div>
            </a>

            {{-- Penawaran Goal --}}
            <a data-kpi-link="goal" href="{{ route('penawaran.index', array_merge($scopeQuery, ['status' => 'goal'])) }}"
                class="block rounded-2xl border border-blue-200 bg-blue-50 p-4 transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 shrink-0 text-base">
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 21h8m-4-4v4m7-17h2v2a5 5 0 0 1-5 5M5 4H3v2a5 5 0 0 0 5 5m0-7h8v5a4 4 0 0 1-8 0V4z" />
                        </svg>
                    </div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Goal / Project</div>
                </div>
                <div class="text-2xl font-bold text-blue-900">{{ $jumlahGoal }}
                    <span class="text-sm font-normal text-blue-700">penawaran</span>
                </div>
                <div class="mt-1 text-sm font-semibold text-blue-800">
                    Rp {{ number_format($totalGoal, 0, ',', '.') }}
                </div>
            </a>

            {{-- Konversi --}}
            <a data-kpi-link="conversion" href="{{ route('penawaran.index', array_merge($scopeQuery, ['status' => 'goal'])) }}"
                class="block rounded-2xl border border-purple-200 bg-purple-50 p-4 transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-purple-100 shrink-0">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="text-xs font-semibold text-purple-700 uppercase tracking-wide">Konversi ke Goal</div>
                </div>

                {{-- Konversi Jumlah --}}
                <div class="mb-2">
                    <div class="flex items-center justify-between text-xs text-purple-700 mb-1">
                        <span>Jumlah</span>
                        <span class="font-bold">{{ $pctJumlah }}%</span>
                    </div>
                    <div class="w-full bg-purple-100 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full"
                             style="width: {{ min($pctJumlah, 100) }}%"></div>
                    </div>
                    <div class="text-[11px] text-purple-600 mt-0.5">{{ $jumlahGoal }} dari {{ $jumlahDisetujui }} penawaran</div>
                </div>

                {{-- Konversi Nilai --}}
                <div>
                    <div class="flex items-center justify-between text-xs text-purple-700 mb-1">
                        <span>Nilai</span>
                        <span class="font-bold">{{ $pctNilai }}%</span>
                    </div>
                    <div class="w-full bg-purple-100 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full"
                             style="width: {{ min($pctNilai, 100) }}%"></div>
                    </div>
                    <div class="text-[11px] text-purple-600 mt-0.5">
                        Rp {{ number_format($totalGoal, 0, ',', '.') }} dari Rp {{ number_format($totalDisetujui, 0, ',', '.') }}
                    </div>
                </div>
            </a>

        </div>
    @endif

    @php
        $currentUser = auth()->user();
        $canEditPenawaran = $currentUser->hasPermission('edit-penawaran');
        $canDeletePenawaran = $currentUser->hasPermission('delete-penawaran');
        $canCreatePenawaran = $currentUser->hasPermission('create-penawaran');
        $rowPresentation = $data->getCollection()->mapWithKeys(function ($row) use ($currentUser, $canEditPenawaran, $canDeletePenawaran, $canCreatePenawaran) {
            $isOwnerOrAdmin = $currentUser->hasRole('admin') || (int) $row->id_user === (int) $currentUser->id;

            return [$row->id => [
                'docNo' => $row->docNumber?->doc_no ?? 'PNW-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                'visible' => $row->approval?->status !== 'dihapus' && $row->approval?->module !== 'penghapusan',
                'canEdit' => $isOwnerOrAdmin && $canEditPenawaran,
                'canDelete' => $isOwnerOrAdmin && $canDeletePenawaran,
                'canDuplicate' => $canCreatePenawaran,
                'canRequestDelete' => $isOwnerOrAdmin,
            ]];
        });
        $visibleRows = $data->getCollection()->filter(fn ($row) => $rowPresentation[$row->id]['visible']);
        $hasActiveFilters = count($activeChips) > 0;
    @endphp

    <div data-penawaran-desktop-table class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white md:block">
        <div class="max-h-[70vh] overflow-auto">
            <table class="min-w-full text-sm">
                <thead data-sticky-header class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50/95 shadow-sm backdrop-blur">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No</th>
                        <th class="px-4 py-3 text-left font-semibold">Judul</th>
                        <th class="px-4 py-3 text-left font-semibold">PIC</th>
                        <th class="px-4 py-3 text-left font-semibold">Updated</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th data-sticky-action-column
                            class="sticky right-0 z-20 bg-slate-50 px-4 py-3 text-right font-semibold shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.45)]">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($visibleRows as $row)
                        @php
                            $presentation = $rowPresentation[$row->id];
                            $docNo = $presentation['docNo'];
                        @endphp
                            <tr class="group align-top hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a data-penawaran-detail-link href="{{ route('penawaran.show', $row) }}"
                                        class="font-semibold text-slate-900 underline-offset-4 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                        {{ $docNo }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a data-penawaran-detail-link href="{{ route('penawaran.show', $row) }}"
                                        class="font-medium text-slate-900 underline-offset-4 hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                        {{ $row->judul ?? '-' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ trim(($row->pic?->honorific ? $row->pic->honorific . ' ' : '') . ($row->pic?->nama ?? '-')) }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $row->pic?->instansi ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    {{ $row->updated_at?->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    @include('penawaran.partials.status-badges', ['row' => $row])

                                </td>
                                <td data-sticky-action-column
                                    class="sticky right-0 z-[5] whitespace-nowrap bg-white px-4 py-3 text-right shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.45)] group-hover:bg-slate-50">
                                    @include('penawaran.partials.actions', [
                                        'row' => $row,
                                        'canEdit' => $presentation['canEdit'],
                                        'canDelete' => $presentation['canDelete'],
                                        'canDuplicate' => $presentation['canDuplicate'],
                                        'canRequestDelete' => $presentation['canRequestDelete'],
                                    ])
                                </td>
                            </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                @include('penawaran.partials.empty-state', ['hasActiveFilters' => $hasActiveFilters])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('penawaran.partials.mobile-list', [
        'visibleRows' => $visibleRows,
        'rowPresentation' => $rowPresentation,
        'hasActiveFilters' => $hasActiveFilters,
    ])

    <div class="mt-4">
        {{ $data->links() }}
    </div>
@endsection
