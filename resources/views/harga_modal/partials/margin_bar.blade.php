{{--
    Setelan margin untuk seluruh baris di tab ini.

    Nilainya disimpan di localStorage peramban masing-masing, bukan di server:
    margin satu orang tidak boleh mengubah tampilan orang lain.

    Catatan cakupan biaya di bawahnya wajib ada. Harga modal dari inventory murni
    biaya bahan, jadi angka margin di layar bukan laba bersih -- tanpa keterangan
    ini hampir pasti terbaca begitu.
--}}
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <label for="margin-target" class="mb-1.5 block text-xs font-semibold text-slate-600">
                Margin target
            </label>

            <div class="flex items-center gap-2">
                <input id="margin-target" type="text" inputmode="decimal"
                       :value="persenTampil(marginGlobal)"
                       x-on:input="setMarginGlobal($event.target.value)"
                       value="{{ $marginBawaan }}"
                       class="w-24 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-right text-sm tabular-nums transition focus:border-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                <span class="text-sm font-medium text-slate-500">%</span>
            </div>
        </div>

        <p class="max-w-md text-xs leading-relaxed text-slate-500">
            Margin dihitung terhadap harga jual, bukan sebagai markup atas modal:
            <span class="font-mono text-slate-600">harga jual = modal &divide; (1 &minus; margin)</span>.
            Harga jual dibulatkan ke atas ke kelipatan Rp {{ number_format($kelipatan, 0, ',', '.') }},
            supaya marginnya tidak pernah jatuh di bawah target.
        </p>
    </div>

    <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
        <i class="ri-error-warning-line mt-0.5 text-base text-amber-600"></i>
        <p class="text-xs leading-relaxed text-amber-900">
            <span class="font-semibold">Harga modal hanya mencakup biaya bahan.</span>
            Belum termasuk ongkos kirim bahan, upah produksi, dan overhead. Jadi margin
            yang tampil di sini bukan laba bersih.
        </p>
    </div>
</div>
