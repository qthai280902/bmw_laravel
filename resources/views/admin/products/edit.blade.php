<x-admin-layout>
    <x-admin.page-header
        eyebrow="Cập nhật sản phẩm"
        title="Chỉnh sửa"
        accent="Sản phẩm"
        :description="$product->name"
    >
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white">
                    Xem trang public
                </a>
                <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                    Quay lại
                </a>
            </div>
        </x-slot>
    </x-admin.page-header>

    <div class="max-w-5xl">
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-500/30 bg-rose-500/10 p-5 text-rose-300">
                <ul class="list-disc pl-5 text-xs font-bold uppercase tracking-wider space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.products._form', [
                'product' => $product,
                'categories' => $categories,
                'types' => $types ?? \App\Enums\VehicleType::cases(),
                'submitLabel' => 'Lưu thay đổi',
            ])
        </form>
    </div>
</x-admin-layout>
