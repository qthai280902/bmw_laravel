@props(['active', 'title' => null])

@php
$classes = ($active ?? false)
            ? 'sidebar-nav-link group flex items-center gap-3 w-full rounded-xl border border-[#1C69D4]/30 bg-gradient-to-r from-[#1C69D4]/20 to-[#1C69D4]/5 px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm transition-all [&_svg]:text-[#38BDF8]'
            : 'sidebar-nav-link group flex items-center gap-3 w-full rounded-xl border border-transparent px-3.5 py-2.5 text-xs font-medium text-zinc-400 transition-all hover:border-white/[0.06] hover:bg-[#131A29] hover:text-zinc-100 [&_svg]:text-zinc-500 hover:[&_svg]:text-zinc-300';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'title' => $title]) }}>
    {{ $slot }}
</a>
