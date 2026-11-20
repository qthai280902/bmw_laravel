<x-admin-layout>
    <x-admin.page-header
        eyebrow="Showroom taxonomy"
        title="Cập nhật"
        accent="Dòng xe"
        :description="$category->name"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="max-w-3xl space-y-6">
            <x-admin.card class="!p-6">
                <div class="mb-6 border-b border-white/[0.08] pb-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.26em] text-zinc-400">Thông tin dòng xe</p>
                </div>

                <div class="space-y-6">
                    <x-admin.form-field name="name" label="Tên dòng xe">
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $category->name) }}"
                            required
                            class="w-full rounded-xl border border-white/10 bg-[#070A10] px-5 py-3.5 text-base font-bold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="description" label="Mô tả phân khúc">
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            class="w-full rounded-xl border border-white/10 bg-[#070A10] px-5 py-3.5 text-sm leading-6 text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >{{ old('description', $category->description) }}</textarea>
                    </x-admin.form-field>
                </div>
            </x-admin.card>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button type="submit" class="rounded-xl border border-[#1C69D4] bg-[#1C69D4] px-6 py-3.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer">
                    Lưu cập nhật
                </button>
                <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-white/10 bg-white/[0.03] px-6 py-3.5 text-center text-[10px] font-semibold uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                    Hủy
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
