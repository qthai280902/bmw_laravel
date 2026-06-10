<x-admin-layout>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">Dòng xe <span class="font-black text-[#1C69D4]">BMW</span></h1>
            <p class="text-zinc-500 font-medium font-outfit">Quản lý các dòng sản phẩm và phân khúc BMW.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="group relative px-8 py-4 bg-white text-black font-black uppercase text-sm tracking-widest hover:bg-zinc-200 transition-all shadow-2xl overflow-hidden">
            <span class="relative z-10">Thêm dòng xe mới</span>
            <div class="absolute inset-0 bg-zinc-200 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
        </a>
    </div>

    <!-- Table -->
    <x-admin.card class="!p-0 border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Dòng xe</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Mô tả phân đoạn</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($categories as $category)
                        <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="text-sm font-black uppercase tracking-wider text-white group-hover:text-[#1C69D4] transition-colors leading-none mb-1">
                                    {{ $category->name }}
                                </div>
                                <div class="text-[9px] text-zinc-600 font-black uppercase tracking-widest ">
                                    {{ $category->slug }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-zinc-400 text-xs  font-medium max-w-md">
                                {{ $category->description ?? 'Chưa có mô tả chi tiết cho dòng sản phẩm này.' }}
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end items-center gap-6">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition-all">Hiệu chỉnh</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="admin-delete-form" data-confirm-message="Cảnh báo: Xóa dòng xe này có thể ảnh hưởng đến các sản phẩm liên quan. Tiếp tục?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-zinc-700 hover:text-rose-500 transition-all">Gỡ bỏ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-8 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-900/50 rounded-full flex items-center justify-center border border-zinc-800 mb-6">
                                        <svg class="w-6 h-6 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500">Chưa có dòng xe</h3>
                                    <p class="text-[10px] text-zinc-700 font-bold mt-2 uppercase tracking-tight">Vui lòng khởi tạo các dòng xe BMW để bắt đầu quản lý kho xe.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="mt-8">
        {{ $categories->links() }}
    </div>
</x-admin-layout>
