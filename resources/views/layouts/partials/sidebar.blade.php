<aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200">
    <div class="px-3 py-4 border-slate-200">
        <div class="flex justify-center items-center gap-3">
            <img src="{{ asset('images/logo_arsol.png') }}" alt="" class="h-10">
        </div>
    </div>

    <nav class="px-3 py-4 overflow-y-auto" x-data="{
        invoice: {{ request()->routeIs('invoices.*') ? 'true' : 'false' }},
        purchase_order: {{ request()->routeIs('purchase-orders.*') ? 'true' : 'false' }},
        penawaran: {{ request()->routeIs('alurpenawaran.*', 'penawaran.*', 'term_templates.*') ? 'true' : 'false' }},
        usulan: {{ request()->routeIs('usulan.*') ? 'true' : 'false' }},
        pricelist: {{ request()->routeIs('price_list.*', 'komponen.*') ? 'true' : 'false' }},
        pic: {{ request()->routeIs('pics.*') ? 'true' : 'false' }},
        users: {{ request()->routeIs('users.*') ? 'true' : 'false' }},
        rbac: {{ request()->routeIs('roles.*', 'permissions.*', 'user-roles.*') ? 'true' : 'false' }}
    }">
        <!-- Penawaran Section -->
        <div class="mb-4">
            <button @click="penawaran = !penawaran"
                class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                <span>Penawaran</span>
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
                <button @click="invoice = !invoice"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>Invoice</span>
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
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>Purchase Order</span>
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
        {{-- Usulan Penawaran --}}
        @if(auth()->user()->hasPermission('view-usulan'))
            <div class="mb-4">
                <button @click="usulan = !usulan"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>Usulan</span>
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

        @if(auth()->user()->hasPermission('manage-pricelist'))
            <div class="mb-4">
                <button @click="pricelist = !pricelist"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>Price List</span>
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
                <button @click="pic = !pic"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>PIC</span>
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
                <button @click="users = !users"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>User Management</span>
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

        @if(auth()->user()->hasPermission('manage-roles'))
            <div class="mb-4">
                <button @click="rbac = !rbac"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg transition">
                    <span>RBAC</span>
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
                </div>
            </div>
        @endif
    </nav>

    <div class="mt-auto px-3 pb-4">
        {{-- Logout button removed and moved to header --}}
    </div>
</aside>
