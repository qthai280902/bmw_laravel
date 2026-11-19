@props(['active'])

@php
$classes = ($active ?? false)
            ? 'group flex items-center gap-3 w-full rounded-none border-l-2 border-[#1C69D4] bg-[#1C69D4]/15 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all [&_svg]:text-[#70A7FF]'
            : 'group flex items-center gap-3 w-full rounded-none border-l-2 border-transparent px-3.5 py-2 text-xs font-medium text-zinc-400 transition-all hover:border-zinc-700 hover:bg-zinc-900/70 hover:text-zinc-100 [&_svg]:text-zinc-500 hover:[&_svg]:text-zinc-300';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
