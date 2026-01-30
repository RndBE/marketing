<aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200">
    <div class="px-3 py-4 border-slate-200">
        <div class="flex justify-center items-center gap-3">
            <img src="{{ asset('images/logo_arsol.png') }}" alt="" class="h-10">
        </div>
    </div>

    <nav class="px-3 py-4 overflow-y-auto">
        <div class="mb-6">
            <div class="px-3 text-xs font-semibold text-slate-500 mb-2">Penawaran</div>

            @if(auth()->user()->hasPermission('manage-alur'))
                <a href="{{ route('alurpenawaran.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('alurpenawaran.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Alur Approval</span>
                </a>
            @endif

            @if(auth()->user()->hasPermission('view-penawaran'))
                <a href="{{ route('penawaran.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('penawaran.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Daftar Penawaran</span>
                </a>
            @endif

            @if(auth()->user()->hasPermission('create-penawaran'))
                <a href="{{ route('penawaran.create') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('penawaran.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Buat Penawaran</span>
                </a>
            @endif

            @if(auth()->user()->hasPermission('edit-penawaran'))
                <a href="{{ route('term_templates.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('term_templates.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Keterangan</span>
                </a>
            @endif

            <a href="{{ route('penawaran.deleted.list') }}"
                class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                {{ request()->routeIs('penawaran.deleted.list') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Riwayat Penghapusan</span>
            </a>
        </div>

        {{-- Usulan Penawaran --}}
        @if(auth()->user()->hasPermission('view-usulan'))
            <div class="mb-6">
                <div class="px-3 text-xs font-semibold text-slate-500 mb-2">Usulan</div>

                <a href="{{ route('usulan.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                            {{ request()->routeIs('usulan.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Daftar Usulan</span>
                </a>

                @if(auth()->user()->hasPermission('create-usulan'))
                    <a href="{{ route('usulan.create') }}"
                        class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                        {{ request()->routeIs('usulan.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>Buat Usulan</span>
                    </a>
                @endif
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-pricelist'))
            <div class="mb-6">
                <div class="px-3 text-xs font-semibold text-slate-500 mb-2">Price List</div>

                <a href="{{ route('price_list.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('price_list.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Daftar Bundle</span>
                </a>

                <a href="{{ route('price_list.create') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('price_list.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Buat Bundle</span>
                </a>

                <a href="{{ route('komponen.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('komponen.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Komponen</span>
                </a>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-pic'))
            <div class="mb-6">
                <div class="px-3 text-xs font-semibold text-slate-500 mb-2">PIC</div>

                <a href="{{ route('pics.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                            {{ request()->routeIs('pics.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Data PIC</span>
                </a>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-users'))
            <div class="mb-6">
                <div class="px-3 text-xs font-semibold text-slate-500 mb-2">User Management</div>

                <a href="{{ route('users.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                            {{ request()->routeIs('users.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Data User</span>
                </a>
            </div>
        @endif

        @if(auth()->user()->hasPermission('manage-roles'))
            <div class="mb-6">
                <div class="px-3 text-xs font-semibold text-slate-500 mb-2">RBAC</div>

                <a href="{{ route('roles.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('roles.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Roles</span>
                </a>

                <a href="{{ route('permissions.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('permissions.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>Permissions</span>
                </a>

                <a href="{{ route('user-roles.index') }}"
                    class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                                    {{ request()->routeIs('user-roles.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span>User Roles</span>
                </a>
            </div>
        @endif
    </nav>

    <div class="mt-auto px-3 pb-4">
        {{-- Logout button removed and moved to header --}}
    </div>
</aside>