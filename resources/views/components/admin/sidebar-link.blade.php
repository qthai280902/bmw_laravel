@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 w-full border-l-2 border-[#1C69D4] bg-[#1C69D4]/15 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition-all'
            : 'flex items-center gap-3 w-full border-l-2 border-transparent px-4 py-2.5 text-xs font-medium text-zinc-400 transition-all hover:bg-zinc-900/60 hover:text-zinc-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
