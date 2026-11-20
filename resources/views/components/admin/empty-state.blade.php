@props([
    'title',
    'description' => null,
    'hint' => null,
    'href' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-white/[0.1] bg-[#0D121C]/80 px-6 py-12 text-center']) }}>
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-white/[0.08] bg-[#131A29] text-[#38BDF8]">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h10" />
        </svg>
    </div>
    <h3 class="text-sm font-bold text-white">{{ $title }}</h3>

    @if($description)
        <p class="mx-auto mt-2 max-w-md text-xs leading-6 text-zinc-400">{{ $description }}</p>
    @endif

    @if($hint)
        <p class="mx-auto mt-2 max-w-md text-xs font-medium text-[#38BDF8]/90">{{ $hint }}</p>
    @endif

    @if($href && $action)
        <a href="{{ $href }}" class="mt-6 inline-flex h-10 items-center rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0]">
            {{ $action }}
        </a>
    @endif
</div>
