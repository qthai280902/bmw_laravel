@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'bg-zinc-950/50 border-zinc-800 focus:border-[#1C69D4] focus:ring-0 rounded-none shadow-sm text-white placeholder-zinc-700 uppercase text-xs font-black tracking-widest px-6 py-4 transition-all']) !!}>
