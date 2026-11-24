<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.email-templates.index')"
        back-label="Quay lại kho mẫu"
        eyebrow="Email Template Studio"
        title="Tạo mẫu"
        accent="Email mới"
        description="Thiết kế mẫu Email chuẩn BMW Concierge với biến tự động và khung xem trước trực tiếp."
    />

    <form method="POST" action="{{ route('admin.email-templates.store') }}">
        @csrf
        @include('admin.email-templates._form', ['submitLabel' => 'Lưu & Phát hành mẫu Email'])
    </form>
</x-admin-layout>
