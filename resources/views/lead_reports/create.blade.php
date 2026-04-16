@extends('layouts.app', ['title' => 'Upload Lead Report'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-3 text-sm text-slate-500">
        <a href="{{ route('lead-reports.index') }}" class="hover:text-slate-800 transition-colors">
            <i class="ri-arrow-left-line mr-1"></i> Lead Reports
        </a>
        <span>/</span>
        <span class="text-slate-800 font-medium">Upload Report</span>
    </div>

    {{-- Upload Form --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="ri-upload-cloud-2-line text-lg text-indigo-600"></i>
                Upload Lead Report (.md)
            </h2>
            <p class="text-xs text-slate-500 mt-1">Upload file markdown berisi report lead dari hasil scrapping</p>
        </div>

        <form method="POST" action="{{ route('lead-reports.store') }}" enctype="multipart/form-data"
              class="p-6 space-y-6" x-data="uploadForm()">

            @csrf

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Judul Report <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                       placeholder="Contoh: Lead Report Google Maps - April 2026"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                              focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 focus:bg-white transition
                              @error('title') border-rose-300 bg-rose-50 @enderror">
                @error('title')
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Report Date --}}
            <div>
                <label for="report_date" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Tanggal Report
                </label>
                <input type="date" id="report_date" name="report_date" value="{{ old('report_date') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                              focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 focus:bg-white transition
                              @error('report_date') border-rose-300 bg-rose-50 @enderror">
                <p class="text-xs text-slate-400 mt-1">Opsional. Tanggal report ini dibuat / tanggal data.</p>
                @error('report_date')
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- File Dropzone --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    File Markdown (.md) <span class="text-rose-500">*</span>
                </label>

                <div class="relative">
                    <input type="file" id="md_file" name="md_file" accept=".md" required
                           class="sr-only" @change="handleFile($event)">

                    <label for="md_file"
                           class="flex flex-col items-center justify-center w-full min-h-[200px] border-2 border-dashed rounded-2xl cursor-pointer
                                  transition-all duration-300"
                           :class="dragOver
                               ? 'border-indigo-400 bg-indigo-50/50'
                               : (fileName
                                   ? 'border-emerald-300 bg-emerald-50/30'
                                   : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300')"
                           @dragover.prevent="dragOver = true"
                           @dragleave.prevent="dragOver = false"
                           @drop.prevent="handleDrop($event)">

                        <template x-if="!fileName">
                            <div class="flex flex-col items-center gap-3 py-6">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center">
                                    <i class="ri-markdown-line text-3xl"></i>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-slate-600">
                                        <span class="text-indigo-600">Klik untuk upload</span> atau drag & drop
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">Format: .md (Markdown) — Maks 10MB</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div class="flex items-center gap-4 py-6 px-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-check-line text-2xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 truncate" x-text="fileName"></p>
                                    <p class="text-xs text-slate-400" x-text="fileSize"></p>
                                </div>
                                <button type="button" @click.prevent="clearFile()"
                                        class="ml-auto p-1.5 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition">
                                    <i class="ri-close-line text-lg"></i>
                                </button>
                            </div>
                        </template>
                    </label>
                </div>

                @error('md_file')
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Preview Section --}}
            <div x-show="preview" x-transition class="border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600 flex items-center gap-1.5">
                        <i class="ri-eye-line"></i> Preview
                    </span>
                    <button type="button" @click="preview = ''" class="text-xs text-slate-400 hover:text-slate-600">&times; Tutup</button>
                </div>
                <div class="p-4 max-h-72 overflow-y-auto">
                    <pre class="text-xs text-slate-700 whitespace-pre-wrap font-mono leading-relaxed" x-text="preview"></pre>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('lead-reports.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-slate-100 rounded-xl
                          hover:bg-slate-200 transition-all duration-200">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl
                               hover:bg-slate-800 transition-all duration-200 shadow-sm hover:shadow-md
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="!fileName">
                    <i class="ri-upload-cloud-2-line mr-1.5"></i>
                    Upload Report
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function uploadForm() {
    return {
        fileName: '',
        fileSize: '',
        preview: '',
        dragOver: false,

        handleFile(event) {
            const file = event.target.files[0];
            if (file) this.processFile(file);
        },

        handleDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                // Set the file to the input
                const input = document.getElementById('md_file');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                this.processFile(file);
            }
        },

        processFile(file) {
            if (!file.name.endsWith('.md')) {
                alert('File harus berformat .md (Markdown)');
                return;
            }

            this.fileName = file.name;
            this.fileSize = this.formatSize(file.size);

            // Read preview
            const reader = new FileReader();
            reader.onload = (e) => {
                const content = e.target.result;
                this.preview = content.substring(0, 2000) + (content.length > 2000 ? '\n\n... (preview terpotong)' : '');
            };
            reader.readAsText(file);

            // Auto-fill title if empty
            const titleInput = document.getElementById('title');
            if (!titleInput.value) {
                titleInput.value = file.name.replace('.md', '').replace(/[-_]/g, ' ');
            }
        },

        clearFile() {
            this.fileName = '';
            this.fileSize = '';
            this.preview = '';
            document.getElementById('md_file').value = '';
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }
}
</script>
@endpush
@endsection
