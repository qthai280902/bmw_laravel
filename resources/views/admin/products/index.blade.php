<x-admin-layout>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">Kho xe <span class="font-black text-[#1C69D4]">Showroom</span></h1>
            <p class="text-zinc-500 font-medium font-outfit">Quản lý danh sách xe và cấu hình kỹ thuật BMW.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="group relative px-8 py-4 bg-white text-black font-black uppercase text-sm tracking-widest hover:bg-zinc-200 transition-all shadow-2xl overflow-hidden">
            <span class="relative z-10">Thêm sản phẩm mới</span>
            <div class="absolute inset-0 bg-zinc-200 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
        </a>
    </div>

    <!-- Filters -->
    <x-admin.card class="mb-8 !p-6">
        <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-6 items-end">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest">Tìm kiếm</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên xe..." class="w-full bg-zinc-950 border-zinc-800 text-white text-sm focus:border-zinc-500 focus:ring-0 placeholder-zinc-800 transition-all">
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest">Loại xe</label>
                <select name="type" class="w-full bg-zinc-950 border-zinc-800 text-white text-sm focus:border-zinc-500 focus:ring-0 transition-all">
                    <option value="">Tất cả</option>
                    <option value="car" {{ request('type') == 'car' ? 'selected' : '' }}>Ô tô</option>
                    <option value="motorbike" {{ request('type') == 'motorbike' ? 'selected' : '' }}>Xe máy</option>
                    <option value="accessory" {{ request('type') == 'accessory' ? 'selected' : '' }}>Phụ kiện</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest">Dòng xe</label>
                <select name="category_id" class="w-full bg-zinc-950 border-zinc-800 text-white text-sm focus:border-zinc-500 focus:ring-0 transition-all">
                    <option value="">Tất cả</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-zinc-800 text-white font-black uppercase text-[10px] tracking-widest hover:bg-zinc-700 transition-all border border-zinc-700">
                    Lọc
                </button>
                <a href="{{ route('admin.products.index') }}" class="flex-1 px-4 py-2 border border-zinc-800 text-zinc-500 font-black uppercase text-[10px] tracking-widest hover:text-white hover:bg-zinc-900 transition-all text-center">
                    Xóa
                </a>
            </div>
        </form>
    </x-admin.card>

    <!-- Table -->
    <x-admin.card class="!p-0 border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Sản phẩm</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Phân loại</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-right">Giá niêm yết</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-center">Tồn kho</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($products as $product)
                        <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-5">
                                    @php $primaryImg = $product->images->where('is_primary', true)->first(); @endphp
                                    <div class="relative w-20 h-12 bg-black border border-zinc-800 overflow-hidden shrink-0">
                                        <img src="{{ $product->displayImageUrl($primaryImg) }}"
                                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 {{ $primaryImg ? 'grayscale group-hover:grayscale-0' : 'opacity-20' }}">
                                    </div>
                                    <div>
                                        <div class="text-sm font-black uppercase tracking-wider text-white group-hover:text-[#1C69D4] transition-colors leading-none mb-1">
                                            {{ $product->name }}
                                        </div>
                                        <div class="text-[9px] text-zinc-600 font-black uppercase tracking-widest ">
                                            {{ $product->category?->name ?? 'Không phân loại' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-2.5 py-1 bg-zinc-950 border border-zinc-800 text-[9px] font-black uppercase text-zinc-500 tracking-tighter group-hover:border-zinc-700 transition-colors">
                                    {{ $product->type->label() }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right font-black text-sm tabular-nums text-zinc-300">
                                {{ number_format($product->price) }} <small class="text-zinc-600 ml-1">VNĐ</small>
                            </td>
                            <td class="px-8 py-6 text-center tabular-nums">
                                <div class="inline-flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $product->stock > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    <span class="text-xs font-bold {{ $product->stock > 0 ? 'text-zinc-400' : 'text-rose-500' }}">
                                        {{ $product->stock }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end items-center gap-6">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-all">Sửa</a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="admin-delete-form" data-confirm-message="Xác nhận xóa xe này khỏi kho lưu trữ?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-zinc-700 hover:text-rose-500 transition-all">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-900/50 rounded-full flex items-center justify-center border border-zinc-800 mb-6">
                                        <svg class="w-6 h-6 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500">Kho xe trống</h3>
                                    <p class="text-[10px] text-zinc-700 font-bold mt-2 uppercase tracking-tight">Không tìm thấy xe nào trong danh sách hiển thị.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="mt-12">
        {{ $products->links() }}
    </div>
</x-admin-layout>
