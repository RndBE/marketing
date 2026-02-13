@extends('layouts.app', ['title' => 'Edit Laporan Perjalanan Marketing'])

@section('content')
    <div class="max-w-full">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">Edit Laporan Perjalanan Marketing</h1>
            <div class="text-sm text-slate-500 mt-0.5">{{ $report->nomor_laporan ?? '-' }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 pt-2 pb-4">
            <form method="POST" action="{{ route('marketing-reports.update', $report->id) }}" class="space-y-3"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('marketing_reports._form', ['report' => $report])

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('marketing-reports.show', $report->id) }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
