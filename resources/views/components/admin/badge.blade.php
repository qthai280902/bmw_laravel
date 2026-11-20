@props(['tone' => 'zinc'])

@php
    $classes = match ($tone) {
        'blue' => 'border-[#1C69D4]/30 bg-[#1C69D4]/15 text-[#70A7FF]',
        'sky' => 'border-sky-500/25 bg-sky-500/15 text-sky-300',
        'emerald' => 'border-emerald-500/25 bg-emerald-500/15 text-emerald-300',
        'yellow', 'amber' => 'border-amber-500/25 bg-amber-500/15 text-amber-300',
        'rose' => 'border-rose-500/25 bg-rose-500/15 text-rose-300',
        default => 'border-white/10 bg-white/[0.05] text-zinc-300',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold '.$classes]) }}>
    {{ $slot }}
</span>
