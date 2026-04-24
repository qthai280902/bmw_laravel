<x-admin-layout>
    <div class="mb-12">
        <h1 class="text-6xl font-light uppercase tracking-tighter mb-2">New <span class="font-black text-[#1C69D4]">Line</span></h1>
        <p class="text-zinc-500 font-medium font-outfit">Thêm dòng xe mới cho Showroom BMW.</p>
    </div>

    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div class="max-w-2xl space-y-12">
            <x-admin.card>
                <h3 class="text-xs font-black uppercase tracking-widest text-[#1C69D4] mb-8">Thông tin dòng xe</h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Tên dòng xe</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ví dụ: Sedan, SAV, BMW i..." class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Mô tả</label>
                        <textarea name="description" rows="4" class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">{{ old('description') }}</textarea>
                    </div>
                </div>
            </x-admin.card>

            <div class="flex gap-4">
                <button type="submit" class="flex-1 py-4 bg-[#1C69D4] text-white font-black uppercase text-sm tracking-widest hover:bg-blue-600 transition-colors shadow-2xl">
                    Lưu dòng xe
                </button>
                <a href="{{ route('admin.categories.index') }}" class="flex-1 py-4 bg-zinc-900 text-zinc-500 font-black uppercase text-sm tracking-widest text-center hover:text-white transition-colors">
                    Hủy bỏ
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
