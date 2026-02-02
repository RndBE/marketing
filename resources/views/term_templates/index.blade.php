@extends('layouts.app', ['title' => 'Terms Template'])

@section('content')
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold">Template Keterangan</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-1 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="font-semibold mb-3">Tambah Keterangan</div>
                <form method="POST" action="{{ route('term_templates.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold mb-1">Parent (opsional)</label>
                        <select name="parent_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                            <option value="">Jadikan utama</option>
                            @foreach ($options as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Aktif</label>
                            <select name="is_active"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <option value="1" selected>Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">Isi</label>
                        <textarea name="isi" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>

                    <button
                        class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Tambah
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <form method="GET" class="flex gap-2">
                    <input name="q" value="{{ $q }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Cari isi/judul...">
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Cari
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="font-semibold mb-3">Daftar (Tree)</div>

                <div class="space-y-2 term-template-list" data-parent-id="">
                    @forelse ($roots as $t)
                        @include('term_templates.partials.node', [
                            't' => $t,
                            'termsByParent' => $termsByParent,
                            'level' => 0,
                        ])
                    @empty
                        <div class="text-sm text-slate-500">Belum ada template.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function getCsrfToken() {
            const input = document.querySelector('input[name="_token"]');
            return input ? input.value : '';
        }

        async function saveTemplateOrder(listEl) {
            const parentId = listEl.dataset.parentId || '';
            const ids = Array.from(listEl.querySelectorAll(':scope > .term-template-node'))
                .map(el => parseInt(el.dataset.templateId, 10))
                .filter(Boolean);
            if (!ids.length) return;

            const payload = {
                parent_id: parentId === '' ? null : parseInt(parentId, 10),
                ids
            };

            try {
                const res = await fetch('{{ route("term_templates.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(payload)
                });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Gagal memperbarui urutan');
                }
            } catch (e) {
                console.error('Reorder error:', e);
                alert('Terjadi kesalahan koneksi');
            }
        }

        function initTemplateDragDrop(root = document) {
            root.querySelectorAll('.term-template-node').forEach(node => {
                node.addEventListener('dragstart', (e) => {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', node.dataset.templateId || '');
                    node.classList.add('opacity-50');
                });
                node.addEventListener('dragend', () => {
                    node.classList.remove('opacity-50');
                });
            });

            root.querySelectorAll('.term-template-list').forEach(list => {
                list.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    const dragging = root.querySelector('.term-template-node.opacity-50');
                    if (!dragging) return;
                    const after = Array.from(list.querySelectorAll(':scope > .term-template-node'))
                        .find(el => {
                            const rect = el.getBoundingClientRect();
                            return e.clientY < rect.top + rect.height / 2;
                        });
                    if (after) {
                        list.insertBefore(dragging, after);
                    } else {
                        list.appendChild(dragging);
                    }
                });
                list.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    await saveTemplateOrder(list);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initTemplateDragDrop();
        });
    </script>
@endsection
