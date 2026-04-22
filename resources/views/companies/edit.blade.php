@extends('layouts.app', ['title' => 'Edit Perusahaan'])

@section('content')
    <div class="w-full max-w-3xl">
        <div class="mb-5">
            <h1 class="text-xl font-semibold">Edit Perusahaan</h1>
            <p class="text-sm text-slate-500">Perubahan di sini langsung dipakai untuk kop PDF perusahaan terkait.</p>
        </div>

        <form method="POST" action="{{ route('companies.update', $company) }}" class="space-y-4" enctype="multipart/form-data">
            @include('companies._form', [
                'company' => $company,
                'method' => 'PUT',
                'submitLabel' => 'Simpan Perubahan',
            ])
        </form>
    </div>
@endsection
