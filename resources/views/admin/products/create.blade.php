<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.products.index')"
        eyebrow="Thêm sản phẩm"
        title="Thêm mới"
        accent="Sản phẩm"
        description="Tạo mẫu xe hoặc phụ kiện mới với định dạng giá VNĐ trực quan, thông số chuẩn BMW và thư viện ảnh kéo thả."
    />

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
