<x-admin-layout>
    <div class="flex justify-between items-end mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2">Showroom <span class="font-black text-[#1C69D4]">Inventory</span></h1>
            <p class="text-zinc-500 font-medium">Quản lý danh sách xe và cấu hình kỹ thuật.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="px-8 py-4 bg-[#1C69D4] text-white font-black uppercase text-sm tracking-widest hover:bg-blue-600 transition-colors shadow-2xl">
            Thêm sản phẩm mới
        </a>
    </div>

    <!-- Filters -->
    <x-admin.card class="mb-12">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-6 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Tìm kiếm</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên xe..." class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0 placeholder-zinc-700">
            </div>

            <div class="w-48">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Loại xe</label>
                <select name="type" class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                    <option value="">Tất cả</option>
                    <option value="car" {{ request('type') == 'car' ? 'selected' : '' }}>Ô tô</option>
                    <option value="motorbike" {{ request('type') == 'motorbike' ? 'selected' : '' }}>Xe máy</option>
                </select>
            </div>

            <div class="w-48">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Hãng xe</label>
                <select name="brand_id" class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                    <option value="">Tất cả</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-6 py-2 bg-zinc-800 text-white font-black uppercase text-xs tracking-widest hover:bg-zinc-700 transition-colors">
                Lọc kết quả
            </button>
            <a href="{{ route('admin.products.index') }}" class="px-6 py-2 border border-zinc-800 text-zinc-500 font-black uppercase text-xs tracking-widest hover:text-white transition-colors">
                Xóa lọc
            </a>
        </form>
    </x-admin.card>

    <!-- Table -->
    <x-admin.card class="p-0">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Sản phẩm</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Loại</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-right">Giá niêm yết</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-center">Tồn kho</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-900">
                @forelse($products as $product)
                    <tr class="group hover:bg-zinc-950/50 transition-colors">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                @if($product->images->where('is_primary', true)->first())
                                    <img src="{{ asset('storage/' . $product->images->where('is_primary', true)->first()->path) }}" class="w-16 h-10 object-cover grayscale group-hover:grayscale-0 transition-all duration-500">
                                @else
                                    <div class="w-16 h-10 bg-zinc-900 border border-zinc-800 flex items-center justify-center text-[8px] uppercase font-black text-zinc-700 italic">No Image</div>
                                @endif
                                <div>
                                    <div class="text-sm font-black uppercase tracking-wider group-hover:text-[#1C69D4] transition-colors">{{ $product->name }}</div>
                                    <div class="text-[10px] text-zinc-600 font-bold uppercase">{{ $product->brand->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-2 py-1 bg-zinc-950 border border-zinc-800 text-[10px] font-black uppercase text-zinc-400">
                                {{ $product->type->label() }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right font-black text-sm tabular-nums">
                            {{ number_format($product->price) }} <small class="text-zinc-600">VNĐ</small>
                        </td>
                        <td class="px-8 py-6 text-center tabular-nums">
                            <span class="text-sm font-bold {{ $product->stock > 0 ? 'text-zinc-300' : 'text-rose-500' }}">
                                {{ $product->stock }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-4">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-[10px] font-black uppercase text-[#1C69D4] hover:text-white transition-colors">Sửa</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Xóa xe này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[10px] font-black uppercase text-zinc-600 hover:text-rose-500 transition-colors">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-8 py-24 text-center">
                            <div class="text-zinc-600 font-black uppercase text-xs">Hiện tại không có xe nào trong Showroom.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.card>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</x-admin-layout>
