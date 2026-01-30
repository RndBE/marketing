@extends('layouts.app')

@section('content')
    <div class=" mx-auto space-y-4">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Profile') }}
        </h2>

        <div class="p-6 bg-white shadow rounded-xl border border-slate-200">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-6 bg-white shadow rounded-xl border border-slate-200">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
@endsection