@props(['tone' => 'zinc'])

@php
    $classes = match ($tone) {
        'blue' => 'border-[#1C69D4]/25 bg-[#1C69D4]/10 text-[#70A7FF]',
        'emerald' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
        'yellow' => 'border-yellow-500/25 bg-yellow-500/10 text-yellow-400',
        'rose' => 'border-rose-500/25 bg-rose-500/10 text-rose-400',
        default => 'border-zinc-700 bg-zinc-950 text-zinc-400',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] '.$classes]) }}>
    {{ $slot }}
</span>
