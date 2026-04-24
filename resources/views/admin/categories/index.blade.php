<x-admin-layout>
    <div class="flex justify-between items-end mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2">Vehicle <span class="font-black text-[#1C69D4]">Lines</span></h1>
            <p class="text-zinc-500 font-medium font-outfit">Quản lý các dòng sản phẩm của BMW.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="px-8 py-4 bg-[#1C69D4] text-white font-black uppercase text-sm tracking-widest hover:bg-blue-600 transition-colors shadow-2xl">
            Thêm dòng xe mới
        </a>
    </div>

    <!-- Table -->
    <x-admin.card class="p-0">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Dòng xe</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Mô tả</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-900">
                @forelse($categories as $category)
                    <tr class="group hover:bg-zinc-950/50 transition-colors">
                        <td class="px-8 py-6">
                            <div class="text-sm font-black uppercase tracking-wider group-hover:text-[#1C69D4] transition-colors">
                                {{ $category->name }}
                            </div>
                            <div class="text-[10px] text-zinc-600 font-bold uppercase">{{ $category->slug }}</div>
                        </td>
                        <td class="px-8 py-6 text-zinc-400 text-sm max-w-md">
                            {{ $category->description ?? '---' }}
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-4">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="text-[10px] font-black uppercase text-[#1C69D4] hover:text-white transition-colors">Sửa</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Xóa dòng xe này? Điều này có thể ảnh hưởng đến các sản phẩm thuộc dòng này.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[10px] font-black uppercase text-zinc-600 hover:text-rose-500 transition-colors">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-8 py-24 text-center">
                            <div class="text-zinc-600 font-black uppercase text-xs">Chưa có dòng xe nào được cấu hình.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.card>

    <div class="mt-8">
        {{ $categories->links() }}
    </div>
</x-admin-layout>
