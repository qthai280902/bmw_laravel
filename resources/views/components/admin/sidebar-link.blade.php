@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center w-full border-l-2 border-[#1C69D4] bg-[#1C69D4]/10 px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition-all'
            : 'flex items-center w-full border-l-2 border-transparent px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-zinc-500 transition-all hover:border-zinc-700 hover:bg-zinc-900 hover:text-zinc-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
