@php
    $ownerCompany = $ownerCompany ?? null;
    $visibilityCompanies = $visibilityCompanies ?? collect();
    $selectedSharedCompanyIds = collect($selectedSharedCompanyIds ?? [])->map(fn($id) => (int) $id);
    $isAdmin = auth()->user()?->hasRole('admin');
@endphp

<div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-sm font-semibold text-slate-900">Visibility Perusahaan</div>
            <div class="mt-1 text-xs text-slate-500">
                Perusahaan asal selalu aktif. Admin bisa menambahkan perusahaan lain agar data ini ikut muncul di sana.
            </div>
        </div>
    </div>

    @if ($ownerCompany || $selectedSharedCompanyIds->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-1.5">
            @if ($ownerCompany)
                <span class="rounded-full bg-slate-900 px-2.5 py-0.5 text-[11px] font-semibold text-white">
                    {{ $ownerCompany->code ? $ownerCompany->code . ' - ' : '' }}{{ $ownerCompany->name }} (asal)
                </span>
            @endif

            @foreach ($visibilityCompanies as $company)
                @continue((int) $company->id === (int) ($ownerCompany?->id ?? 0))
                @continue(!$selectedSharedCompanyIds->contains((int) $company->id))

                <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700">
                    {{ $company->code ? $company->code . ' - ' : '' }}{{ $company->name }}
                </span>
            @endforeach
        </div>
    @endif

    @if ($isAdmin && $visibilityCompanies->isNotEmpty())
        <form method="POST" action="{{ $action }}" class="mt-3 space-y-2.5">
            @csrf

            <div class="grid grid-cols-1 gap-1.5 md:grid-cols-2">
                @foreach ($visibilityCompanies as $company)
                    @php
                        $isOwner = (int) $company->id === (int) ($ownerCompany?->id ?? 0);
                        $checked = $isOwner || $selectedSharedCompanyIds->contains((int) $company->id);
                    @endphp
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 px-3 py-2.5">
                        <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" class="mt-0.5"
                            {{ $checked ? 'checked' : '' }} {{ $isOwner ? 'disabled' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">
                                {{ $company->code ? $company->code . ' - ' : '' }}{{ $company->name }}
                            </span>
                            <span class="mt-1 block text-xs text-slate-500">
                                {{ $isOwner ? 'Perusahaan asal, tidak bisa dilepas.' : 'Tampilkan data ini pada perusahaan ini.' }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end">
                <button
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Simpan Visibility
                </button>
            </div>
        </form>
    @endif
</div>
