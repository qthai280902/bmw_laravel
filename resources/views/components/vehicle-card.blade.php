@props(['vehicle', 'showCompare' => true])

@php
    $isAccessory = $vehicle->canOrderAccessory();
    $categoryName = $vehicle->category?->name ?? 'BMW Showroom';
    $primaryAction = $isAccessory
        ? route('accessory-orders.create', $vehicle->slug)
        : route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'test_drive']);
    $primaryActionLabel = $isAccessory ? 'Đặt hàng' : 'Lái thử';
@endphp

<article
    x-data="{
        isSelected: isVehicleSelected({{ $vehicle->id }}),
        toggle() {
            toggleComparison({{ $vehicle->id }});
            this.isSelected = isVehicleSelected({{ $vehicle->id }});
        }
    }"
    class="group relative flex h-full flex-col overflow-hidden border border-zinc-900 bg-zinc-950 transition-all duration-500 hover:border-[#1C69D4]"
>
    @if($vehicle->is_featured)
        <div class="absolute left-4 top-4 z-10">
            <span class="bg-[#1C69D4] px-3 py-1 text-[10px] font-black uppercase tracking-widest text-white">
                Premium
            </span>
        </div>
    @endif

    @if($showCompare && $vehicle->canCompare())
        <div class="absolute right-4 top-4 z-10">
            <button
                type="button"
                @click.prevent="toggle()"
                :class="isSelected ? 'bg-[#1C69D4] text-white' : 'bg-zinc-950/70 text-zinc-300 hover:text-white'"
                class="flex h-10 w-10 items-center justify-center border border-white/10 backdrop-blur transition-all duration-300"
                aria-label="Thêm vào so sánh"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!isSelected" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    <path x-show="isSelected" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
    @endif

    <a href="{{ route('products.show', $vehicle->slug) }}" class="block shrink-0">
        <div class="h-64 overflow-hidden bg-zinc-900 sm:h-72 lg:h-64">
            <img
                loading="lazy"
                src="{{ $vehicle->displayImageUrl() }}"
                alt="{{ $vehicle->name }}"
                class="h-full w-full object-cover transition-transform duration-1000 group-hover:scale-105"
            >
        </div>
    </a>

    <div class="flex flex-1 flex-col p-6 sm:p-8">
        <div class="flex-1 space-y-8">
            <div class="space-y-3">
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">{{ $categoryName }}</p>
                <a href="{{ route('products.show', $vehicle->slug) }}" class="block">
                    <h3 class="min-h-16 text-2xl font-black uppercase leading-none tracking-normal text-white transition-colors group-hover:text-[#1C69D4]">
                        {{ $vehicle->name }}
                    </h3>
                </a>
                <p class="min-h-12 text-sm font-medium leading-6 text-zinc-500">
                    {{ $isAccessory ? 'Phụ kiện chính hãng, tư vấn tương thích và lắp đặt tại showroom.' : 'Cấu hình showroom, tư vấn trải nghiệm và báo giá theo nhu cầu.' }}
                </p>
            </div>

            <div class="border-t border-zinc-900 pt-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Giá niêm yết</p>
                <p class="mt-2 text-2xl font-black tracking-normal text-white">
                    {{ number_format($vehicle->price) }} <span class="text-xs text-zinc-500">VNĐ</span>
                </p>
                @if($vehicle->deposit_amount)
                    <p class="mt-2 text-[10px] font-bold uppercase tracking-widest text-zinc-600">
                        Cọc tham khảo: <span class="text-zinc-400">{{ number_format($vehicle->deposit_amount) }} VNĐ</span>
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-8 grid grid-cols-[auto_1fr] gap-3">
            <a
                href="{{ $primaryAction }}"
                title="{{ $primaryActionLabel }}"
                class="flex h-11 w-11 items-center justify-center border border-zinc-800 bg-zinc-900 text-zinc-300 transition-all duration-300 hover:border-[#1C69D4] hover:text-[#1C69D4]"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $isAccessory ? 'M3 7h18M6 7l1 13h10l1-13M9 7V5a3 3 0 016 0v2' : 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' }}" />
                </svg>
            </a>

            <a
                href="{{ route('products.show', $vehicle->slug) }}"
                class="inline-flex items-center justify-between border border-zinc-800 px-4 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:border-white hover:bg-white hover:text-black"
            >
                Khám phá
                <svg class="h-4 w-4 text-[#1C69D4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</article>
