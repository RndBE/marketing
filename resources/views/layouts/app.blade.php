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

        {{-- SIDEBAR --}}
        @include('layouts.partials.sidebar')

        {{-- CONTENT --}}
        <div class="flex-1 flex flex-col md:ml-64">

            {{-- TOPBAR --}}
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div class="font-semibold text-slate-700">
                    {{ $title ?? 'Dashboard' }}
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-sm text-slate-600">
                        {{ auth()->user()->name }}
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-rose-600 hover:underline">
                            Logout
                        </button>
                    </form>
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
