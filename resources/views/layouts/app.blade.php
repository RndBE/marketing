<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen flex">
        @include('layouts.partials.sidebar')
        <div class="flex-1 flex flex-col md:ml-64">

            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div class="font-semibold text-slate-700">
                    {{ $title ?? 'Dashboard' }}
                </div>

                <div class="flex items-center gap-4" x-data="{ open: false }">
                    <div class="relative">
                        <button @click="open = !open" class="flex items-center gap-3 focus:outline-none">
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

            <main class="p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>