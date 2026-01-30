@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-xs text-slate-700 mb-1']) }}>
    {{ $value ?? $slot }}
</label>