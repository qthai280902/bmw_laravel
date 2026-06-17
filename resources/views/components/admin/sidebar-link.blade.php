@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center w-full border border-[#1C69D4]/35 bg-[#1C69D4]/15 px-4 py-3 text-xs font-black uppercase tracking-[0.16em] text-white shadow-[0_0_24px_rgba(28,105,212,0.12)] transition-all'
            : 'flex items-center w-full border border-transparent px-4 py-3 text-xs font-bold uppercase tracking-[0.16em] text-zinc-500 transition-all hover:border-white/10 hover:bg-white/[0.04] hover:text-zinc-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
