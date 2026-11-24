<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.categories.index')"
        eyebrow="Showroom taxonomy"
        title="Tạo"
        accent="Dòng xe"
        description="Định nghĩa phân khúc hoặc nhóm sản phẩm mới cho catalog BMW."
    />

    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
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
                            value="{{ old('name') }}"
                            required
                            placeholder="Ví dụ: BMW M Performance"
                            class="w-full rounded-xl border border-white/10 bg-[#070A10] px-5 py-3.5 text-base font-bold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="description" label="Mô tả phân khúc">
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            placeholder="Mô tả ngắn về đặc điểm kỹ thuật hoặc phong cách của dòng sản phẩm..."
                            class="w-full rounded-xl border border-white/10 bg-[#070A10] px-5 py-3.5 text-sm leading-6 text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >{{ old('description') }}</textarea>
                    </x-admin.form-field>
                </div>
            </x-admin.card>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button type="submit" class="rounded-xl border border-[#1C69D4] bg-[#1C69D4] px-6 py-3.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer">
                    Tạo dòng xe
                </button>
                <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-white/10 bg-white/[0.03] px-6 py-3.5 text-center text-[10px] font-semibold uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                    Hủy
                </a>
            </div>
        </div>
    </form>
</x-admin-layout>
