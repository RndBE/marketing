@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 focus:border-slate-900 focus:ring-slate-900 rounded-xl px-3 py-2 text-sm shadow-sm']) }}>