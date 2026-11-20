@props([
    'eyebrow' => null,
    'title',
    'accent' => null,
    'description' => null,
    'metric' => null,
])

<div {{ $attributes->merge(['class' => 'mb-8 border-b border-white/[0.08] pb-6']) }}>
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-4xl">
            @if($eyebrow)
                <p class="mb-2 text-xs font-semibold text-[#38BDF8]">{{ $eyebrow }}</p>
            @endif

            <h1 class="text-2xl font-extrabold tracking-tight text-white md:text-4xl">
                {{ $title }}
                @if($accent)
                    <span class="text-[#70A7FF]">{{ $accent }}</span>
                @endif
            </h1>

            @if($description)
                <p class="mt-2.5 max-w-2xl text-sm leading-6 text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        @if($metric || isset($actions))
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @if($metric)
                    <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-5 py-3 shadow-lg shadow-black/30">
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
