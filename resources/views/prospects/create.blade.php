@extends('layouts.app', ['title' => 'Tambah Prospek'])

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold">Tambah Prospek</h1>
        <div class="text-sm text-slate-500 mt-0.5">Simpan lead baru dan kaitkan ke PIC, penawaran, serta PIC kantor.</div>
    </div>

    <form method="POST" action="{{ route('prospects.store') }}">
        @include('prospects._form')
    </form>
@endsection
