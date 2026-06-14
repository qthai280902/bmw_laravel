<x-admin-layout>
    <x-admin.page-header
        eyebrow="Content studio"
        title="Viết"
        accent="Bài mới"
        description="Tạo nội dung showroom để xuất bản ở khu vực Tìm hiểu thêm ngoài public site."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center justify-center border border-zinc-800 px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.articles._form', ['submitLabel' => 'Tạo bài viết'])
    </form>
</x-admin-layout>
