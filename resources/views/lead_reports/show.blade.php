@extends('layouts.app', ['title' => $leadReport->title])

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-sm text-slate-500">
            <a href="{{ route('lead-reports.index') }}" class="hover:text-slate-800 transition-colors">
                <i class="ri-arrow-left-line mr-1"></i> Lead Reports
            </a>
            <span>/</span>
            <span class="text-slate-800 font-medium truncate max-w-xs">{{ $leadReport->title }}</span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('lead-reports.download', $leadReport) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-sm font-medium rounded-xl
                      text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200 shadow-sm">
                <i class="ri-download-2-line"></i>
                Download .md
            </a>

            @if(auth()->user()->isSuperadmin())
                <form method="POST" action="{{ route('lead-reports.destroy', $leadReport) }}"
                      onsubmit="return confirm('Yakin ingin menghapus report ini?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-rose-200 text-sm font-medium rounded-xl
                                   text-rose-600 bg-white hover:bg-rose-50 transition-all duration-200 shadow-sm">
                        <i class="ri-delete-bin-6-line"></i>
                        Hapus
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Meta Info Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center gap-4 md:gap-8">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-500 text-white
                            flex items-center justify-center flex-shrink-0">
                    <i class="ri-markdown-line text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">{{ $leadReport->title }}</h1>
                    <p class="text-xs text-slate-400">{{ $leadReport->original_filename }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 md:ml-auto text-sm">
                <div class="flex items-center gap-2 text-slate-500">
                    <div class="w-6 h-6 rounded-full bg-slate-900 text-white text-[10px] font-bold flex items-center justify-center">
                        {{ substr($leadReport->uploader->name ?? '?', 0, 1) }}
                    </div>
                    <span>{{ $leadReport->uploader->name ?? '-' }}</span>
                </div>

                <div class="flex items-center gap-1.5 text-slate-500">
                    <i class="ri-calendar-line text-base"></i>
                    <span>Diupload: {{ $leadReport->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>

                @if($leadReport->report_date)
                    <div class="flex items-center gap-1.5 text-slate-500">
                        <i class="ri-calendar-check-line text-base"></i>
                        <span>Laporan: {{ $leadReport->report_date->translatedFormat('d M Y') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Rendered Markdown Content --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <i class="ri-article-line text-base"></i>
                Isi Report
            </div>
            <span class="text-xs text-slate-400 font-medium bg-slate-100 px-2.5 py-1 rounded-lg">Markdown</span>
        </div>

        <div class="p-6 md:p-8 markdown-body">
            {!! $renderedContent !!}
        </div>
    </div>
</div>

@push('scripts')
<style>
.markdown-body {
    color: #334155;
    font-size: 0.9375rem;
    line-height: 1.75;
    word-wrap: break-word;
}
.markdown-body h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.75rem;
    margin: 1.5rem 0 1rem;
}
.markdown-body h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 2rem 0 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
}
.markdown-body h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 1.5rem 0 0.5rem;
}
.markdown-body h4, .markdown-body h5, .markdown-body h6 {
    font-weight: 600;
    color: #1e293b;
    margin: 1.25rem 0 0.5rem;
}
.markdown-body p {
    margin: 0.75rem 0;
    color: #475569;
    line-height: 1.75;
}
.markdown-body a {
    color: #4f46e5;
    text-decoration: none;
}
.markdown-body a:hover {
    text-decoration: underline;
}
.markdown-body strong {
    font-weight: 600;
    color: #0f172a;
}
.markdown-body code {
    background: #f1f5f9;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.8125rem;
    color: #334155;
    font-family: 'Fira Code', 'Consolas', monospace;
}
.markdown-body pre {
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    overflow-x: auto;
    margin: 1rem 0;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
}
.markdown-body pre code {
    background: none;
    padding: 0;
    color: inherit;
    font-size: 0.8125rem;
}
.markdown-body table {
    border-collapse: collapse;
    width: 100%;
    margin: 1rem 0;
    font-size: 0.875rem;
}
.markdown-body th {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.625rem 1rem;
    text-align: left;
    font-weight: 600;
    color: #475569;
}
.markdown-body td {
    border: 1px solid #e2e8f0;
    padding: 0.625rem 1rem;
    color: #475569;
}
.markdown-body tr:nth-child(even) {
    background: #f8fafc;
}
.markdown-body ul {
    list-style: disc;
    padding-left: 1.5rem;
    margin: 0.75rem 0;
}
.markdown-body ol {
    list-style: decimal;
    padding-left: 1.5rem;
    margin: 0.75rem 0;
}
.markdown-body li {
    margin: 0.25rem 0;
    color: #475569;
}
.markdown-body blockquote {
    border-left: 4px solid #a5b4fc;
    background: rgba(238, 242, 255, 0.5);
    padding: 0.5rem 1rem;
    margin: 1rem 0;
    border-radius: 0 0.5rem 0.5rem 0;
    color: #475569;
}
.markdown-body blockquote p {
    margin: 0.25rem 0;
}
.markdown-body hr {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 2rem 0;
}
.markdown-body img {
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    max-width: 100%;
}
</style>
@endpush
@endsection
