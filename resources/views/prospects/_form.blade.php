@csrf
@if (isset($prospect))
    @method('PUT')
@endif

@php
    $currentProspect = $prospect ?? null;
    $selectedPicId = (string) old('pic_id', $currentProspect?->pic_id ?? '');
    $manualInstansi = old('instansi', $currentProspect?->instansi ?? '');
    $statusSearchOptions = collect($statusOptions)
        ->map(fn($label, $value) => ['id' => (string) $value, 'label' => $label])
        ->values()
        ->all();
    $outcomeSearchOptions = collect($outcomeOptions)
        ->map(fn($label, $value) => ['id' => (string) $value, 'label' => $label])
        ->values()
        ->all();
    $sourceSearchOptions = collect($sourceOptions)
        ->map(fn($label, $value) => ['id' => (string) $value, 'label' => $label])
        ->values()
        ->all();
@endphp

<div class="" x-data="{
    selectedPicId: @js($selectedPicId),
    manualInstansi: @js($manualInstansi),
    picOptions: @js($picOptions),
    get selectedPic() {
        return this.selectedPicId && this.picOptions[this.selectedPicId] ? this.picOptions[this.selectedPicId] : null;
    }
}">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1">Tanggal Lead</label>
                <input type="date" name="tanggal_lead"
                    value="{{ old('tanggal_lead', $currentProspect?->tanggal_lead?->format('Y-m-d')) }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Judul Prospek</label>
                <input name="judul" value="{{ old('judul', $currentProspect?->judul ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required
                    placeholder="Contoh: Pengadaan Telemetri AWLR BBWS Serayu Opak">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Sumber Lead</label>
                <div x-data="searchableSelect({
                    options: @js($sourceSearchOptions),
                    selectedId: @js((string) old('sumber_lead', $currentProspect?->sumber_lead ?? '')),
                    placeholder: 'Cari sumber lead...',
                    emptyText: 'Sumber lead tidak ditemukan.'
                })" class="relative">
                    <input type="hidden" name="sumber_lead" :value="selectedId">

                    <div class="relative">
                        <input type="text" x-model="query" :placeholder="placeholder" @focus="openOptions()"
                            @click="openOptions()" @input="onInput()" @keydown.arrow-down.prevent="moveHighlight(1)"
                            @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                            @keydown.escape.prevent="open = false" @blur="setTimeout(() => closeOptions(), 120)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-20 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                            <button type="button" x-show="selectedId || query" @click="clear()"
                                class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <i class="ri-close-line text-base"></i>
                            </button>
                            <i class="ri-arrow-down-s-line text-lg text-slate-400"></i>
                        </div>
                    </div>

                    <div x-show="open" x-cloak
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="max-h-64 overflow-y-auto py-1">
                            <template x-if="filteredOptions.length === 0">
                                <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
                            </template>

                            <template x-for="(option, index) in filteredOptions" :key="option.id">
                                <button type="button" @mousedown.prevent="choose(option)"
                                    @mouseenter="highlightedIndex = index"
                                    :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' :
                                        'text-slate-700'"
                                    class="flex w-full items-start px-3 py-2 text-left text-sm hover:bg-slate-50">
                                    <span x-text="option.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">PIC</label>
                <div x-data="searchableSelect({
                    options: @js($picSearchOptions),
                    selectedId: @js($selectedPicId),
                    placeholder: 'Cari PIC dari master...',
                    emptyText: 'PIC tidak ditemukan.'
                })" @searchable-select-change="selectedPicId = $event.detail.value"
                    class="relative">
                    <input type="hidden" name="pic_id" :value="selectedId">

                    <div class="relative">
                        <input type="text" x-model="query" :placeholder="placeholder" @focus="openOptions()"
                            @click="openOptions()" @input="onInput()" @keydown.arrow-down.prevent="moveHighlight(1)"
                            @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                            @keydown.escape.prevent="open = false" @blur="setTimeout(() => closeOptions(), 120)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-20 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                            <button type="button" x-show="selectedId || query" @click="clear()"
                                class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <i class="ri-close-line text-base"></i>
                            </button>
                            <i class="ri-arrow-down-s-line text-lg text-slate-400"></i>
                        </div>
                    </div>

                    <div x-show="open" x-cloak
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="max-h-64 overflow-y-auto py-1">
                            <template x-if="filteredOptions.length === 0">
                                <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
                            </template>

                            <template x-for="(option, index) in filteredOptions" :key="option.id">
                                <button type="button" @mousedown.prevent="choose(option)"
                                    @mouseenter="highlightedIndex = index"
                                    :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' :
                                        'text-slate-700'"
                                    class="flex w-full items-start px-3 py-2 text-left text-sm hover:bg-slate-50">
                                    <span x-text="option.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4" x-show="selectedPic">
                <div class="text-sm font-semibold text-emerald-800">Data PIC diambil dari master PIC</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 text-sm">
                    <div class="md:col-span-2">
                        <div class="text-xs text-emerald-700">Instansi</div>
                        <div class="font-medium text-slate-800" x-text="selectedPic?.instansi || '-'"></div>
                    </div>
                    <div>
                        <div class="text-xs text-emerald-700">Nama</div>
                        <div class="font-medium text-slate-800" x-text="selectedPic?.nama || '-'"></div>
                    </div>
                    <div>
                        <div class="text-xs text-emerald-700">Jabatan</div>
                        <div class="font-medium text-slate-800" x-text="selectedPic?.jabatan || '-'"></div>
                    </div>
                    <div>
                        <div class="text-xs text-emerald-700">HP / WA</div>
                        <div class="font-medium text-slate-800" x-text="selectedPic?.no_hp || '-'"></div>
                    </div>
                    <div>
                        <div class="text-xs text-emerald-700">Email</div>
                        <div class="font-medium text-slate-800" x-text="selectedPic?.email || '-'"></div>
                    </div>
                </div>
                <input type="hidden" name="instansi" :disabled="!selectedPic" :value="selectedPic?.instansi || ''">
                <input type="hidden" name="nama_pic" :disabled="!selectedPic" :value="selectedPic?.nama || ''">
                <input type="hidden" name="jabatan_pic" :disabled="!selectedPic" :value="selectedPic?.jabatan || ''">
                <input type="hidden" name="no_hp" :disabled="!selectedPic" :value="selectedPic?.no_hp || ''">
                <input type="hidden" name="email" :disabled="!selectedPic" :value="selectedPic?.email || ''">
            </div>

            <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4" x-show="!selectedPic">
                <div class="text-sm font-semibold text-slate-800">Input manual hanya jika PIC belum ada di master</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold mb-1">Instansi</label>
                        <input name="instansi" value="{{ $manualInstansi }}" @input="manualInstansi = $event.target.value"
                            :disabled="!!selectedPic"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">Nama PIC</label>
                        <input name="nama_pic" value="{{ old('nama_pic', $currentProspect?->nama_pic ?? '') }}"
                            :disabled="!!selectedPic"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">Jabatan PIC</label>
                        <input name="jabatan_pic" value="{{ old('jabatan_pic', $currentProspect?->jabatan_pic ?? '') }}"
                            :disabled="!!selectedPic"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">HP / WA</label>
                        <input name="no_hp" value="{{ old('no_hp', $currentProspect?->no_hp ?? '') }}"
                            :disabled="!!selectedPic"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1">Email</label>
                        <input type="email" name="email"
                            value="{{ old('email', $currentProspect?->email ?? '') }}" :disabled="!!selectedPic"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Lokasi</label>
                <input name="lokasi" value="{{ old('lokasi', $currentProspect?->lokasi ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Produk</label>
                <input name="produk" value="{{ old('produk', $currentProspect?->produk ?? '') }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Potensi Nilai</label>
                <div class="relative"
                    x-data="currencyInput(@js((string) old('potensi_nilai', $currentProspect?->potensi_nilai ?? '')))">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 inline-flex items-center pl-3 text-sm font-medium text-slate-500">
                        Rp
                    </span>
                    <input type="hidden" name="potensi_nilai" :value="numericValue">
                    <input type="text" x-model="displayValue" @input="onInput($event)" inputmode="numeric"
                        class="w-full rounded-xl border border-slate-200 py-2 pl-11 pr-3 text-sm"
                        placeholder="0">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Status</label>
                <div x-data="searchableSelect({
                    options: @js($statusSearchOptions),
                    selectedId: @js((string) old('status', $currentProspect?->status ?? 'new')),
                    placeholder: 'Cari status...',
                    emptyText: 'Status tidak ditemukan.'
                })" class="relative">
                    <input type="hidden" name="status" :value="selectedId">

                    <div class="relative">
                        <input type="text" x-model="query" :placeholder="placeholder" @focus="openOptions()"
                            @click="openOptions()" @input="onInput()" @keydown.arrow-down.prevent="moveHighlight(1)"
                            @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                            @keydown.escape.prevent="open = false" @blur="setTimeout(() => closeOptions(), 120)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-20 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                            <button type="button" x-show="selectedId || query" @click="clear()"
                                class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <i class="ri-close-line text-base"></i>
                            </button>
                            <i class="ri-arrow-down-s-line text-lg text-slate-400"></i>
                        </div>
                    </div>

                    <div x-show="open" x-cloak
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="max-h-64 overflow-y-auto py-1">
                            <template x-if="filteredOptions.length === 0">
                                <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
                            </template>

                            <template x-for="(option, index) in filteredOptions" :key="option.id">
                                <button type="button" @mousedown.prevent="choose(option)"
                                    @mouseenter="highlightedIndex = index"
                                    :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' :
                                        'text-slate-700'"
                                    class="flex w-full items-start px-3 py-2 text-left text-sm hover:bg-slate-50">
                                    <span x-text="option.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Hasil Akhir</label>
                <div x-data="searchableSelect({
                    options: @js($outcomeSearchOptions),
                    selectedId: @js((string) old('hasil_akhir', $currentProspect?->hasil_akhir ?? 'in_progress')),
                    placeholder: 'Cari hasil akhir...',
                    emptyText: 'Hasil akhir tidak ditemukan.'
                })" class="relative">
                    <input type="hidden" name="hasil_akhir" :value="selectedId">

                    <div class="relative">
                        <input type="text" x-model="query" :placeholder="placeholder" @focus="openOptions()"
                            @click="openOptions()" @input="onInput()" @keydown.arrow-down.prevent="moveHighlight(1)"
                            @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                            @keydown.escape.prevent="open = false" @blur="setTimeout(() => closeOptions(), 120)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-20 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                            <button type="button" x-show="selectedId || query" @click="clear()"
                                class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <i class="ri-close-line text-base"></i>
                            </button>
                            <i class="ri-arrow-down-s-line text-lg text-slate-400"></i>
                        </div>
                    </div>

                    <div x-show="open" x-cloak
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="max-h-64 overflow-y-auto py-1">
                            <template x-if="filteredOptions.length === 0">
                                <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
                            </template>

                            <template x-for="(option, index) in filteredOptions" :key="option.id">
                                <button type="button" @mousedown.prevent="choose(option)"
                                    @mouseenter="highlightedIndex = index"
                                    :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' :
                                        'text-slate-700'"
                                    class="flex w-full items-start px-3 py-2 text-left text-sm hover:bg-slate-50">
                                    <span x-text="option.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Last Follow Up</label>
                <input type="date" name="last_follow_up_at"
                    value="{{ old('last_follow_up_at', $currentProspect?->last_follow_up_at?->format('Y-m-d')) }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Next Follow Up</label>
                <input type="date" name="next_follow_up_at"
                    value="{{ old('next_follow_up_at', $currentProspect?->next_follow_up_at?->format('Y-m-d')) }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">PIC Kantor</label>
                <div x-data="searchableSelect({
                    options: @js($assignedUserOptions),
                    selectedId: @js((string) old('assigned_to', $currentProspect?->assigned_to ?? '')),
                    placeholder: 'Cari PIC kantor...',
                    emptyText: 'User tidak ditemukan.'
                })" class="relative">
                    <input type="hidden" name="assigned_to" :value="selectedId">

                    <div class="relative">
                        <input type="text" x-model="query" :placeholder="placeholder" @focus="openOptions()"
                            @click="openOptions()" @input="onInput()" @keydown.arrow-down.prevent="moveHighlight(1)"
                            @keydown.arrow-up.prevent="moveHighlight(-1)" @keydown.enter.prevent="chooseHighlighted()"
                            @keydown.escape.prevent="open = false" @blur="setTimeout(() => closeOptions(), 120)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-20 text-sm focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">

                        <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                            <button type="button" x-show="selectedId || query" @click="clear()"
                                class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <i class="ri-close-line text-base"></i>
                            </button>
                            <i class="ri-arrow-down-s-line text-lg text-slate-400"></i>
                        </div>
                    </div>

                    <div x-show="open" x-cloak
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="max-h-64 overflow-y-auto py-1">
                            <template x-if="filteredOptions.length === 0">
                                <div class="px-3 py-2 text-sm text-slate-500" x-text="emptyText"></div>
                            </template>

                            <template x-for="(option, index) in filteredOptions" :key="option.id">
                                <button type="button" @mousedown.prevent="choose(option)"
                                    @mouseenter="highlightedIndex = index"
                                    :class="index === highlightedIndex ? 'bg-slate-100 text-slate-900' :
                                        'text-slate-700'"
                                    class="flex w-full items-start px-3 py-2 text-left text-sm hover:bg-slate-50">
                                    <span x-text="option.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 rounded-xl border border-sky-200 bg-sky-50 p-4">
                <div class="text-sm font-semibold text-sky-900">Penawaran dikelola dari halaman detail prospek</div>
                <div class="mt-1 text-sm text-sky-800">
                    Satu prospek bisa punya banyak penawaran. Setelah prospek disimpan, penawaran bisa dibuat berulang dari halaman detail untuk kebutuhan negosiasi, revisi, atau vendor/kontraktor yang berbeda.
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold mb-1">Kebutuhan</label>
                <textarea name="kebutuhan" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('kebutuhan', $currentProspect?->kebutuhan ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold mb-1">Catatan</label>
                <textarea name="catatan" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('catatan', $currentProspect?->catatan ?? '') }}</textarea>
            </div>
        </div>
    </div>

</div>

<div class="mt-4 flex justify-end gap-2">
    <a href="{{ route('prospects.index') }}"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
        Batal
    </a>
    <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
        Simpan
    </button>
</div>
