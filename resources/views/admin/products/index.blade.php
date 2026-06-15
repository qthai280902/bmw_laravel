<x-admin-layout>
    <x-admin.page-header
        eyebrow="Inventory control"
        title="Kho sản phẩm"
        accent="Showroom"
        description="Quản lý ô tô, Motorrad và phụ kiện chính hãng với bộ lọc giữ nguyên logic Phase 13."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng hiển thị</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $products->total() }}</p>
        </x-slot>

        <x-slot name="actions">
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center border border-white bg-white px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                Thêm sản phẩm
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.card class="mb-6 !p-5">
        <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 gap-4 xl:grid-cols-[1.5fr_1fr_1fr_auto] xl:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm">
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Tên sản phẩm..."
                    class="w-full border-zinc-800 bg-black px-4 py-3 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="type" label="Loại">
                <select id="type" name="type" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả</option>
                    <option value="car" {{ request('type') === 'car' ? 'selected' : '' }}>Ô tô</option>
                    <option value="motorbike" {{ request('type') === 'motorbike' ? 'selected' : '' }}>Xe máy</option>
                    <option value="accessory" {{ request('type') === 'accessory' ? 'selected' : '' }}>Phụ kiện</option>
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="category_id" label="Dòng xe">
                <select id="category_id" name="category_id" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="border border-zinc-700 bg-zinc-900 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]">
                    Lọc
                </button>
                <a href="{{ route('admin.products.index') }}" class="border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                    Xóa
                </a>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1060px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Sản phẩm</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Loại</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Giá</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Tồn kho</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($products as $product)
                        @php $primaryImg = $product->images->where('is_primary', true)->first(); @endphp
                        <tr class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-14 w-24 shrink-0 overflow-hidden border border-zinc-800 bg-black">
                                        <img src="{{ $product->displayImageUrl($primaryImg) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black uppercase tracking-wider text-white group-hover:text-[#70A7FF]">{{ $product->name }}</p>
                                        <p class="mt-1 truncate text-[10px] font-black uppercase tracking-[0.2em] text-zinc-700">{{ $product->category?->name ?? 'Không phân loại' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <x-admin.badge tone="blue">{{ $product->type->label() }}</x-admin.badge>
                            </td>
                            <td class="px-6 py-5 text-right text-sm font-black tabular-nums text-zinc-200">
                                {{ number_format($product->price) }} <span class="text-xs text-zinc-600">VNĐ</span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="inline-flex items-center gap-2 text-xs font-black {{ $product->stock > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    <span class="h-1.5 w-1.5 {{ $product->stock > 0 ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-end gap-5">
                                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF] transition-colors hover:text-white">
                                        Public
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:text-white">
                                        Sửa
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="admin-delete-form" data-confirm-message="Xác nhận xóa sản phẩm này khỏi kho showroom?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-700 transition-colors hover:text-rose-400">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Kho sản phẩm trống"
                                    description="Không tìm thấy sản phẩm nào theo bộ lọc hiện tại."
                                    :href="route('admin.products.create')"
                                    action="Thêm sản phẩm"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($products->hasPages())
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</x-admin-layout>
