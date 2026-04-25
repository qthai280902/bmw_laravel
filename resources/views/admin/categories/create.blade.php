<x-admin-layout>
    <div class="mb-12">
        <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">New <span class="font-black text-[#1C69D4]">Line</span></h1>
        <p class="text-zinc-500 font-medium font-outfit">Định nghĩa phân khúc và dòng xe mới cho hệ thống BMW Showroom.</p>
    </div>

    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div class="max-w-3xl space-y-8">
            <x-admin.card class="!p-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-10 pb-4 border-b border-zinc-800">Cấu hình dòng xe</h3>
                
                <div class="space-y-8">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Tên định danh dòng xe</label>
                        <input type="text" name="name" value="{{ old('name') }}" required 
                               class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all placeholder-zinc-800"
                               placeholder="Ví dụ: BMW M Performance">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Mô tả đặc điểm phân khúc</label>
                        <textarea name="description" rows="5" 
                                  class="w-full bg-zinc-950 border-zinc-800 text-zinc-300 text-sm px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all placeholder-zinc-800"
                                  placeholder="Mô tả tóm tắt về đặc điểm kỹ thuật hoặc phong cách của dòng xe này...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </x-admin.card>

            <div class="grid grid-cols-2 gap-4">
                <button type="submit" class="group relative py-5 bg-white text-black font-black uppercase text-sm tracking-widest shadow-2xl overflow-hidden text-center">
                    <span class="relative z-10">Khởi tạo dòng xe</span>
                    <div class="absolute inset-0 bg-zinc-200 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                </button>
                <a href="{{ route('admin.categories.index') }}" class="py-5 bg-zinc-900 border border-zinc-800 text-zinc-500 font-black uppercase text-sm tracking-widest text-center hover:text-white hover:bg-zinc-800 transition-all">
                    Hủy bỏ
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
