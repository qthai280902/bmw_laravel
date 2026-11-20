@props([
    'keyId',
    'items' => [],
])

@if(count($items) > 0)
    <section
        wire:key="crm-mail-preview-{{ $keyId }}"
        x-data="{ openPreview: false }"
        class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4"
    >
        <button
            type="button"
            @click="openPreview = !openPreview"
            :aria-expanded="openPreview ? 'true' : 'false'"
            class="flex w-full items-center justify-between gap-3 text-left cursor-pointer group"
        >
            <span class="flex items-center gap-2 text-xs font-semibold text-zinc-200 group-hover:text-[#70A7FF] transition-colors">
                <svg class="h-4 w-4 shrink-0 text-[#38BDF8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>5. Xem trước mẫu Email (Chỉ xem — Không gửi thư)</span>
            </span>

            <svg
                class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-200"
                :class="openPreview ? 'rotate-180 text-[#70A7FF]' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="openPreview"
            x-cloak
            class="mt-3 grid grid-cols-1 gap-2 border-t border-white/[0.07] pt-3 sm:grid-cols-2"
        >
            @foreach($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    target="_blank"
                    rel="noopener"
                    class="{{ $item['spanClass'] ?? '' }} flex h-10 items-center justify-center rounded-xl border bg-[#0D121C] px-3 text-center text-xs font-medium transition-colors {{ $item['toneClass'] ?? 'border-white/10 text-zinc-200 hover:border-[#1C69D4] hover:text-white' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </section>
@endif
