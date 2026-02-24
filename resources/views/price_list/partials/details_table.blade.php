@php
    $unitPrice = 0;
    foreach ($product->details as $d) {
        $sub = (int) ($d->subtotal ?? 0);
        if ($sub <= 0) {
            $q = (float) ($d->qty ?? 0);
            $h = (int) ($d->harga ?? 0);
            $sub = (int) round($q * $h);
        }
        $unitPrice += $sub;
    }
@endphp

<div class="flex items-center justify-between mb-2">
    <div class="text-sm text-slate-700">
        Harga Satuan Bundle: <span class="font-semibold">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span>
    </div>
</div>

<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border border-slate-200">
            <tr>
                <th class="px-3 py-2 text-left font-semibold">No</th>
                <th class="px-3 py-2 text-left font-semibold">Nama</th>
                <th class="px-3 py-2 text-left font-semibold">Spesifikasi</th>
                <th class="px-3 py-2 text-right font-semibold">Qty</th>
                <th class="px-3 py-2 text-left font-semibold">Satuan</th>
                <th class="px-3 py-2 text-right font-semibold">Harga</th>
                <th class="px-3 py-2 text-right font-semibold">Subtotal</th>
                <th class="px-3 py-2 text-right font-semibold">Aksi</th>
            </tr>
        </thead>

        <tbody class="border border-slate-200 divide-y divide-slate-100" id="detail-sortable">
            @forelse($product->details as $d)
                @php
                    $rowSub = (int) ($d->subtotal ?? 0);
                    if ($rowSub <= 0) {
                        $rowSub = (int) round(((float) $d->qty) * ((int) $d->harga));
                    }
                @endphp

                {{-- VIEW ROW --}}
                <tr class="detail-row pl-detail-view-{{ $d->id }}" draggable="true" data-detail-id="{{ $d->id }}">
                    <td class="px-3 py-2 text-slate-500 text-xs">{{ $d->urutan }}</td>
                    <td class="px-3 py-2">
                        <div class="font-semibold">{{ $d->nama }}</div>
                    </td>
                    <td class="px-3 py-2">
                        <div class="text-xs text-slate-600 whitespace-pre-wrap">{{ $d->spesifikasi }}</div>
                    </td>
                    <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format((float) $d->qty, 2, ',', '.') }}
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $d->satuan }}</td>
                    <td class="px-3 py-2 text-right whitespace-nowrap">Rp {{ number_format((int) $d->harga, 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">Rp
                        {{ number_format($rowSub, 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-right whitespace-nowrap">
                        <button type="button" onclick="plDetailEdit({{ $d->id }})"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                            Edit
                        </button>
                    </td>
                </tr>

                {{-- EDIT ROW --}}
                <tr class="pl-detail-edit-{{ $d->id }} hidden bg-slate-50">
                    <td class="px-2 py-2 text-slate-400 text-xs align-top pt-3">{{ $d->urutan }}</td>
                    <td class="px-2 py-1.5" colspan="5">
                        <form class="ajax-detail-form" id="pl-detail-form-{{ $d->id }}"
                            action="{{ route('price_list.details.update', [$product->id, $d->id]) }}" method="POST"
                            data-method="PUT">
                            @csrf
                            <div class="flex flex-col gap-1.5">
                                <input name="nama" value="{{ $d->nama }}" placeholder="Nama"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400"
                                    required>
                                <textarea name="spesifikasi" rows="2" placeholder="Spesifikasi (opsional)"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-1 focus:ring-slate-400">{{ $d->spesifikasi }}</textarea>
                                <div class="flex gap-1.5">
                                    <input name="qty" value="{{ $d->qty }}" placeholder="Qty"
                                        class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400"
                                        required>
                                    <input name="satuan" value="{{ $d->satuan }}" placeholder="Satuan"
                                        class="w-24 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-slate-400">
                                    <input name="harga" value="{{ $d->harga }}" placeholder="Harga" inputmode="numeric"
                                        class="flex-1 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm rupiah-input focus:outline-none focus:ring-1 focus:ring-slate-400"
                                        required>
                                </div>
                            </div>
                        </form>
                    </td>
                    <td class="px-2 py-1.5 text-right font-semibold text-slate-400 whitespace-nowrap align-top pt-3">
                        Rp {{ number_format($rowSub, 0, ',', '.') }}
                    </td>
                    <td class="px-2 py-1.5 align-top whitespace-nowrap">
                        <div class="flex flex-col gap-1 items-end">
                            <button type="submit" form="pl-detail-form-{{ $d->id }}"
                                class="w-full rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                                Simpan
                            </button>
                            <button type="button" onclick="plDetailCancel({{ $d->id }})"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">
                                Batal
                            </button>
                            <form class="ajax-detail-form w-full"
                                action="{{ route('price_list.details.delete', [$product->id, $d->id]) }}" method="POST"
                                data-method="DELETE" data-confirm="Hapus item ini?">
                                @csrf
                                <button type="submit"
                                    class="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-slate-500">Belum ada item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    // Re-init inline edit functions setiap kali partial ini di-render ulang via AJAX
    window.plDetailEdit = function (id) {
        document.querySelectorAll('.pl-detail-view-' + id).forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.pl-detail-edit-' + id).forEach(el => el.classList.remove('hidden'));
        const form = document.getElementById('pl-detail-form-' + id);
        if (form) {
            const namaInput = form.querySelector('input[name="nama"]');
            if (namaInput) namaInput.focus();
            // init rupiah input
            form.querySelectorAll('input.rupiah-input').forEach(inp => {
                if (inp.dataset.rupiahBound) return;
                inp.dataset.rupiahBound = '1';
                inp.addEventListener('input', () => {
                    const digits = inp.value.replace(/\D/g, '');
                    inp.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                });
                const init = inp.value.replace(/\D/g, '');
                inp.value = init.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            });
        }
    };

    window.plDetailCancel = function (id) {
        document.querySelectorAll('.pl-detail-edit-' + id).forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.pl-detail-view-' + id).forEach(el => el.classList.remove('hidden'));
    };
</script>