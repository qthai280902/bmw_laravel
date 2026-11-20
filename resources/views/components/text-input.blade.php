@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'bg-zinc-950/50 border-zinc-800 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] rounded-lg shadow-sm text-white placeholder-zinc-600 text-xs font-semibold px-5 py-3.5 transition-all']) !!}>
