@extends('layouts.app', ['title' => 'Edit Prospek'])

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-semibold">Edit Prospek</h1>
        <div class="text-sm text-slate-500 mt-0.5">Perbarui data peluang dan follow up terakhir prospek ini.</div>
    </div>

    <form method="POST" action="{{ route('prospects.update', $prospect) }}">
        @include('prospects._form')
    </form>
@endsection
