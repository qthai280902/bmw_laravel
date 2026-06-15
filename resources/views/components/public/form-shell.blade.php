@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'backgroundUrl' => null,
])

@php
    $resolvedBackgroundUrl = $backgroundUrl ?? \App\Models\SiteSetting::publicFormBackgroundImageUrl();
@endphp

<section {{ $attributes->merge(['class' => 'relative min-h-screen overflow-hidden bg-black text-white']) }}>
    <img src="{{ $resolvedBackgroundUrl }}" alt="" class="absolute inset-0 h-full w-full object-cover" aria-hidden="true">
    <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(0,0,0,0.94)_0%,rgba(0,0,0,0.82)_42%,rgba(0,0,0,0.48)_100%)]"></div>
    <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-zinc-950 to-transparent"></div>

    <div class="relative z-10 mx-auto grid min-h-screen max-w-7xl grid-cols-1 gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8">
        <div class="flex items-end">
            <div class="max-w-xl pb-2 lg:pb-10">
                @if($eyebrow)
                    <p class="text-[10px] font-black uppercase tracking-[0.34em] text-[#70A7FF]">{{ $eyebrow }}</p>
                @endif

                <h1 class="mt-5 text-4xl font-black uppercase leading-none tracking-normal text-white sm:text-5xl lg:text-6xl">
                    {{ $title }}
                </h1>

                @if($description)
                    <p class="mt-6 text-base font-medium leading-7 text-zinc-300">{{ $description }}</p>
                @endif

                <div class="mt-8 grid grid-cols-3 gap-px bg-white/10 text-center">
                    <div class="bg-black/55 p-4 backdrop-blur">
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-500">Response</p>
                        <p class="mt-2 text-sm font-black uppercase text-white">24h</p>
                    </div>
                    <div class="bg-black/55 p-4 backdrop-blur">
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-500">Showroom</p>
                        <p class="mt-2 text-sm font-black uppercase text-white">BMW</p>
                    </div>
                    <div class="bg-black/55 p-4 backdrop-blur">
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-500">Support</p>
                        <p class="mt-2 text-sm font-black uppercase text-white">1:1</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center">
            <div class="w-full border border-white/10 bg-black/80 p-5 shadow-[0_35px_100px_rgba(0,0,0,0.45)] backdrop-blur-xl sm:p-8 lg:p-10">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
