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

                <select id="pic-sort-select" name="sort"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    <option value="latest" @selected(($sort ?? 'latest') === 'latest')>Terbaru Ditambahkan</option>
                    <option value="oldest" @selected(($sort ?? 'latest') === 'oldest')>Terlama Ditambahkan</option>
                    <option value="name_asc" @selected(($sort ?? 'latest') === 'name_asc')>Nama A-Z</option>
                </select>

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
            const sortSelect = document.getElementById('pic-sort-select');
            const wrapper = document.getElementById('pic-table-wrapper');
            const resetButton = document.getElementById('pic-search-reset');

            if (!form || !input || !sortSelect || !wrapper || !resetButton) {
                return;
            }

            let timer = null;
            let lastFetchToken = 0;

            const buildUrl = (baseUrl, overrides = {}) => {
                const url = new URL(baseUrl || form.action, window.location.origin);
                const params = new URLSearchParams(new FormData(form));
                const shouldResetPage = overrides.resetPage ?? !baseUrl;
                const query = params.get('q') ?? '';
                const sort = params.get('sort') ?? 'latest';

                if (query !== '') {
                    url.searchParams.set('q', query);
                } else {
                    url.searchParams.delete('q');
                }

                if (sort !== '' && sort !== 'latest') {
                    url.searchParams.set('sort', sort);
                } else {
                    url.searchParams.delete('sort');
                }

                if (shouldResetPage) {
                    url.searchParams.delete('page');
                }

                url.searchParams.set('_partial', '1');

                return url;
            };

            const syncBrowserUrl = (requestedUrl) => {
                const url = new URL(requestedUrl.toString());
                url.searchParams.delete('_partial');
                window.history.replaceState({}, '', url);
            };

            const fetchTable = async (baseUrl = null, options = {}) => {
                const token = ++lastFetchToken;
                const url = buildUrl(baseUrl, options);

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
                    const fallbackUrl = new URL(url.toString());
                    fallbackUrl.searchParams.delete('_partial');
                    window.location.href = fallbackUrl.toString();
                } finally {
                    if (token === lastFetchToken) {
                        wrapper.classList.remove('opacity-60', 'pointer-events-none');
                    }
                }
            };

            input.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => fetchTable(null, {
                    resetPage: true
                }), 300);
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                window.clearTimeout(timer);
                fetchTable(null, {
                    resetPage: true
                });
            });

            wrapper.addEventListener('click', (event) => {
                const link = event.target.closest('nav a[href]');

                if (!link) {
                    return;
                }

                event.preventDefault();
                fetchTable(link.href, {
                    resetPage: false
                });
            });

            resetButton.addEventListener('click', () => {
                input.value = '';
                sortSelect.value = 'latest';
                window.clearTimeout(timer);
                fetchTable(form.action, {
                    resetPage: true
                });
            });

            sortSelect.addEventListener('change', () => {
                window.clearTimeout(timer);
                fetchTable(null, {
                    resetPage: true
                });
            });
        })();
    </script>
@endpush
