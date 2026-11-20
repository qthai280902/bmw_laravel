<x-app-layout>
    @php
        $isAccessoryCatalog = ($filters['type'] ?? null) === 'accessory';
        $catalogTitle = $isAccessoryCatalog ? 'Phụ kiện chính hãng' : 'BMW Showroom';
        $catalogEyebrow = $isAccessoryCatalog ? 'Accessories studio' : 'Model range';
        $catalogIntro = $isAccessoryCatalog
            ? 'Khám phá phụ kiện chính hãng, tư vấn tương thích và lắp đặt cho từng dòng BMW.'
            : 'Khám phá danh mục ô tô và Motorrad với bộ lọc thời gian thực theo chuẩn BMW Showroom.';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.35em] text-[#70A7FF]">{{ $catalogEyebrow }}</p>
                <h1 class="mt-3 text-5xl font-extrabold leading-none tracking-tight text-white md:text-7xl">
                    {{ $catalogTitle }}
                </h1>
                <p class="mt-5 max-w-2xl text-sm font-medium leading-relaxed text-zinc-400">{{ $catalogIntro }}</p>
            </div>
            <div
                x-data="{
                    comparisonIds: getComparisonIds(),
                    updateComparison() {
                        this.comparisonIds = getComparisonIds();
                    },
                    goToCompare() {
                        if (this.comparisonIds.length < 2) {
                            alert('Vui lòng chọn ít nhất 2 xe để so sánh.');
                            return;
                        }
                        window.location.href = '{{ route('products.compare') }}?ids=' + this.comparisonIds.join(',');
                    }
                }"
                @comparison-updated.window="updateComparison()"
                class="{{ $isAccessoryCatalog ? 'hidden' : 'flex' }} flex-col gap-3 rounded-xl border border-zinc-800 bg-zinc-950 px-5 py-4 sm:min-w-72"
            >
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-500">Comparison</p>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm font-black uppercase tracking-widest text-white">
                        <span x-text="comparisonIds.length"></span>/4 mẫu xe
                    </span>
                    <button type="button" @click="goToCompare()" class="rounded-lg bg-white px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:bg-[#1C69D4] hover:text-white">
                        So sánh
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    <livewire:catalog-browser :locked-type="$isAccessoryCatalog ? 'accessory' : null" />
</x-app-layout>
