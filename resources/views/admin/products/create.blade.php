<x-admin-layout>
    <x-admin.page-header
        eyebrow="Thêm sản phẩm"
        title="Thêm mới"
        accent="Sản phẩm"
        description="Tạo mẫu xe hoặc phụ kiện mới với định dạng giá VNĐ trực quan, thông số chuẩn BMW và thư viện ảnh kéo thả."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Quay lại
            </a>
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

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('admin.products._form', [
                'product' => null,
                'categories' => $categories,
                'types' => $types ?? \App\Enums\VehicleType::cases(),
                'submitLabel' => 'Tạo sản phẩm',
            ])
        </form>
    </div>
</x-admin-layout>
