<aside
    class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200 transition-transform duration-300 z-20"
    x-cloak x-show="$store.sidebar.open" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full">
    <div class="px-3 py-4 border-slate-200">
        <div class="flex justify-center items-center gap-3">
            <img src="{{ asset('images/logo_arsol.png') }}" alt="" class="h-10">
        </div>
    </div>

    <nav class="px-3 py-4 overflow-y-auto" x-data="{
        invoice: {{ request()->routeIs('invoices.*') ? 'true' : 'false' }},
        purchase_order: {{ request()->routeIs('purchase-orders.*') ? 'true' : 'false' }},
        marketing_report: {{ request()->routeIs('marketing-reports.*') ? 'true' : 'false' }},
        penawaran: {{ request()->routeIs('alurpenawaran.*', 'penawaran.*', 'term_templates.*') ? 'true' : 'false' }},
        usulan: {{ request()->routeIs('usulan.*') ? 'true' : 'false' }},
        prospect: {{ request()->routeIs('prospects.*') ? 'true' : 'false' }},
        lead_report: {{ request()->routeIs('lead-reports.*') ? 'true' : 'false' }},
        pricelist: {{ request()->routeIs('price_list.*', 'komponen.*') ? 'true' : 'false' }},
        pic: {{ request()->routeIs('pics.*') ? 'true' : 'false' }},
        users: {{ request()->routeIs('users.*') ? 'true' : 'false' }},
        rbac: {{ request()->routeIs('roles.*', 'permissions.*', 'user-roles.*', 'audit-logs.*') ? 'true' : 'false' }}
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

            <div x-show="penawaran" x-collapse class="mt-1 space-y-1">
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
        @if(auth()->user()->hasPermission('view-usulan'))
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

                <div x-show="invoice" x-collapse class="mt-1 space-y-1">
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
        @endif
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

                <div x-show="purchase_order" x-collapse class="mt-1 space-y-1">
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

        @if(auth()->user()->hasPermission('view-marketing-report') || auth()->user()->hasPermission('create-marketing-report'))
            <div class="mb-4">
                <button @click="marketing_report = !marketing_report"
                    :class="marketing_report ? 'bg-slate-100 text-slate-900' : 'text-slate-700'"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold hover:bg-slate-50 rounded-lg transition">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex w-5 justify-center">
                            <i class="fa-regular fa-chart-bar fa-fw text-[18px] leading-none"></i>
                        </span>
                        <span>Marketing BD</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': marketing_report }" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="marketing_report" x-collapse class="mt-1 space-y-1">
                    @if(auth()->user()->hasPermission('view-marketing-report'))
                        <a href="{{ route('marketing-reports.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                        {{ request()->routeIs('marketing-reports.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Daftar Laporan</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('create-marketing-report'))
                        <a href="{{ route('marketing-reports.create') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                        {{ request()->routeIs('marketing-reports.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>Buat Laporan</span>
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

            <div x-show="lead_report" x-collapse class="mt-1 space-y-1">
                <a href="{{ route('lead-reports.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                        {{ request()->routeIs('lead-reports.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Daftar Report</span>
                </a>

                @if(auth()->user()->hasRole('superadmin'))
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

                <div x-show="usulan" x-collapse class="mt-1 space-y-1">
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

                <div x-show="prospect" x-collapse class="mt-1 space-y-1">
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

                <div x-show="pricelist" x-collapse class="mt-1 space-y-1">
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

                <div x-show="pic" x-collapse class="mt-1 space-y-1">
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

                <div x-show="users" x-collapse class="mt-1 space-y-1">
                    <a href="{{ route('users.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                                            {{ request()->routeIs('users.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Data User</span>
                    </a>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-roles') && auth()->user()->hasRole('superadmin'))
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

                <div x-show="rbac" x-collapse class="mt-1 space-y-1">
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

                    @if(auth()->user()->hasPermission('view-audit-logs') && auth()->user()->hasRole('superadmin'))
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
