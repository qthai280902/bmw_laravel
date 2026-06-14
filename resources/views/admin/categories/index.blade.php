<x-admin-layout>
    <x-admin.page-header
        eyebrow="Showroom taxonomy"
        title="Dòng xe"
        accent="BMW"
        description="Quản lý phân khúc sản phẩm dùng cho catalog, bộ lọc và product CRUD."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng dòng</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $categories->total() }}</p>
        </x-slot>

        <x-slot name="actions">
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center justify-center border border-white bg-white px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                Thêm dòng xe
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Dòng xe</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Mô tả</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($categories as $category)
                        <tr class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white group-hover:text-[#70A7FF]">{{ $category->name }}</p>
                                <p class="mt-1 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-700">{{ $category->slug }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-2xl text-sm font-medium leading-6 text-zinc-500">
                                    {{ $category->description ?? 'Chưa có mô tả cho dòng sản phẩm này.' }}
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-end gap-5">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:text-white">
                                        Sửa
                                    </a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="admin-delete-form" data-confirm-message="Xóa dòng xe này có thể ảnh hưởng đến sản phẩm liên quan. Tiếp tục?">
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
                            <td colspan="3" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Chưa có dòng xe"
                                    description="Khởi tạo các dòng xe hoặc nhóm sản phẩm để bắt đầu quản lý catalog."
                                    :href="route('admin.categories.create')"
                                    action="Thêm dòng xe"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($categories->hasPages())
        <div class="mt-8">
            {{ $categories->links() }}
        </div>
    @endif
</x-admin-layout>
