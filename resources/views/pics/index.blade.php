@extends('layouts.app', ['title' => 'Data PIC'])

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-lg font-semibold">Daftar PIC</h1>
            <a href="{{ route('pics.create') }}"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Tambah PIC
            </a>
        </div>

        <form method="GET" action="{{ route('pics.index') }}" id="pic-search-form"
            class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex flex-col gap-3 md:flex-row">
                <input id="pic-search-input" name="q" value="{{ $q ?? '' }}"
                    placeholder="Cari nama, jabatan, instansi, email, HP, atau alamat..."
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                <div class="flex gap-2">
                    <button type="submit"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">
                        Cari
                    </button>
                    <button type="button" id="pic-search-reset"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">
                        Reset
                    </button>
                </div>
            </div>
        </form>

        <div id="pic-table-wrapper">
            @include('pics._table', ['data' => $data])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('pic-search-form');
            const input = document.getElementById('pic-search-input');
            const wrapper = document.getElementById('pic-table-wrapper');
            const resetButton = document.getElementById('pic-search-reset');

            if (!form || !input || !wrapper || !resetButton) {
                return;
            }

            let timer = null;
            let lastFetchToken = 0;

            const buildUrl = (baseUrl) => {
                const url = new URL(baseUrl || form.action, window.location.origin);
                const params = new URLSearchParams(new FormData(form));

                url.search = '';

                params.forEach((value, key) => {
                    if (value !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                url.searchParams.set('_partial', '1');

                return url;
            };

            const syncBrowserUrl = (requestedUrl) => {
                const url = new URL(requestedUrl.toString());
                url.searchParams.delete('_partial');
                window.history.replaceState({}, '', url);
            };

            const fetchTable = async (baseUrl = null) => {
                const token = ++lastFetchToken;
                const url = buildUrl(baseUrl);

                wrapper.classList.add('opacity-60', 'pointer-events-none');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat data PIC.');
                    }

                    const html = await response.text();

                    if (token !== lastFetchToken) {
                        return;
                    }

                    wrapper.innerHTML = html;
                    syncBrowserUrl(url);
                } catch (error) {
                    console.error(error);
                } finally {
                    if (token === lastFetchToken) {
                        wrapper.classList.remove('opacity-60', 'pointer-events-none');
                    }
                }
            };

            input.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => fetchTable(), 300);
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                window.clearTimeout(timer);
                fetchTable();
            });

            wrapper.addEventListener('click', (event) => {
                const link = event.target.closest('nav a[href]');

                if (!link) {
                    return;
                }

                event.preventDefault();
                fetchTable(link.href);
            });

            resetButton.addEventListener('click', () => {
                input.value = '';
                window.clearTimeout(timer);
                fetchTable(form.action);
            });
        })();
    </script>
@endpush
