@extends('layouts.app', ['title' => 'Edit PIC'])

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <h1 class="text-lg font-semibold mb-4">Edit PIC</h1>

        <form action="{{ route('pics.update', $pic->id) }}" method="POST">
            @include('pics.form')
        </form>
    </div>
@endsection
