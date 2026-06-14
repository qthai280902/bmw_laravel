<x-admin-layout>
    <x-admin.page-header
        eyebrow="Showroom taxonomy"
        title="Cập nhật"
        accent="Dòng xe"
        :description="$category->name"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center justify-center border border-zinc-800 px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="max-w-3xl space-y-6">
            <x-admin.card class="!p-6">
                <div class="mb-6 border-b border-zinc-900 pb-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.26em] text-zinc-600">Thông tin dòng xe</p>
                </div>

                <div class="space-y-6">
                    <x-admin.form-field name="name" label="Tên dòng xe">
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $category->name) }}"
                            required
                            class="w-full border-zinc-800 bg-black px-5 py-4 text-base font-black text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="description" label="Mô tả phân khúc">
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            class="w-full border-zinc-800 bg-black px-5 py-4 text-sm leading-6 text-zinc-200 placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >{{ old('description', $category->description) }}</textarea>
                    </x-admin.form-field>
                </div>
            </x-admin.card>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button type="submit" class="border border-white bg-white px-6 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                    Lưu cập nhật
                </button>
                <a href="{{ route('admin.categories.index') }}" class="border border-zinc-800 px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                    Hủy
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
