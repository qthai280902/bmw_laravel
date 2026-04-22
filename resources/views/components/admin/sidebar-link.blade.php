@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-4 py-2 bg-zinc-900 text-white text-xs font-black uppercase tracking-widest border-l-2 border-[#1C69D4] focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-4 py-2 bg-transparent text-zinc-400 text-xs font-black uppercase tracking-widest border-l-2 border-transparent hover:text-white hover:border-zinc-700 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
