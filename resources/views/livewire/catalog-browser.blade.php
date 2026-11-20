<div class="bg-zinc-950 py-14 text-white sm:py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[280px_1fr] xl:gap-14">
            <aside class="lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto lg:pb-8 lg:pr-2 scrollbar-none">
                <form
                    id="filterForm"
                    action="{{ $filterAction }}"
                    method="GET"
                    wire:submit.prevent="$refresh"
                    class="rounded-2xl border border-white/[0.06] bg-zinc-950/90 p-6 shadow-xl shadow-black/50 backdrop-blur"
                >
                    @if($isAccessoryCatalog)
                        <input type="hidden" name="type" value="accessory">
                    @endif

                    <div class="mb-5 border-b border-zinc-800/60 pb-5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-[#70A7FF]">Bộ lọc</p>
                            @if($search !== '' || ($type !== '' && ! $lockedType) || $categoryId !== '' || $minPrice !== '' || $maxPrice !== '' || $sort !== 'latest')
                                <button
                                    type="button"
                                    wire:click="resetFilters"
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:text-white"
                                >
                                    Đặt lại
                                </button>
                            @endif
                        </div>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white">
                            {{ $isAccessoryCatalog ? 'Tìm phụ kiện' : 'Tìm mẫu xe' }}
                        </h2>
                        <div class="mt-4">
                            <label for="catalog-search-input" class="sr-only">Tìm theo tên hoặc từ khóa</label>
                            <input
                                id="catalog-search-input"
                                type="text"
                                name="search"
                                wire:model.live.debounce.350ms="search"
                                placeholder="{{ $isAccessoryCatalog ? 'Nhập tên phụ kiện...' : 'Nhập tên mẫu xe, dòng xe...' }}"
                                class="w-full rounded-xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                            >
                        </div>
                    </div>

                    @unless($lockedType === 'accessory')
                        <div x-data="{ open: true }" class="mb-5 border-b border-zinc-800/60 pb-5">
                            <button
                                type="button"
                                @click="open = !open"
                                class="group/acc flex w-full items-center justify-between py-1 text-left"
                            >
                                <h3 class="text-[10px] font-semibold uppercase tracking-[0.25em] text-zinc-400 transition-colors group-hover/acc:text-white">Loại sản phẩm</h3>
                                <svg
                                    class="h-4 w-4 text-zinc-500 transition-transform duration-300"
                                    :class="open ? 'rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-collapse>
                                <div class="space-y-4 pt-4">
                                    @foreach($types as $vehicleType)
                                        <label class="group flex cursor-pointer items-center" wire:key="catalog-type-{{ $vehicleType->value }}">
                                            <input
                                                type="radio"
                                                name="type"
                                                value="{{ $vehicleType->value }}"
                                                wire:model.live="type"
                                                class="peer hidden"
                                            >
                                            <span class="mr-3 h-2 w-2 bg-zinc-800 transition-all group-hover:bg-[#1C69D4] peer-checked:bg-[#1C69D4]"></span>
                                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 transition-all group-hover:text-white peer-checked:text-white">
                                                {{ $vehicleType->label() }}
                                            </span>
                                        </label>
                                    @endforeach
                                    <button
                                        type="button"
                                        wire:click="selectType('')"
                                        class="mt-4 block text-[10px] font-black uppercase tracking-widest text-zinc-500 transition-colors hover:text-[#1C69D4]"
                                    >
                                        Xóa lọc loại
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endunless

                    <div x-data="{ open: true }" class="mb-5 border-b border-zinc-800/60 pb-5">
                        <button
                            type="button"
                            @click="open = !open"
                            class="group/acc flex w-full items-center justify-between py-1 text-left"
                        >
                            <h3 class="text-[10px] font-semibold uppercase tracking-[0.25em] text-zinc-400 transition-colors group-hover/acc:text-white">
                                {{ $isAccessoryCatalog ? 'Nhóm phụ kiện' : 'Phân khúc' }}
                            </h3>
                            <svg
                                class="h-4 w-4 text-zinc-500 transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-collapse>
                            <div class="pt-4">
                                <x-ui.custom-select
                                    name="category_id"
                                    :value="$categoryId"
                                    :options="['' => 'Tất cả'] + $categories->pluck('name', 'id')->all()"
                                    wire-model="categoryId"
                                />
                            </div>
                        </div>
                    </div>

                    <div x-data="{ open: true }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="group/acc flex w-full items-center justify-between py-1 text-left"
                        >
                            <h3 class="text-[10px] font-semibold uppercase tracking-[0.25em] text-zinc-400 transition-colors group-hover/acc:text-white">Khoảng giá</h3>
                            <svg
                                class="h-4 w-4 text-zinc-500 transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-collapse>
                            <div class="space-y-4 pt-4">
                                <input
                                    type="number"
                                    name="min_price"
                                    wire:model.live.debounce.400ms="minPrice"
                                    placeholder="Từ (VNĐ)"
                                    class="w-full rounded-xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                >
                                <input
                                    type="number"
                                    name="max_price"
                                    wire:model.live.debounce.400ms="maxPrice"
                                    placeholder="Đến (VNĐ)"
                                    class="w-full rounded-xl border border-zinc-800 bg-zinc-900 p-4 text-sm text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                >
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-white py-4 text-[10px] font-black uppercase tracking-[0.25em] text-black transition-all hover:bg-[#1C69D4] hover:text-white"
                                >
                                    Áp dụng
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </aside>

            <section class="relative">
                <div class="mb-8 flex flex-col gap-5 border-b border-zinc-900 pb-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-400">
                            Hiển thị {{ $vehicles->count() }} / {{ $vehicles->total() }} kết quả
                        </p>
                        <span
                            wire:loading.flex
                            class="items-center gap-2 border border-[#1C69D4]/40 bg-[#1C69D4]/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]"
                        >
                            <span class="h-1.5 w-1.5 animate-pulse bg-[#1C69D4]"></span>
                            Đang lọc...
                        </span>
                    </div>

                    <div class="w-full sm:w-56">
                        <x-ui.custom-select
                            name="sort"
                            :value="$sort"
                            :options="[
                                'latest' => 'Mới nhất',
                                'price_low' => 'Giá thấp đến cao',
                                'price_high' => 'Giá cao đến thấp',
                            ]"
                            wire-model="sort"
                        />
                    </div>
                </div>

                <div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity duration-200">
                    @if($vehicles->isEmpty())
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/30 px-6 py-20 text-center">
                            <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Không có kết quả</p>
                            <h3 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl">
                                {{ $isAccessoryCatalog ? 'Chưa tìm thấy phụ kiện phù hợp' : 'Chưa tìm thấy mẫu xe phù hợp' }}
                            </h3>
                            <button
                                type="button"
                                wire:click="resetFilters"
                                class="mt-8 inline-flex rounded-lg border border-white px-8 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black"
                            >
                                Xem tất cả
                            </button>
                        </div>
                    @else
                        <div id="catalogGrid" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($vehicles as $vehicle)
                                <div wire:key="catalog-product-{{ $vehicle->id }}" class="h-full">
                                    <x-vehicle-card :vehicle="$vehicle" />
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-14">
                            {{ $vehicles->links() }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
