@props([
    'title',
    'description' => null,
    'hint' => null,
    'href' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-none border border-dashed border-[#242B38] bg-[#0B0F17]/70 px-6 py-12 text-center']) }}>
    <div class="mx-auto mb-4 flex h-11 w-11 items-center justify-center rounded-none border border-[#242B38] bg-[#050505] text-[#70A7FF]">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h10" />
        </svg>
    </div>
    <h3 class="text-xs font-black uppercase tracking-[0.22em] text-white">{{ $title }}</h3>

    @if($description)
        <p class="mx-auto mt-2.5 max-w-md text-xs font-medium leading-6 text-zinc-400">{{ $description }}</p>
    @endif

    @if($hint)
        <p class="mx-auto mt-2 max-w-md text-[11px] font-medium text-[#70A7FF]/90">{{ $hint }}</p>
    @endif

    @if($href && $action)
        <a href="{{ $href }}" class="mt-6 inline-flex rounded-none border border-[#1C69D4] bg-[#1C69D4] px-5 py-2.5 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-colors hover:bg-white hover:text-black">
            {{ $action }}
        </a>
    @endif
</div>
