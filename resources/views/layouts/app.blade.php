<!doctype html>
<html lang="id" data-sidebar-preload="true" data-sidebar-open="true">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css">
    <script>
        (function () {
            try {
                document.documentElement.dataset.sidebarOpen = localStorage.getItem('sidebarOpen') !== 'false' ? 'true' : 'false';
            } catch (error) {
                document.documentElement.dataset.sidebarOpen = 'true';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="overflow-x-hidden bg-slate-100 text-slate-900" x-data="{ mobileSidebarOpen: false, isDesktop: window.innerWidth >= 768 }"
    x-init="$store.sidebar.open = localStorage.getItem('sidebarOpen') !== 'false'; document.documentElement.removeAttribute('data-sidebar-preload')"
    @keydown.escape.window="mobileSidebarOpen = false"
    @resize.window="isDesktop = window.innerWidth >= 768; if (isDesktop) mobileSidebarOpen = false"
    :class="mobileSidebarOpen ? 'overflow-y-hidden md:overflow-y-auto' : ''">
    <div data-app-shell class="flex min-h-screen min-w-0">
        @include('layouts.partials.sidebar')

        <button type="button" data-sidebar-backdrop x-cloak x-show="mobileSidebarOpen"
            @click="mobileSidebarOpen = false" aria-label="Tutup navigasi"
            x-transition:enter="transition-opacity duration-300 ease-out motion-reduce:transition-none"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-300 ease-in motion-reduce:transition-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-30 bg-slate-950/40 backdrop-blur-[1px] md:hidden"></button>

        {{-- Main content shifts when sidebar is open --}}
        <div data-sidebar-content
            class="flex min-w-0 flex-1 flex-col transition-[margin] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none"
            :class="$store.sidebar.open ? 'md:ml-64' : 'md:ml-0'">

            <header class="flex min-w-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    {{-- Sidebar toggle button --}}
                    <button type="button" data-sidebar-toggle
                        @click="if (isDesktop) { $store.sidebar.open = !$store.sidebar.open; localStorage.setItem('sidebarOpen', $store.sidebar.open) } else { mobileSidebarOpen = !mobileSidebarOpen }"
                        :aria-expanded="isDesktop ? $store.sidebar.open : mobileSidebarOpen"
                        aria-controls="application-sidebar" aria-label="Buka navigasi"
                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="truncate font-semibold text-slate-700">
                        {{ $title ?? 'Dashboard' }}
                    </div>
                </div>

                {{-- relative: panel notifikasi menempel ke tepi kanan header, bukan ke tombol
                     loncengnya, supaya tidak keluar layar saat loncengnya di sisi kiri. --}}
                <div class="relative flex items-center gap-4" x-data="{ open: false }">
                    @include('layouts.partials.notification_bell')

                    @if (auth()->user()->hasRole('admin') && $layoutAvailableCompanies->isNotEmpty())
                        <form method="POST" action="{{ route('active-company.update') }}" class="hidden md:block">
                            @csrf
                            <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">
                                Perusahaan Aktif
                            </label>
                            <select name="company_id" onchange="this.form.submit()"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                @foreach ($layoutAvailableCompanies as $company)
                                    <option value="{{ $company->id }}" {{ (int) $layoutActiveCompanyId === (int) $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @elseif ($layoutActiveCompany)
                        <div class="hidden md:block rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Perusahaan</div>
                            <div class="text-sm font-medium text-slate-700">{{ $layoutActiveCompany->name }}</div>
                        </div>
                    @endif

                    <div class="relative">
                        <button type="button" @click="open = !open" class="flex items-center gap-3 focus:outline-none"
                            aria-label="Buka menu pengguna" :aria-expanded="open">
                            <span
                                class="text-sm font-medium text-slate-700 hidden md:block">{{ auth()->user()->name }}</span>
                            <div
                                class="h-9 w-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-sm overflow-hidden ring-2 ring-slate-100">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </button>

                        <div x-show="open" @click.away="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50 origin-top-right">

                            <div class="px-4 py-3 border-b border-slate-100 md:hidden">
                                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                            </div>

                            <a href="{{ route('profile.edit') }}"
                                class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Edit Profile / Password
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main data-app-main class="min-w-0 max-w-full p-4 md:p-6">
                @if ($errors->any())
                    <div data-validation-error-summary
                        class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <script type="application/json" data-validation-error-keys>@json($errors->keys())</script>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    <x-unsaved-changes-confirmation />
    <x-action-confirmation />
    <x-duplicate-confirmation />
    <x-delete-confirmation />
    <x-toast-notifications />
    @stack('scripts')
</body>

</html>
