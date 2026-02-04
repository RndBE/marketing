@extends('layouts.app', ['title' => 'Daftar Template Invoice'])

@section('content')
    <div class="">
        <div class="max-w-full   grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- SIGNATURE TEMPLATES --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl-lg p-5">
                <h3 class="font-bold text-lg mb-4">Signature Templates</h3>

                {{-- Form for New Signature --}}
                <form action="{{ route('templates.signature.store') }}" method="POST" enctype="multipart/form-data"
                    class="mb-6 p-4 bg-slate-50 rounded-xl border">
                    @csrf
                    <div class="mb-2">
                        <label class="block text-xs font-bold">Template Name</label>
                        <input name="template_name" class="w-full text-sm rounded-xl border-slate-200"
                            placeholder="e.g. Direktur" required>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <div>
                            <label class="block text-xs font-bold">Nama</label>
                            <input name="nama" class="w-full text-sm rounded-xl border-slate-200" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold">Jabatan</label>
                            <input name="jabatan" class="w-full text-sm rounded-xl border-slate-200" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-bold">Kota</label>
                        <input name="kota" class="w-full text-sm rounded-xl border-slate-200" value="Sleman">
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-bold">TTD Image</label>
                        <input type="file" name="ttd" class="w-full text-xs">
                    </div>
                    <button class="bg-blue-600 text-white px-3 py-1 rounded-xl text-sm hover:bg-blue-700">Add
                        Template</button>
                </form>

                {{-- List of Signatures --}}
                <div class="space-y-3">
                    @foreach($signatures as $sig)
                        <div class="border rounded-xl p-3 flex justify-between items-center">
                            <div class="flex gap-3 items-center">
                                @if($sig->ttd_path)
                                    <img src="{{ asset('storage/' . $sig->ttd_path) }}" class="h-10 w-10 object-contain">
                                @else
                                    <div class="h-10 w-10 bg-gray-100 flex items-center justify-center text-xs">No Img</div>
                                @endif
                                <div>
                                    <div class="font-bold text-sm">{{ $sig->template_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $sig->nama }} - {{ $sig->jabatan }}</div>
                                </div>
                            </div>
                            <form action="{{ route('templates.signature.delete', $sig->id) }}" method="POST"
                                onsubmit="return confirm('Delete this template?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline text-xs">Delete</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- TERM TEMPLATES --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl-lg p-5">
                <h3 class="font-bold text-lg mb-4">Terms Templates</h3>

                {{-- Form for New Term --}}
                <form action="{{ route('templates.term.store') }}" method="POST"
                    class="mb-6 p-4 bg-slate-50 rounded-xl border">
                    @csrf
                    <div class="mb-2">
                        <label class="block text-xs font-bold">Template Name</label>
                        <input name="template_name" class="w-full text-sm rounded-xl border-slate-200"
                            placeholder="e.g. Proyek Besar" required>
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-bold">Terms</label>
                        <div id="terms-input-list" class="space-y-2">
                            <input name="terms[]" class="w-full text-sm rounded-xl border-slate-200" placeholder="Term 1"
                                required>
                            <input name="terms[]" class="w-full text-sm rounded-xl border-slate-200" placeholder="Term 2">
                        </div>
                        <button type="button" onclick="addTermInput()" class="text-xs text-blue-500 mt-1 underline">+
                            Add Line</button>
                    </div>
                    <button class="bg-blue-600 text-white px-3 py-1 rounded-xl text-sm hover:bg-blue-700">Add
                        Template</button>
                </form>

                {{-- List of Terms --}}
                <div class="space-y-3">
                    @foreach($terms as $term)
                        <div class="border rounded-xl p-3">
                            <div class="flex justify-between items-center mb-2">
                                <div class="font-bold text-sm">{{ $term->template_name }}</div>
                                <form action="{{ route('templates.term.delete', $term->id) }}" method="POST"
                                    onsubmit="return confirm('Delete this template?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline text-xs">Delete</button>
                                </form>
                            </div>
                            <ul class="list-disc list-inside text-xs text-gray-600">
                                @foreach($term->terms as $t)
                                    <li>{{ $t }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function addTermInput() {
            const container = document.getElementById('terms-input-list');
            const input = document.createElement('input');
            input.name = "terms[]";
            input.className = "w-full text-sm rounded-xl border-slate-200 mt-2";
            input.placeholder = "Term " + (container.children.length + 1);
            container.appendChild(input);
        }
    </script>
@endpush