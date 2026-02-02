@extends('layouts.app', ['title' => 'Edit Bundle'])

@section('content')
    <div class="w-full">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Edit Bundle</h1>
            <div class="text-sm text-slate-500">{{ $product->nama }}</div>
        </div>

        <form method="POST" action="{{ route('price_list.update', $product->id) }}" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold mb-1">Kode (opsional)</label>
                <input name="kode" value="{{ $product->kode }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Nama</label>
                <input name="nama" value="{{ $product->nama }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Satuan</label>
                <input name="satuan" value="{{ old('satuan', $product->satuan ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white" required>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="4" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">{{ $product->deskripsi }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input id="is_active" type="checkbox" name="is_active" value="1"
                    {{ $product->is_active ? 'checked' : '' }} class="rounded border-slate-300">
                <label for="is_active" class="text-sm">Aktif</label>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('price_list.show', $product->id) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Kembali</a>
                <button
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button>
            </div>
        </form>
        <hr class="my-8 border-slate-200">

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="text-sm font-semibold">Item dalam Bundle</div>
                    <div class="text-xs text-slate-500">Tambah / edit / hapus tanpa reload</div>
                </div>
                <a href="{{ route('price_list.show', $product->id) }}"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Preview</a>
            </div>

            <form id="add-detail-form" method="POST" action="{{ route('price_list.details.add', $product->id) }}"
                class="space-y-3">
                @csrf
                {{-- Searchable Komponen Select --}}
                <div class="relative">
                    <label class="block text-xs font-semibold mb-1">Pilih dari Komponen (opsional)</label>
                    <input type="text" id="komponen-search" placeholder="Ketik untuk cari komponen..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" autocomplete="off">
                    <input type="hidden" id="komponen-select" value="">
                    <div id="komponen-dropdown"
                        class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-10 gap-2">
                    <div class="md:col-span-3">
                        <input name="nama" id="detail-nama" placeholder="Nama"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" required>
                    </div>
                    <div class="md:col-span-3">
                        <input name="spesifikasi" id="detail-spesifikasi" placeholder="Spesifikasi (opsional)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-1">
                        <input name="qty" id="detail-qty" value="1" inputmode="decimal"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" required>
                    </div>
                    <div class="md:col-span-1">
                        <input name="satuan" id="detail-satuan" placeholder="Satuan"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <input name="harga" id="detail-harga" value="0" inputmode="numeric"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm rupiah-input"
                            required>
                    </div>

                    <div class="md:col-span-10 flex justify-end">
                        <button
                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                            Tambah Item
                        </button>
                    </div>
                </div>
            </form>

            <div id="details-wrap" class="mt-4">
                @include('price_list.partials.details_table', ['product' => $product, 'unitPrice' => 0])
            </div>
        </div>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            let komponenData = []
            const searchInput = document.getElementById('komponen-search')
            const dropdown = document.getElementById('komponen-dropdown')
            const hiddenSelect = document.getElementById('komponen-select')

            function formatRupiahDigits(digits) {
                if (!digits) return ''
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
            }

            function bindRupiahInput(input) {
                if (!input || input.dataset.rupiahBound) return
                input.dataset.rupiahBound = '1'
                input.addEventListener('input', () => {
                    const digits = input.value.replace(/\D/g, '')
                    input.value = formatRupiahDigits(digits)
                })
                const initial = input.value.replace(/\D/g, '')
                input.value = formatRupiahDigits(initial)
            }

            function initRupiahInputs(root = document) {
                root.querySelectorAll('input.rupiah-input').forEach(bindRupiahInput)
            }

            function normalizeRupiahInputs(form) {
                if (!form) return
                form.querySelectorAll('input.rupiah-input').forEach(input => {
                    input.value = input.value.replace(/\D/g, '')
                })
            }

            // Load komponen list on page load
            async function loadKomponen() {
                try {
                    const res = await fetch('{{ route('api.komponen.list') }}')
                    if (res.ok) {
                        komponenData = await res.json()
                    }
                } catch (e) {
                    console.error('Failed to load komponen:', e)
                }
            }
            loadKomponen()

            // Render dropdown items
            function renderDropdown(items) {
                dropdown.innerHTML = ''
                if (items.length === 0) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">Tidak ditemukan</div>'
                    return
                }
                items.forEach(k => {
                    const div = document.createElement('div')
                    div.className = 'px-3 py-2 text-sm hover:bg-slate-100 cursor-pointer'
                    div.textContent = k.kode ? `[${k.kode}] ${k.nama}` : k.nama
                    div.dataset.id = k.id
                    div.addEventListener('click', () => selectKomponen(k))
                    dropdown.appendChild(div)
                })
            }

            // Select komponen and fill form
            function selectKomponen(k) {
                searchInput.value = k.kode ? `[${k.kode}] ${k.nama}` : k.nama
                hiddenSelect.value = k.id
                dropdown.classList.add('hidden')

                document.getElementById('detail-nama').value = k.nama || ''
                document.getElementById('detail-spesifikasi').value = k.spesifikasi || ''
                document.getElementById('detail-satuan').value = k.satuan || ''
                const hargaDigits = String(k.harga || 0).replace(/\D/g, '')
                document.getElementById('detail-harga').value = formatRupiahDigits(hargaDigits)
            }

            // Search input events
            searchInput.addEventListener('focus', () => {
                const q = searchInput.value.toLowerCase().trim()
                const filtered = komponenData.filter(k =>
                    k.nama.toLowerCase().includes(q) ||
                    (k.kode && k.kode.toLowerCase().includes(q))
                )
                renderDropdown(filtered)
                dropdown.classList.remove('hidden')
            })

            searchInput.addEventListener('input', () => {
                const q = searchInput.value.toLowerCase().trim()
                const filtered = komponenData.filter(k =>
                    k.nama.toLowerCase().includes(q) ||
                    (k.kode && k.kode.toLowerCase().includes(q))
                )
                renderDropdown(filtered)
                dropdown.classList.remove('hidden')
                hiddenSelect.value = '' // Reset selection when typing
            })

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#komponen-search') && !e.target.closest('#komponen-dropdown')) {
                    dropdown.classList.add('hidden')
                }
            })

            document.getElementById('add-detail-form').addEventListener('submit', async (e) => {
                e.preventDefault()
                const form = e.currentTarget
                const url = form.getAttribute('action')
                normalizeRupiahInputs(form)
                const fd = new FormData(form)

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: fd
                })

                if (!res.ok) return

                const html = await res.text()
                document.getElementById('details-wrap').innerHTML = html

                form.reset()
                document.getElementById('detail-qty').value = '1'
                document.getElementById('detail-harga').value = '0'
                searchInput.value = ''
                hiddenSelect.value = ''
                initRupiahInputs(form)
            })

            document.addEventListener('submit', async (e) => {
                const form = e.target
                if (!form.matches('.ajax-detail-form')) return

                e.preventDefault()

                const confirmText = form.getAttribute('data-confirm')
                if (confirmText && !window.confirm(confirmText)) return

                const url = form.getAttribute('action')
                const m = (form.getAttribute('data-method') || 'POST').toUpperCase()
                normalizeRupiahInputs(form)
                const fd = new FormData(form)
                if (m !== 'POST') fd.append('_method', m)

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: fd
                })

                if (!res.ok) return

                const html = await res.text()
                document.getElementById('details-wrap').innerHTML = html
                initRupiahInputs(document.getElementById('details-wrap'))
            })

            initRupiahInputs()
        </script>


    </div>
@endsection
