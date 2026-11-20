@props([
    'guide' => null,
])

@if(is_array($guide))
    <div
        x-data="{ open: false }"
        @keydown.escape.window="open = false"
        class="relative inline-flex items-center"
    >
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open ? 'true' : 'false'"
            aria-label="Hướng dẫn sử dụng trang"
            title="Hướng dẫn nhanh"
            class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/15 bg-[#0D121C] text-xs font-bold text-zinc-300 transition-colors hover:border-[#38BDF8]/60 hover:bg-[#131C2E] hover:text-[#38BDF8] focus:outline-none focus:ring-2 focus:ring-[#1C69D4] cursor-pointer"
        >
            ?
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
            @click.outside="open = false"
            role="dialog"
            aria-label="{{ $guide['title'] }}"
            class="absolute left-0 top-9 z-50 w-80 sm:w-96 rounded-2xl border border-white/15 bg-[#0D121C] p-5 text-left shadow-2xl shadow-black/80"
        >
            <div class="flex items-start justify-between gap-3 border-b border-white/[0.08] pb-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#38BDF8]">Hướng dẫn thao tác</p>
                    <h3 class="mt-0.5 text-sm font-bold text-white">{{ $guide['title'] }}</h3>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    aria-label="Đóng hướng dẫn"
                    class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-white/10 hover:text-white cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="mt-3 text-xs leading-relaxed text-zinc-300">
                {{ $guide['summary'] }}
            </p>

            @if(! empty($guide['bullets']))
                <ul class="mt-3 space-y-2 border-t border-white/[0.06] pt-3">
                    @foreach($guide['bullets'] as $bullet)
                        <li class="flex items-start gap-2 text-xs leading-relaxed text-zinc-400">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#38BDF8]"></span>
                            <span>{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
