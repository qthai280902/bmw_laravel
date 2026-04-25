@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center w-full px-4 py-3 text-xs font-black uppercase tracking-widest bg-[#1C69D4]/10 text-white border-r-2 border-[#1C69D4] transition-all'
            : 'flex items-center w-full px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-500 hover:bg-zinc-800 hover:text-zinc-200 transition-all';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
