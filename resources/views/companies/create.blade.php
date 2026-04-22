@extends('layouts.app', ['title' => 'Tambah Perusahaan'])

@section('content')
    <div class="w-full max-w-3xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Tambah Perusahaan</h1>
            <p class="text-sm text-slate-500">Data ini akan menjadi identitas perusahaan untuk dokumen dan kop PDF.</p>
        </div>

        <form method="POST" action="{{ route('companies.store') }}" class="space-y-4" enctype="multipart/form-data">
            @include('companies._form', [
                'company' => $company,
                'method' => 'POST',
                'submitLabel' => 'Simpan Perusahaan',
            ])
        </form>
    </div>
@endsection
