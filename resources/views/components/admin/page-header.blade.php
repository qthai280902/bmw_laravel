@props([
    'eyebrow' => null,
    'title',
    'accent' => null,
    'description' => null,
    'metric' => null,
])

<div {{ $attributes->merge(['class' => 'mb-8 border-b border-white/10 pb-8']) }}>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-4xl">
            @if($eyebrow)
                <p class="mb-3 text-[10px] font-black uppercase tracking-[0.32em] text-[#70A7FF]">{{ $eyebrow }}</p>
            @endif

            <h1 class="text-3xl font-black uppercase leading-none tracking-normal text-white md:text-5xl">
                {{ $title }}
                @if($accent)
                    <span class="text-[#70A7FF]">{{ $accent }}</span>
                @endif
            </h1>

            @if($description)
                <p class="mt-4 max-w-2xl text-sm font-medium leading-6 text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        @if($metric || isset($actions))
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @if($metric)
                    <div class="border border-white/10 bg-black/50 px-5 py-3">
                        {{ $metric }}
                    </div>
                @endif

                @isset($actions)
                    {{ $actions }}
                @endisset
            </div>
        @endif
    </div>
</div>
