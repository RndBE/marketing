@extends('layouts.app', ['title' => 'Lead Reports'])

@section('content')
<div class="space-y-6">

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Lead Reports</h1>
            <p class="text-sm text-slate-500 mt-1">Hasil scrapping lead yang telah diupload dalam format Markdown</p>
        </div>

        @if(auth()->user()->isSuperadmin())
            <a href="{{ route('lead-reports.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl
                      hover:bg-slate-800 transition-all duration-200 shadow-sm hover:shadow-md">
                <i class="ri-upload-cloud-2-line text-lg"></i>
                Upload Report
            </a>
        @endif
    </div>

    {{-- Filter Section --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <form method="GET" action="{{ route('lead-reports.index') }}" class="flex flex-col md:flex-row gap-3 items-end">
            <div class="flex-1 min-w-0">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cari</label>
                <div class="relative">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari judul, filename, atau isi report..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                                  focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 focus:bg-white transition">
                </div>
            </div>

            <div class="w-full md:w-44">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                              focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 focus:bg-white transition">
            </div>

            <div class="w-full md:w-44">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm
                              focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 focus:bg-white transition">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl
                               hover:bg-slate-800 transition-all duration-200 whitespace-nowrap">
                    <i class="ri-filter-3-line mr-1"></i> Filter
                </button>

                @if($q || $dateFrom || $dateTo)
                    <a href="{{ route('lead-reports.index') }}"
                       class="px-4 py-2.5 bg-slate-100 text-slate-700 text-sm font-medium rounded-xl
                              hover:bg-slate-200 transition-all duration-200 whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="ri-file-text-line text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Total Reports</p>
                    <p class="text-xl font-bold text-slate-900">{{ $reports->total() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="ri-calendar-check-line text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Halaman</p>
                    <p class="text-xl font-bold text-slate-900">{{ $reports->currentPage() }} / {{ $reports->lastPage() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="ri-time-line text-xl"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Upload Terakhir</p>
                    <p class="text-sm font-bold text-slate-900">
                        @if($reports->count() > 0)
                            {{ $reports->first()->created_at->translatedFormat('d M Y') }}
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Reports List --}}
    @if($reports->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($reports as $report)
                <a href="{{ route('lead-reports.show', $report) }}"
                   class="group bg-white rounded-2xl border border-slate-200 p-5 shadow-sm
                          hover:shadow-md hover:border-slate-300 hover:-translate-y-0.5
                          transition-all duration-300 flex flex-col">

                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-800 to-slate-600 text-white
                                        flex items-center justify-center flex-shrink-0
                                        group-hover:from-indigo-600 group-hover:to-indigo-500 transition-all duration-300">
                                <i class="ri-markdown-line text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-slate-900 text-sm truncate group-hover:text-indigo-600 transition-colors">
                                    {{ $report->title }}
                                </h3>
                                <p class="text-xs text-slate-400 truncate">{{ $report->original_filename }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Preview snippet --}}
                    <p class="text-xs text-slate-500 line-clamp-3 mb-4 flex-1 leading-relaxed">
                        {{ Str::limit(strip_tags($report->content), 150) }}
                    </p>

                    {{-- Card Footer --}}
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-slate-900 text-white text-[10px] font-bold flex items-center justify-center">
                                {{ substr($report->uploader->name ?? '?', 0, 1) }}
                            </div>
                            <span class="text-xs text-slate-500">{{ $report->uploader->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400">
                            <i class="ri-calendar-line"></i>
                            {{ $report->created_at->translatedFormat('d M Y') }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $reports->links() }}
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-12 shadow-sm text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                <i class="ri-file-search-line text-3xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-1">Belum Ada Report</h3>
            <p class="text-sm text-slate-500">
                @if($q || $dateFrom || $dateTo)
                    Tidak ditemukan report dengan filter yang dipilih.
                @else
                    Report lead dari hasil scrapping akan muncul di sini setelah diupload.
                @endif
            </p>
            @if(auth()->user()->isSuperadmin() && !($q || $dateFrom || $dateTo))
                <a href="{{ route('lead-reports.create') }}"
                   class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl
                          hover:bg-slate-800 transition-all duration-200">
                    <i class="ri-upload-cloud-2-line"></i>
                    Upload Report Pertama
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
