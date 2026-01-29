@extends('layouts.app', ['title' => 'Tambah PIC'])

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <h1 class="text-lg font-semibold mb-4">Tambah PIC</h1>

        <form action="{{ route('pics.store') }}" method="POST">
            @include('pics.form')
        </form>
    </div>
@endsection
