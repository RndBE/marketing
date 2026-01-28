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

        <tbody class="border border-slate-200 divide-y divide-slate-100">
            @forelse($product->details as $d)
                @php
                    $rowSub = (int) ($d->subtotal ?? 0);
                    if ($rowSub <= 0) {
                        $rowSub = (int) round(((float) $d->qty) * ((int) $d->harga));
                    }
                @endphp

                <tr>
                    <td class="px-3 py-2">{{ $d->urutan }}</td>

                    <td class="px-3 py-2">
                        <div class="font-semibold">{{ $d->nama }}</div>
                    </td>

                    <td class="px-3 py-2">
                        <div class="text-xs text-slate-600 whitespace-pre-wrap">{{ $d->spesifikasi }}</div>
                    </td>

                    <td class="px-3 py-2 text-right">{{ number_format((float) $d->qty, 2, ',', '.') }}</td>
                    <td class="px-3 py-2">{{ $d->satuan }}</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format((int) $d->harga, 0, ',', '.') }}</td>

                    <td class="px-3 py-2 text-right font-semibold">Rp {{ number_format($rowSub, 0, ',', '.') }}</td>

                    <td class="px-3 py-2 text-right">
                        <details class="inline-block text-left">
                            <summary
                                class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                Edit
                            </summary>

                            <div
                                class="mt-2 w-[420px] rounded-2xl border border-slate-200 bg-white p-4 shadow-lg space-y-2">
                                <form class="ajax-detail-form space-y-2"
                                    action="{{ route('price_list.details.update', [$product->id, $d->id]) }}"
                                    method="POST" data-method="PUT">
                                    @csrf

                                    <input name="nama" value="{{ $d->nama }}"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>

                                    <textarea name="spesifikasi" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ $d->spesifikasi }}</textarea>

                                    <div class="grid grid-cols-3 gap-2">
                                        <input name="qty" value="{{ $d->qty }}"
                                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                            required>
                                        <input name="satuan" value="{{ $d->satuan }}"
                                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                        <input name="harga" value="{{ $d->harga }}"
                                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                            required>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                            Simpan
                                        </button>
                                    </div>
                                </form>

                                <form class="ajax-detail-form"
                                    action="{{ route('price_list.details.delete', [$product->id, $d->id]) }}"
                                    method="POST" data-method="DELETE" data-confirm="Hapus item ini?">
                                    @csrf
                                    <button type="submit"
                                        class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </details>
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
