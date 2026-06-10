<x-app-layout>
    @php
        $isAccessoryCatalog = ($filters['type'] ?? null) === 'accessory' && request()->routeIs('accessories.index');
        $filterAction = $isAccessoryCatalog ? route('accessories.index') : route('products.index');
        $catalogTitle = $isAccessoryCatalog ? 'Phụ kiện chính hãng' : 'BMW Showroom';
        $catalogEyebrow = $isAccessoryCatalog ? 'Accessories studio' : 'Model range';
        $catalogIntro = $isAccessoryCatalog
            ? 'Khám phá phụ kiện chính hãng, tư vấn tương thích và lắp đặt cho từng dòng BMW.'
            : 'Khám phá danh mục ô tô và Motorrad với bộ lọc giữ nguyên logic sản phẩm hiện có.';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $catalogEyebrow }}</p>
                <h1 class="mt-3 text-5xl font-black uppercase leading-none tracking-normal text-white md:text-7xl">
                    {{ $catalogTitle }}
                </h1>
                <p class="mt-5 max-w-2xl text-sm font-medium leading-6 text-zinc-500">{{ $catalogIntro }}</p>
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
                class="{{ $isAccessoryCatalog ? 'hidden' : 'flex' }} flex-col gap-3 border border-zinc-800 bg-zinc-950 px-5 py-4 sm:min-w-72"
            >
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-500">Comparison</p>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm font-black uppercase tracking-widest text-white">
                        <span x-text="comparisonIds.length"></span>/4 mẫu xe
                    </span>
                    <button type="button" @click="goToCompare()" class="bg-white px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:bg-[#1C69D4] hover:text-white">
                        So sánh
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-zinc-950 py-14 text-white sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-[280px_1fr] xl:gap-14">
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <form id="filterForm" action="{{ $filterAction }}" method="GET" class="border border-zinc-800 bg-zinc-950">
                        @if($isAccessoryCatalog)
                            <input type="hidden" name="type" value="accessory">
                        @endif

                        <div class="border-b border-zinc-800 p-6">
                            <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Bộ lọc</p>
                            <h2 class="mt-3 text-2xl font-black uppercase tracking-normal text-white">
                                {{ $isAccessoryCatalog ? 'Tìm phụ kiện' : 'Tìm mẫu xe' }}
                            </h2>
                        </div>

                        @unless($isAccessoryCatalog)
                            <div class="border-b border-zinc-800 p-6">
                                <h3 class="mb-5 text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Loại sản phẩm</h3>
                                <div class="space-y-4">
                                    @foreach($types as $type)
                                        <label class="group flex cursor-pointer items-center">
                                            <input
                                                type="radio"
                                                name="type"
                                                value="{{ $type->value }}"
                                                class="peer hidden"
                                                {{ request('type') == $type->value ? 'checked' : '' }}
                                                onchange="this.form.submit()"
                                            >
                                            <span class="mr-3 h-2 w-2 bg-zinc-800 transition-all group-hover:bg-[#1C69D4] peer-checked:bg-[#1C69D4]"></span>
                                            <span class="text-xs font-black uppercase tracking-widest text-zinc-500 transition-all group-hover:text-white peer-checked:text-white">
                                                {{ $type->label() }}
                                            </span>
                                        </label>
                                    @endforeach
                                    <a href="{{ route('products.index') }}" class="mt-4 block text-[10px] font-black uppercase tracking-widest text-zinc-600 transition-colors hover:text-[#1C69D4]">Xóa lọc loại</a>
                                </div>
                            </div>
                        @endunless

                        <div class="border-b border-zinc-800 p-6">
                            <h3 class="mb-5 text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">
                                {{ $isAccessoryCatalog ? 'Nhóm phụ kiện' : 'Phân khúc' }}
                            </h3>
                            <select name="category_id" onchange="this.form.submit()" class="w-full border border-zinc-800 bg-zinc-900 p-4 text-xs font-black uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                                <option value="">Tất cả</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-4 p-6">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Khoảng giá</h3>
                            <input
                                type="number"
                                name="min_price"
                                placeholder="Từ (VNĐ)"
                                value="{{ request('min_price') }}"
                                class="w-full border border-zinc-800 bg-zinc-900 p-4 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                            >
                            <input
                                type="number"
                                name="max_price"
                                placeholder="Đến (VNĐ)"
                                value="{{ request('max_price') }}"
                                class="w-full border border-zinc-800 bg-zinc-900 p-4 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                            >
                            <button type="submit" class="w-full bg-white py-4 text-[10px] font-black uppercase tracking-[0.25em] text-black transition-all hover:bg-[#1C69D4] hover:text-white">
                                Áp dụng
                            </button>
                        </div>
                    </form>
                </aside>

                <section>
                    <div class="mb-8 flex flex-col gap-5 border-b border-zinc-900 pb-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-500">
                            Hiển thị {{ $vehicles->count() }} / {{ $vehicles->total() }} kết quả
                        </p>
                        <select
                            name="sort"
                            form="filterForm"
                            onchange="document.getElementById('filterForm').submit()"
                            class="border border-zinc-800 bg-zinc-950 px-4 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                            <option value="latest" class="bg-zinc-950" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="price_low" class="bg-zinc-950" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Giá thấp đến cao</option>
                            <option value="price_high" class="bg-zinc-950" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Giá cao đến thấp</option>
                        </select>
                    </div>

                    @if($vehicles->isEmpty())
                        <div class="border border-zinc-800 bg-zinc-900/30 px-6 py-20 text-center">
                            <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Không có kết quả</p>
                            <h3 class="mt-4 text-4xl font-black uppercase tracking-normal text-white">
                                {{ $isAccessoryCatalog ? 'Chưa tìm thấy phụ kiện phù hợp' : 'Chưa tìm thấy mẫu xe phù hợp' }}
                            </h3>
                            <a href="{{ $isAccessoryCatalog ? route('accessories.index') : route('products.index') }}" class="mt-8 inline-flex border border-white px-8 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                                Xem tất cả
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($vehicles as $vehicle)
                                <x-vehicle-card :vehicle="$vehicle" />
                            @endforeach
                        </div>

                        <div class="mt-14">
                            {{ $vehicles->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
