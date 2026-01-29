<aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200">
    <div class="px-3 py-4 border-slate-200">
        <div class="flex justify-center items-center gap-3">
            <img src="{{ asset('images/logo_arsol.png') }}" alt="" class="h-10">
        </div>
    </div>

    <nav class="px-3 py-4 space-y-4 overflow-y-auto">
        <div>
            <div class="px-3 text-xs font-semibold text-slate-500 mb-2">Penawaran</div>

            <a href="{{ route('alurpenawaran.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                {{ request()->routeIs('alurpenawaran.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Alur Approval</span>
            </a>

            <a href="{{ route('penawaran.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                {{ request()->routeIs('penawaran.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Daftar Penawaran</span>
            </a>

            <a href="{{ route('penawaran.create') }}"
                class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                {{ request()->routeIs('penawaran.create') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Buat Penawaran</span>
            </a>
            <a href="{{ route('term_templates.index') }}"
                class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                {{ request()->routeIs('term_templates.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Keterangan</span>
            </a>
            {{-- <a href="{{ route('term_templates.index') }}"
                class="mt-1 flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
                {{ request()->routeIs('term_templates.index') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Ri</span>
            </a>

            <a href="{{ route('penawaran.deleted.list') }}"
                class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded">
                Riwayat Penghapusan
            </a>
        </div> --}}

        <div>
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
        </div>
        <div>
            <div class="px-3 text-xs font-semibold text-slate-500 mb-2">PIC</div>

            <a href="{{ route('pics.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium
        {{ request()->routeIs('pics.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                <span>Data PIC</span>
            </a>
        </div>
    </nav>

    <div class="mt-auto px-3 pb-4">
        <form method="POST" action="">
            @csrf
            <button type="submit"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Logout
            </button>
        </form>
    </div>
</aside>
