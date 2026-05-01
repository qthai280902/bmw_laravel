<x-app-layout>
    <x-slot name="header">
        <h2 class="text-4xl font-black text-white uppercase tracking-tighter ">
            BMW <span class="text-accent underline decoration-4">Showroom</span>
        </h2>
        <p class="text-zinc-500 mt-2 uppercase text-xs tracking-widest font-bold">Khám phá tuyệt tác cơ khí Đức</p>
    </x-slot>

    <div class="py-12" x-data="{ 
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
    }" @comparison-updated.window="updateComparison()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-12">
                
                <!-- Sidebar Filters -->
                <aside class="w-full md:w-64 flex-shrink-0">
                    <form action="{{ route('products.index') }}" method="GET" class="space-y-10">
                        <!-- Category Filter -->
                        <div class="border-t border-zinc-900 pt-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-white mb-6">Dòng xe</h3>
                            <div class="space-y-4">
                                @foreach($types as $type)
                                    <label class="flex items-center group cursor-pointer">
                                        <input type="radio" name="type" value="{{ $type->value }}" 
                                            class="hidden peer"
                                            {{ request('type') == $type->value ? 'checked' : '' }}
                                            onchange="this.form.submit()">
                                        <span class="w-2 h-2 bg-zinc-800 group-hover:bg-accent peer-checked:bg-accent transition-all mr-3"></span>
                                        <span class="text-sm uppercase font-bold text-zinc-500 group-hover:text-white peer-checked:text-white transition-all">
                                            {{ $type->name }}
                                        </span>
                                    </label>
                                @endforeach
                                <a href="{{ route('products.index') }}" class="text-[10px] uppercase font-black text-zinc-600 hover:text-accent block mt-4">Xóa lọc</a>
                            </div>
                        </div>

                        <!-- Phân khúc Filter -->
                        <div class="border-t border-zinc-900 pt-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-white mb-6">Phân khúc xe</h3>
                            <select name="category_id" onchange="this.form.submit()" 
                                class="w-full bg-zinc-900 border-none text-zinc-400 text-xs font-bold uppercase tracking-widest p-4 focus:ring-1 focus:ring-accent">
                                <option value="">Tất cả phân khúc</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Price Filter -->
                        <div class="border-t border-zinc-900 pt-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-white mb-6">Khoảng giá</h3>
                            <div class="space-y-4">
                                <input type="number" name="min_price" placeholder="Từ (VNĐ)" value="{{ request('min_price') }}"
                                    class="w-full bg-zinc-900 border-none text-white text-sm p-4 focus:ring-1 focus:ring-accent placeholder-zinc-700">
                                <input type="number" name="max_price" placeholder="Đến (VNĐ)" value="{{ request('max_price') }}"
                                    class="w-full bg-zinc-900 border-none text-white text-sm p-4 focus:ring-1 focus:ring-accent placeholder-zinc-700">
                                <button type="submit" class="w-full bg-white text-black text-[10px] font-black uppercase tracking-widest py-4 hover:bg-accent hover:text-white transition-all">
                                    Áp dụng
                                </button>
                            </div>
                        </div>
                    </form>
                </aside>

                <!-- Product Grid -->
                <div class="flex-grow">
                    <div class="flex justify-between items-center mb-10 border-b border-zinc-900 pb-6">
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500">
                            Hiển thị {{ $vehicles->count() }} / {{ $vehicles->total() }} kết quả
                        </p>
                        
                        <div class="flex items-center gap-4">
                            <select name="sort" form="filterForm" onchange="this.form.submit()"
                                class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-white focus:ring-0">
                                <option value="latest" class="bg-zinc-950">Mới nhất</option>
                                <option value="price_low" class="bg-zinc-950">Giá thấp dần</option>
                                <option value="price_high" class="bg-zinc-950">Giá cao dần</option>
                            </select>
                        </div>
                    </div>

                    @if($vehicles->isEmpty())
                        <div class="py-20 text-center">
                            <h3 class="text-4xl font-black text-zinc-900 uppercase  mb-4">Không tìm thấy xe</h3>
                            <a href="{{ route('products.index') }}" class="text-accent uppercase text-xs font-black tracking-widest border-b-2 border-accent pb-1">Xem tất cả dòng xe</a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
                            @foreach($vehicles as $vehicle)
                                <x-vehicle-card :vehicle="$vehicle" />
                            @endforeach
                        </div>

                        <div class="mt-16">
                            {{ $vehicles->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Floating Comparison Bar (CTO Standard) -->
        <template x-if="comparisonIds.length > 0">
            <div class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-2xl">
                <div class="bg-white p-4 flex items-center justify-between shadow-2xl skew-x-[-10deg]">
                    <div class="flex items-center gap-4 skew-x-[10deg] px-4">
                        <div class="flex -space-x-2">
                            <template x-for="i in comparisonIds.length">
                                <div class="w-8 h-8 bg-accent border-2 border-white flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </template>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-black">
                            Đã chọn <span x-text="comparisonIds.length"></span>/4 xe để so sánh
                        </p>
                    </div>
                    <div class="flex gap-2 skew-x-[10deg]">
                        <button @click="localStorage.removeItem('bmw_comparison_ids'); updateComparison()" 
                            class="text-[10px] font-black uppercase tracking-widest text-zinc-400 hover:text-black px-4 py-2">
                            Xóa hết
                        </button>
                        <button @click="goToCompare()" 
                            class="bg-accent text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 hover:bg-black transition-all">
                            So sánh ngay
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
