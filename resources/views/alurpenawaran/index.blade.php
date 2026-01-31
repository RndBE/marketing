@extends('layouts.app')

@section('content')
    <div class="w-full ">

        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold">Alur Approval</h1>
            <button onclick="openCreateModal()"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Tambah Alur
            </button>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama Alur</th>
                        <th class="px-4 py-3 text-left">Jumlah Langkah</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($data as $alur)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $alur->nama }}</td>
                            <td class="px-4 py-3">{{ $alur->langkah->count() }} langkah</td>
                            <td class="px-4 py-3">
                                @if ($alur->status)
                                    <span class="px-2 py-1 text-ms bg-green-100 text-green-700 rounded-lg">Aktif</span>
                                @else
                                    <span class="px-2 py-1 text-ms bg-slate-100 text-slate-600 rounded-lg">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" class="px-3 py-1 bg-amber-500 text-white rounded-lg text-ms"
                                    data-id="{{ $alur->id }}" data-nama="{{ e($alur->nama) }}" data-status="{{ $alur->status }}"
                                    data-steps='@json($alur->langkah->sortBy('no_langkah')->values())'
                                    onclick="openEditModal(this)">
                                    Edit
                                </button>
                                <form action="{{ route('alurpenawaran.destroy', $alur->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 bg-red-600 text-white rounded-lg text-ms">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <tr class="bg-slate-50">
                            <td colspan="4" class="px-6 py-3">
                                <div class="flex flex-wrap gap-3 text-ms">
                                    @foreach ($alur->langkah->sortBy('urutan') as $step)
                                        <div class="bg-white border border-slate-300 px-3 py-2 rounded-lg shadow-sm">
                                            <div class="font-semibold">Langkah {{ $step->no_langkah }}</div>
                                            <div>{{ $step->nama_langkah }}</div>

                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>


            <div id="createModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
                onclick="closeCreateModal(event)">

                <div class="bg-white w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-xl p-6"
                    onclick="event.stopPropagation()">
                    <h2 class="text-lg font-semibold mb-4">Tambah Alur Penawaran</h2>
                    <form method="POST" action="{{ route('alurpenawaran.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="text-sm font-medium">Nama Alur</label>
                            <input type="text" name="nama" class="w-full border border-slate-300 rounded-lg p-2 mt-1"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="text-sm font-medium">Berlaku Untuk</label>
                            <select name="berlaku_untuk" class="w-full border border-slate-300 rounded-lg p-2 mt-1"
                                required>
                                <option value="penawaran">Penawaran</option>
                                <option value="penghapusan">penghapusan</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="text-sm font-medium">Status</label>
                            <select name="status" class="w-full border border-slate-300 rounded-lg p-2 mt-1">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <h3 class="font-semibold text-sm mb-2">Langkah Approval</h3>
                        <div id="stepsWrapper" class="space-y-3 mb-3"></div>
                        <button type="button" onclick="addStep()" class="text-indigo-600 text-sm mb-4">+ Tambah
                            Langkah</button>
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeCreateModal()"
                                class="px-4 py-2 bg-slate-200 rounded-lg">Batal</button>
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>


            <div id="editModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
                onclick="closeEditModal(event)">
                <div class="bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl p-6"
                    onclick="event.stopPropagation()">
                    <h2 class="text-lg font-semibold mb-4">Edit Alur Penawaran</h2>

                    <form id="editForm" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="text-sm font-medium">Nama Alur</label>
                            <input type="text" name="nama" id="edit_nama"
                                class="w-full border border-slate-300 rounded-lg p-2 mt-1" required>
                        </div>

                        <div class="mb-4">
                            <label class="text-sm font-medium">Status</label>
                            <select name="status" id="edit_status"
                                class="w-full border border-slate-300 rounded-lg p-2 mt-1">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>

                        <h3 class="font-semibold text-sm mb-2">Langkah Approval</h3>
                        <div id="editStepsWrapper" class="space-y-3 mb-3"></div>

                        <button type="button" onclick="addEditStep()" class="text-indigo-600 text-sm mb-4">+ Tambah
                            Langkah</button>

                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeEditModal()"
                                class="px-4 py-2 bg-slate-200 rounded-lg">Batal</button>
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Update</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden')
        }

        function closeCreateModal(e) {
            if (!e || e.target.id === 'createModal') {
                document.getElementById('createModal').classList.add('hidden')
            }
        }

        let stepCount = 0

        function addStep() {
            stepCount++

            const isFirst = stepCount === 1

            document.getElementById('stepsWrapper').insertAdjacentHTML('beforeend', `
                                        <div class="grid grid-cols-2 gap-2 border border-slate-300 p-3 rounded-lg relative step-item">

                                            ${!isFirst ? `
                                                                            <button type="button"
                                                                                onclick="removeStep(this)"
                                                                                class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs flex items-center justify-center">
                                                                                ✕
                                                                            </button>
                                                                            ` : ''}

                                            <input type="text" name="langkah[${stepCount}][nama_langkah]"
                                                placeholder="Nama Langkah"
                                                class="border border-slate-300 rounded-lg p-2 col-span-2" required>

                                            <select name="langkah[${stepCount}][user_id]"
                                                class="border border-slate-300 rounded-lg p-2">
                                                <option value="">-- Pilih Approver --</option>
                                                @foreach ($users as $u)
                                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                                @endforeach
                                            </select>

                                            <select name="langkah[${stepCount}][harus_semua]"
                                                class="border border-slate-300 rounded-lg p-2">
                                                <option value="1">Semua harus setuju</option>
                                            </select>
                                        </div>
                                    `)
        }

        function removeStep(btn) {
            btn.closest('.step-item').remove()
        }

        const userOptions = `
                                    <option value="">-- Pilih Approver --</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                `;

        function closeEditModal(e) {
            if (!e || e.target.id === 'editModal') {
                document.getElementById('editModal').classList.add('hidden')
            }
        }

        function openEditModal(btn) {
            const id = btn.dataset.id
            const nama = btn.dataset.nama
            const status = btn.dataset.status
            const steps = JSON.parse(btn.dataset.steps || '[]')

            document.getElementById('editModal').classList.remove('hidden')
            document.getElementById('edit_nama').value = nama
            document.getElementById('edit_status').value = status

            document.getElementById('editForm').action = `{{ url('/alur-penawaran') }}/${id}`

            const wrap = document.getElementById('editStepsWrapper')
            wrap.innerHTML = ''

            steps.forEach((s, idx) => {
                wrap.insertAdjacentHTML('beforeend', renderEditStep(idx, s, idx !== 0))
                const block = wrap.lastElementChild
                block.querySelector('select[name="steps[' + idx + '][user_id]"]').value = s.user_id ?? ''
                block.querySelector('select[name="steps[' + idx + '][harus_semua]"]').value = String(s
                    .harus_semua ?? 0)
            })
        }

        function renderEditStep(index, s, removable) {
            const stepId = s.id ?? ''
            const nama = (s.nama_langkah ?? '').replaceAll('"', '&quot;')

            return `
                                        <div class="grid grid-cols-2 gap-2 border border-slate-300 p-3 rounded-lg relative step-item">
                                            ${removable ? `
                                                    <button type="button" onclick="removeEditStep(this)"
                                                        class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs flex items-center justify-center">✕</button>
                                                ` : ''}

                                            <input type="hidden" name="steps[${index}][id]" value="${stepId}">

                                            <input type="text" name="steps[${index}][nama_langkah]"
                                                value="${nama}"
                                                class="border border-slate-300 rounded-lg p-2 col-span-2" required>

                                            <select name="steps[${index}][user_id]" class="border border-slate-300 rounded-lg p-2 col-span-2">
                                                ${userOptions}
                                            </select>

                                            <select name="steps[${index}][harus_semua]" class="border border-slate-300 rounded-lg p-2 col-span-2">
                                                <option value="1">Semua harus setuju</option>
                                            </select>
                                        </div>
                                    `
        }

        function addEditStep() {
            const wrap = document.getElementById('editStepsWrapper')
            const index = wrap.querySelectorAll('.step-item').length
            wrap.insertAdjacentHTML('beforeend', renderEditStep(index, {}, index !== 0))
        }

        function removeEditStep(btn) {
            btn.closest('.step-item').remove()
        }
    </script>
@endsection
