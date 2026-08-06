@php
    $sidebarGroups = [
        'invoice' => request()->routeIs('invoices.*', 'templates.*'),
        'purchase_order' => request()->routeIs('purchase-orders.*'),
        'penawaran' => request()->routeIs('alurpenawaran.*', 'penawaran.*', 'term_templates.*'),
        'usulan' => request()->routeIs('usulan.*') && ! request()->routeIs('usulan.quotation.*'),
        'penawaran_harga' => request()->routeIs('usulan.quotation.*'),
        'prospect' => request()->routeIs('prospects.*'),
        'lead_report' => request()->routeIs('lead-reports.*'),
        'pricelist' => request()->routeIs('price_list.*', 'komponen.*'),
        'pic' => request()->routeIs('pics.*'),
        'users' => request()->routeIs('users.*', 'companies.*'),
        'rbac' => request()->routeIs('roles.*', 'permissions.*', 'user-roles.*', 'audit-logs.*'),
    ];

    $sidebarPanelStyle = fn (string $group) => $sidebarGroups[$group] ? '' : 'display: none;';
@endphp

<aside id="application-sidebar" data-mobile-sidebar
    class="fixed inset-y-0 left-0 z-40 flex w-[min(18rem,calc(100vw-2rem))] transform-gpu flex-col border-r border-slate-200 bg-white shadow-xl will-change-transform md:z-20 md:w-64 md:shadow-none"
    x-show="mobileSidebarOpen || (isDesktop && $store.sidebar.open)"
    x-transition:enter="transform transition duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transform transition duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full">
    <div class="px-3 py-4 border-slate-200">
        <div class="flex items-center justify-between gap-3 md:justify-center">
            <img src="{{ asset('images/logo_be.png') }}" alt="Logo BE" class="h-10">
            <button type="button" data-sidebar-close
                class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 md:hidden"
                @click="mobileSidebarOpen = false" aria-label="Tutup navigasi">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4" @click="if ($event.target.closest('a')) mobileSidebarOpen = false" x-data="{
        invoice: {{ $sidebarGroups['invoice'] ? 'true' : 'false' }},
        purchase_order: {{ $sidebarGroups['purchase_order'] ? 'true' : 'false' }},
        penawaran: {{ $sidebarGroups['penawaran'] ? 'true' : 'false' }},
        usulan: {{ $sidebarGroups['usulan'] ? 'true' : 'false' }},
        penawaranHarga: {{ $sidebarGroups['penawaran_harga'] ? 'true' : 'false' }},
        prospect: {{ $sidebarGroups['prospect'] ? 'true' : 'false' }},
        lead_report: {{ $sidebarGroups['lead_report'] ? 'true' : 'false' }},
        pricelist: {{ $sidebarGroups['pricelist'] ? 'true' : 'false' }},
        pic: {{ $sidebarGroups['pic'] ? 'true' : 'false' }},
        users: {{ $sidebarGroups['users'] ? 'true' : 'false' }},
        rbac: {{ $sidebarGroups['rbac'] ? 'true' : 'false' }}
    }">
        <!-- Penawaran Section -->
        <div class="mb-4">
            <button @click="penawaran = !penawaran"
                :class="penawaran ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex w-5 justify-center">
                        <i class="fa-regular fa-file-lines fa-fw text-[18px] leading-none"></i>
                    </span>
                    <span>Penawaran</span>
                </span>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': penawaran }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="penawaran" x-collapse style="{{ $sidebarPanelStyle('penawaran') }}" class="mt-1 space-y-1">
                @if(auth()->user()->hasPermission('manage-alur'))
                    <a href="{{ route('alurpenawaran.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('alurpenawaran.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Alur Approval</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('view-penawaran'))
                    <a href="{{ route('penawaran.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('penawaran.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Daftar Penawaran</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('create-penawaran'))
                    <a href="{{ route('penawaran.create') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('penawaran.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Buat Penawaran</span>
                    </a>
                @endif

                @if(auth()->user()->hasPermission('edit-penawaran'))
                    <a href="{{ route('term_templates.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('term_templates.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Keterangan</span>
                    </a>
                @endif

                <a href="{{ route('penawaran.deleted.list') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                    {{ request()->routeIs('penawaran.deleted.list') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Riwayat Penghapusan</span>
                </a>
            </div>

            <!-- Invoice Section -->

        </div>
        <div class="mb-4">
                <button @click="invoice = !invoice" :class="invoice ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-credit-card fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Invoice</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': invoice }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="invoice" x-collapse style="{{ $sidebarPanelStyle('invoice') }}" class="mt-1 space-y-1">
                    <a href="{{ route('invoices.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                    {{ request()->routeIs('invoices.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Daftar Invoice</span>
                    </a>

                    <a href="{{ route('invoices.create') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                    {{ request()->routeIs('invoices.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Buat Invoice</span>
                    </a>
                    <a href="{{ route('templates.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('templates.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Atur Template</span>
                    </a>
                </div>
        </div>
        @if(auth()->user()->hasPermission('view-purchase-order') || auth()->user()->hasPermission('create-purchase-order'))
            <div class="mb-4">
                <button @click="purchase_order = !purchase_order"
                    :class="purchase_order ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-clipboard fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Purchase Order</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': purchase_order }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="purchase_order" x-collapse style="{{ $sidebarPanelStyle('purchase_order') }}" class="mt-1 space-y-1">
                    @if(auth()->user()->hasPermission('view-purchase-order'))
                        <a href="{{ route('purchase-orders.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('purchase-orders.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Daftar PO</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('create-purchase-order'))
                        <a href="{{ route('purchase-orders.create') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('purchase-orders.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Buat PO</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Lead Reports --}}
        <div class="mb-4">
            <button @click="lead_report = !lead_report"
                :class="lead_report ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex w-5 justify-center">
                        <i class="ri-file-search-line text-[18px] leading-none"></i>
                    </span>
                    <span>Lead Reports</span>
                </span>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': lead_report }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="lead_report" x-collapse style="{{ $sidebarPanelStyle('lead_report') }}" class="mt-1 space-y-1">
                <a href="{{ route('lead-reports.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                        {{ request()->routeIs('lead-reports.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Daftar Report</span>
                </a>

                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('lead-reports.create') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                            {{ request()->routeIs('lead-reports.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Upload Report</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Usulan Penawaran --}}
        @if(auth()->user()->hasPermission('view-usulan'))
            <div class="mb-4">
                <button @click="usulan = !usulan" :class="usulan ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-lightbulb fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Usulan</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': usulan }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="usulan" x-collapse style="{{ $sidebarPanelStyle('usulan') }}" class="mt-1 space-y-1">
                    <a href="{{ route('usulan.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                            {{ request()->routeIs('usulan.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Daftar Usulan</span>
                    </a>

                    @if(auth()->user()->hasPermission('create-usulan'))
                        <a href="{{ route('usulan.create') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                                                    {{ request()->routeIs('usulan.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Buat Usulan</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Penawaran Harga: dokumen yang kita terbitkan atas usulan yang masuk. --}}
        @if(auth()->user()->hasPermission('view-usulan'))
            <div class="mb-4">
                <button @click="penawaranHarga = !penawaranHarga"
                    :class="penawaranHarga ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-file-lines fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Penawaran Harga</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': penawaranHarga }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="penawaranHarga" x-collapse style="{{ $sidebarPanelStyle('penawaran_harga') }}"
                    class="mt-1 space-y-1">
                    <a href="{{ route('usulan.quotation.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                               {{ request()->routeIs('usulan.quotation.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Daftar Penawaran Harga</span>
                    </a>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasPermission('view-prospect'))
            <div class="mb-4">
                <button @click="prospect = !prospect"
                    :class="prospect ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-compass fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Prospek</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': prospect }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="prospect" x-collapse style="{{ $sidebarPanelStyle('prospect') }}" class="mt-1 space-y-1">
                    <a href="{{ route('prospects.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                            {{ request()->routeIs('prospects.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Daftar Prospek</span>
                    </a>

                    @if(auth()->user()->hasPermission('create-prospect'))
                        <a href="{{ route('prospects.create') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                {{ request()->routeIs('prospects.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Tambah Prospek</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-pricelist'))
            <div class="mb-4">
                <button @click="pricelist = !pricelist"
                    :class="pricelist ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-bookmark fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Price List</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': pricelist }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="pricelist" x-collapse style="{{ $sidebarPanelStyle('pricelist') }}" class="mt-1 space-y-1">
                    <a href="{{ route('price_list.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('price_list.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Daftar Bundle</span>
                    </a>

                    <a href="{{ route('price_list.create') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('price_list.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Buat Bundle</span>
                    </a>

                    <a href="{{ route('komponen.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('komponen.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Komponen</span>
                    </a>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-pic'))
            <div class="mb-4">
                <button @click="pic = !pic" :class="pic ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-address-card fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>PIC</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': pic }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="pic" x-collapse style="{{ $sidebarPanelStyle('pic') }}" class="mt-1 space-y-1">
                    <a href="{{ route('pics.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                            {{ request()->routeIs('pics.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Data PIC</span>
                    </a>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-users'))
            <div class="mb-4">
                <button @click="users = !users" :class="users ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-user fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>User Management</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': users }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="users" x-collapse style="{{ $sidebarPanelStyle('users') }}" class="mt-1 space-y-1">
                    <a href="{{ route('users.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                            {{ request()->routeIs('users.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Data User</span>
                    </a>

                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('companies.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                            {{ request()->routeIs('companies.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Data Perusahaan</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-roles') && auth()->user()->hasRole('admin'))
            <div class="mb-4">
                <button @click="rbac = !rbac" :class="rbac ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-id-badge fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>RBAC</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': rbac }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="rbac" x-collapse style="{{ $sidebarPanelStyle('rbac') }}" class="mt-1 space-y-1">
                    <a href="{{ route('roles.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('roles.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Roles</span>
                    </a>

                    <a href="{{ route('permissions.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('permissions.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Permissions</span>
                    </a>

                    <a href="{{ route('user-roles.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                    {{ request()->routeIs('user-roles.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>User Roles</span>
                    </a>

                    @if(auth()->user()->hasPermission('view-audit-logs') && auth()->user()->hasRole('admin'))
                        <a href="{{ route('audit-logs.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                                            {{ request()->routeIs('audit-logs.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Audit Log</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </nav>

    <div class="mt-auto px-3 pb-4">
        {{-- Logout button removed and moved to header --}}
    </div>
</aside>
