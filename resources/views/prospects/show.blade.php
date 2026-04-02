@extends('layouts.app', ['title' => 'Detail Prospek'])

@section('content')
    @php
        $canEdit =
            auth()->user()->hasPermission('edit-prospect') &&
            ((int) $prospect->created_by === (int) auth()->id() ||
                (int) $prospect->assigned_to === (int) auth()->id() ||
                auth()->user()->hasPermission('view-all-prospect'));

        $canDelete =
            auth()->user()->hasPermission('delete-prospect') &&
            ((int) $prospect->created_by === (int) auth()->id() ||
                (int) $prospect->assigned_to === (int) auth()->id() ||
                auth()->user()->hasPermission('view-all-prospect'));
        $latestPenawaran = $prospect->penawarans->first();

        $hasProgressErrors = $errors->hasAny([
            'tanggal',
            'aktivitas',
            'status',
            'next_follow_up_at',
            'hasil_akhir',
            'catatan',
            'attachments',
            'attachments.*',
        ]);
        $selectedAttachPenawaranId = (string) old('attach_penawaran_id', '');
        $selectedAttachUsulanId = (string) old('attach_usulan_id', '');
    @endphp

    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-semibold">{{ $prospect->display_title }}</h1>
            <div class="text-sm text-slate-500 mt-0.5">
                {{ $prospect->display_instansi }}
                @if ($prospect->display_instansi !== '-')
                    •
                @endif
                Lead sejak {{ $prospect->tanggal_lead?->format('d M Y') ?? '-' }}
                @if ($prospect->sumber_lead)
                    • {{ $prospect->sumber_lead }}
                @endif
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <span
                    class="px-2 py-1 rounded text-xs font-semibold bg-{{ $prospect->status_color }}-100 text-{{ $prospect->status_color }}-700">
                    {{ $prospect->status_label }}
                </span>
                <span
                    class="px-2 py-1 rounded text-xs font-semibold bg-{{ $prospect->outcome_color }}-100 text-{{ $prospect->outcome_color }}-700">
                    {{ $prospect->outcome_label }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('prospects.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Kembali
            </a>

            @if ($canEdit)
                <a href="{{ route('prospects.edit', $prospect) }}"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    Edit
                </a>
                <button type="button" onclick="document.getElementById('progressModal').classList.remove('hidden')"
                    class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                    Tambah Progress
                </button>
            @endif

            @if ($latestPenawaran)
                <a href="{{ route('penawaran.show', $latestPenawaran) }}"
                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                    Penawaran Terakhir
                </a>
            @endif

            @if (auth()->user()->hasPermission('create-penawaran'))
                <form method="POST" action="{{ route('prospects.buat-penawaran', $prospect) }}">
                    @csrf
                    <button
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                        Buat Penawaran Baru
                    </button>
                </form>
            @endif

            @if ($canDelete)
                <form method="POST" action="{{ route('prospects.destroy', $prospect) }}"
                    onsubmit="return confirm('Hapus prospek ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-500">
                        Hapus
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                        <div class="text-xs text-slate-500">Judul Prospek</div>
                        <div class="font-semibold text-sm">{{ $prospect->display_title }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Instansi</div>
                        <div class="font-semibold text-sm">{{ $prospect->display_instansi }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Nama PIC</div>
                        <div class="font-semibold text-sm">{{ $prospect->display_pic_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">HP / WA</div>
                        <div class="font-semibold text-sm">{{ $prospect->display_phone }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Email</div>
                        <div class="font-semibold text-sm">{{ $prospect->display_email }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Jabatan PIC</div>
                        <div class="font-semibold text-sm">{{ $prospect->jabatan_pic ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Lokasi</div>
                        <div class="font-semibold text-sm">{{ $prospect->lokasi ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">PIC Existing</div>
                        <div class="font-semibold text-sm">
                            @if ($prospect->pic)
                                {{ trim(($prospect->pic->honorific ? $prospect->pic->honorific . ' ' : '') . $prospect->pic->nama) }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold mb-2">Kebutuhan</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $prospect->kebutuhan ?: '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold mb-2">Catatan</div>
                <div class="text-sm text-slate-700 whitespace-pre-line">{{ $prospect->catatan ?: '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <div class="text-sm font-semibold">Timeline Progress</div>
                    </div>
                </div>

                @if ($prospect->updates->isEmpty())
                    <div class="text-sm text-slate-500">Belum ada progress follow up.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($prospect->updates as $update)
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">{{ $update->aktivitas }}</div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            {{ $update->tanggal?->format('d M Y') ?? '-' }}
                                            • {{ $update->user?->name ?? 'Sistem' }}
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="px-2 py-1 rounded text-xs font-semibold bg-{{ $update->status_color }}-100 text-{{ $update->status_color }}-700">
                                            {{ $update->status_label }}
                                        </span>
                                        <span
                                            class="px-2 py-1 rounded text-xs font-semibold bg-{{ $update->outcome_color }}-100 text-{{ $update->outcome_color }}-700">
                                            {{ $update->outcome_label }}
                                        </span>
                                    </div>
                                </div>

                                @if ($update->catatan)
                                    <div class="text-sm text-slate-700 whitespace-pre-line mt-3">{{ $update->catatan }}
                                    </div>
                                @endif

                                @if ($update->attachments->isNotEmpty())
                                    <div class="mt-4">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                            Bukti Follow Up
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach ($update->attachments as $attachment)
                                                @php
                                                    $fileUrl = '/storage/' . ltrim($attachment->file_path, '/');
                                                    $mime = strtolower((string) $attachment->mime);
                                                    $isImage = str_contains($mime, 'image');
                                                    $fileSize = $attachment->size
                                                        ? number_format($attachment->size / 1024, 1) . ' KB'
                                                        : '-';
                                                @endphp
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                                    @if ($isImage)
                                                        <a href="{{ $fileUrl }}" target="_blank" class="block">
                                                            <img src="{{ $fileUrl }}"
                                                                alt="{{ $attachment->file_name }}"
                                                                class="h-40 w-full rounded-lg border border-slate-200 object-cover">
                                                        </a>
                                                    @else
                                                        <a href="{{ $fileUrl }}" target="_blank"
                                                            class="flex h-40 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white text-center text-sm font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-800">
                                                            Lihat File
                                                        </a>
                                                    @endif

                                                    <div class="mt-3">
                                                        <a href="{{ $fileUrl }}" target="_blank"
                                                            class="block truncate text-sm font-medium text-slate-800 hover:underline">
                                                            {{ $attachment->file_name }}
                                                        </a>
                                                        <div class="mt-1 text-xs text-slate-500">
                                                            {{ $attachment->mime ?: '-' }} • {{ $fileSize }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="text-xs text-slate-500 mt-3">
                                    Next follow up:
                                    <span class="font-medium text-slate-700">
                                        {{ $update->next_follow_up_at?->format('d M Y') ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Produk</div>
                <div class="text-lg font-semibold mt-1">{{ $prospect->produk ?: '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Potensi Nilai</div>
                <div class="text-sm font-medium">Rp {{ number_format((int) $prospect->potensi_nilai, 0, ',', '.') }}</div>

                <div class="text-xs text-slate-500 mt-4">PIC Kantor</div>
                <div class="text-sm font-medium">{{ $prospect->assignedTo?->name ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Last Follow Up</div>
                <div class="text-sm font-medium">{{ $prospect->last_follow_up_at?->format('d M Y') ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Next Follow Up</div>
                <div class="text-sm font-medium">{{ $prospect->next_follow_up_at?->format('d M Y') ?? '-' }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Dibuat Oleh</div>
                <div class="text-sm font-medium">{{ $prospect->creator?->name ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Update Terakhir</div>
                <div class="text-sm font-medium">{{ $prospect->updated_at?->format('d M Y H:i') ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-4">Jumlah Penawaran</div>
                <div class="text-sm font-medium">
                    {{ $prospect->penawarans->count() }} penawaran terkait
                </div>

                <div class="text-xs text-slate-500 mt-4">Jumlah Usulan</div>
                <div class="text-sm font-medium">
                    {{ $prospect->usulans->count() }} usulan terkait
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <div class="text-sm font-semibold">Grup Usulan</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $prospect->usulans->count() }} usulan
                    </div>
                </div>

                @if ($canEdit && $canViewUsulan)
                    <form method="POST" action="{{ route('prospects.attach-usulan', $prospect) }}"
                        class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @csrf
                        <label class="block text-xs font-semibold mb-2">Hubungkan Usulan Existing</label>
                        <div class="grid grid-cols-1 gap-3">
                            <div x-data="searchableSelect({
                                options: @js($attachUsulanOptions),
                                selectedId: @js($selectedAttachUsulanId),
                                placeholder: 'Cari judul usulan...',
                                emptyText: 'Usulan tidak ditemukan.'
                            })" class="relative">
                                <input type="hidden" name="attach_usulan_id" :value="selectedId">

                                <div class="relative">
                                    <input type="text" x-model="query" :placeholder="placeholder"
                                        @focus="openOptions()" @click="openOptions()" @input="onInput()"
                                        @keydown.arrow-down.prevent="moveHighlight(1)"
                                        @keydown.arrow-up.prevent="moveHighlight(-1)"
                                        @keydown.enter.prevent="chooseHighlighted()"
                                        @keydown.escape.prevent="open = false"
                                        @blur="setTimeout(() => closeOptions(), 120)"
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

                            <button
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Hubungkan
                            </button>
                        </div>
                        @error('attach_usulan_id')
                            <div class="mt-2 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </form>
                @endif

                @if ($prospect->usulans->isEmpty())
                    <div class="text-sm text-slate-500">Belum ada usulan yang terhubung ke prospek ini.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($prospect->usulans as $usulan)
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex flex-col gap-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $usulan->judul }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $usulan->pic?->instansi ?? '-' }}
                                            • {{ $usulan->creator?->name ?? '-' }}
                                            @if ($usulan->tanggal_dibutuhkan)
                                                • Deadline {{ $usulan->tanggal_dibutuhkan->format('d M Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="rounded-full bg-{{ $usulan->status_color }}-100 px-2 py-1 text-xs font-semibold text-{{ $usulan->status_color }}-700">
                                            {{ $usulan->status_label }}
                                        </span>
                                        <a href="{{ route('usulan.show', $usulan) }}"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                            Buka Usulan
                                        </a>
                                        @if ($canEdit)
                                            <form method="POST" action="{{ route('prospects.detach-usulan', [$prospect, $usulan]) }}"
                                                onsubmit="return confirm('Lepas usulan ini dari prospek? Data usulan tidak akan terhapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                    Lepas
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-slate-500">
                                    @if ($usulan->penawaran)
                                        Terhubung ke penawaran
                                        {{ $usulan->penawaran->docNumber?->doc_no ?? 'Draft #' . $usulan->penawaran->id }}
                                    @else
                                        Belum terhubung ke penawaran
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <div class="text-sm font-semibold">Grup Penawaran</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $prospect->penawarans->count() }} penawaran
                    </div>
                </div>

                @if ($canEdit)
                    <form method="POST" action="{{ route('prospects.attach-penawaran', $prospect) }}"
                        class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        @csrf
                        <label class="block text-xs font-semibold mb-2">Hubungkan Penawaran Existing</label>
                        <div class="grid grid-cols-1 gap-3">
                            <div x-data="searchableSelect({
                                options: @js($attachPenawaranOptions),
                                selectedId: @js($selectedAttachPenawaranId),
                                placeholder: 'Cari nomor atau judul penawaran...',
                                emptyText: 'Penawaran tidak ditemukan.'
                            })" class="relative">
                                <input type="hidden" name="attach_penawaran_id" :value="selectedId">

                                <div class="relative">
                                    <input type="text" x-model="query" :placeholder="placeholder"
                                        @focus="openOptions()" @click="openOptions()" @input="onInput()"
                                        @keydown.arrow-down.prevent="moveHighlight(1)"
                                        @keydown.arrow-up.prevent="moveHighlight(-1)"
                                        @keydown.enter.prevent="chooseHighlighted()"
                                        @keydown.escape.prevent="open = false"
                                        @blur="setTimeout(() => closeOptions(), 120)"
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

                            <button
                                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Hubungkan
                            </button>
                        </div>
                        @error('attach_penawaran_id')
                            <div class="mt-2 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </form>
                @endif

                @if ($prospect->penawarans->isEmpty())
                    <div class="text-sm text-slate-500">Belum ada penawaran yang terhubung ke prospek ini.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($prospect->penawarans as $penawaran)
                            @php
                                $approvalStatus = $penawaran->approval?->status;
                                $approvalLabel = match ($approvalStatus) {
                                    'disetujui' => 'Disetujui',
                                    'ditolak' => 'Ditolak',
                                    'revisi' => 'Perlu Revisi',
                                    'menunggu' => 'Menunggu Approval',
                                    default => 'Draft',
                                };
                                $approvalColor = match ($approvalStatus) {
                                    'disetujui' => 'emerald',
                                    'ditolak' => 'rose',
                                    'revisi' => 'amber',
                                    'menunggu' => 'sky',
                                    default => 'slate',
                                };
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex flex-col gap-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">
                                            {{ $penawaran->judul ?: 'Draft #' . $penawaran->id }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $penawaran->docNumber?->doc_no ?? 'Draft #' . $penawaran->id }}
                                            @if ($penawaran->pic)
                                                •
                                                {{ trim(($penawaran->pic->honorific ? $penawaran->pic->honorific . ' ' : '') . $penawaran->pic->nama) }}
                                            @endif
                                            @if ($penawaran->instansi_tujuan)
                                                • {{ $penawaran->instansi_tujuan }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="rounded-xl bg-{{ $approvalColor }}-100 px-2 py-2 text-xs font-semibold text-{{ $approvalColor }}-700">
                                            {{ $approvalLabel }}
                                        </span>
                                        <a href="{{ route('penawaran.show', $penawaran) }}"
                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                                            Buka Penawaran
                                        </a>
                                        @if ($canEdit)
                                            <form method="POST" action="{{ route('prospects.detach-penawaran', [$prospect, $penawaran]) }}"
                                                onsubmit="return confirm('Lepas penawaran ini dari prospek? Data penawaran tidak akan terhapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                    Lepas
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-slate-500">
                                    Update terakhir {{ $penawaran->updated_at?->format('d M Y H:i') ?? '-' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($canEdit)
        <div id="progressModal"
            class="{{ $hasProgressErrors ? '' : 'hidden' }} fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
            onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white w-full max-w-xl rounded-2xl p-6 max-h-[90vh] overflow-y-auto"
                onclick="event.stopPropagation()">
                <h2 class="text-lg font-semibold mb-4">Tambah Progress Prospek</h2>

                <form method="POST" action="{{ route('prospects.updates.store', $prospect) }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal', now()->format('Y-m-d')) }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                            @error('tanggal')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Next Follow Up</label>
                            <input type="date" name="next_follow_up_at"
                                value="{{ old('next_follow_up_at', $prospect->next_follow_up_at?->format('Y-m-d')) }}"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @error('next_follow_up_at')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Aktivitas</label>
                            <input name="aktivitas" value="{{ old('aktivitas') }}"
                                placeholder="Contoh: Follow up WA, meeting, kirim proposal"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                            @error('aktivitas')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Status</label>
                            <select name="status"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('status', $prospect->status) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Hasil Akhir</label>
                            <select name="hasil_akhir"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                                @foreach ($outcomeOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('hasil_akhir', $prospect->hasil_akhir) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('hasil_akhir')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Catatan</label>
                            <textarea name="catatan" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                placeholder="Catatan hasil follow up, keputusan, kebutuhan tambahan, dan seterusnya">{{ old('catatan') }}</textarea>
                            @error('catatan')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold mb-1">Bukti Follow Up / Progress</label>
                            <input type="file" name="attachments[]" multiple
                                accept=".pdf,.doc,.docx,.xls,.xlsx,image/*"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                            <div class="mt-1 text-xs text-slate-500">
                                Bisa upload banyak file sekaligus. Format: gambar, PDF, Word, atau Excel. Maks. 10 MB per
                                file.
                            </div>
                            @error('attachments')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                            @error('attachments.*')
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" onclick="document.getElementById('progressModal').classList.add('hidden')"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">
                            Batal
                        </button>
                        <button
                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                            Simpan Progress
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
