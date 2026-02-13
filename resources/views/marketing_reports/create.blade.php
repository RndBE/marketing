@extends('layouts.app', ['title' => 'Buat Laporan Perjalanan Marketing'])

@section('content')
    <div class="max-w-full">
        <div class="mb-3">
            <h1 class="text-xl font-semibold">Buat Laporan Perjalanan Marketing</h1>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 pt-2 pb-4">
            <form method="POST" action="{{ route('marketing-reports.store') }}" class="space-y-3" enctype="multipart/form-data">
                @csrf

                @include('marketing_reports._form')

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('marketing-reports.index') }}"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Batal</a>
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Simpan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
