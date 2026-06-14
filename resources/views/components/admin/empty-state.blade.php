@props([
    'title',
    'description' => null,
    'href' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'border border-dashed border-zinc-800 bg-zinc-950 px-6 py-16 text-center']) }}>
    <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center border border-zinc-800 bg-zinc-900 text-zinc-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10" />
        </svg>
    </div>
    <h3 class="text-xs font-black uppercase tracking-[0.24em] text-zinc-300">{{ $title }}</h3>

    @if($description)
        <p class="mx-auto mt-3 max-w-md text-sm font-medium leading-6 text-zinc-600">{{ $description }}</p>
    @endif

    @if($href && $action)
        <a href="{{ $href }}" class="mt-7 inline-flex border border-white px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:bg-white hover:text-black">
            {{ $action }}
        </a>
    @endif
</div>
