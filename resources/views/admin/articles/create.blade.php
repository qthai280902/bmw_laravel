<x-admin-layout>
    <x-admin.page-header
        eyebrow="Content studio"
        title="Viết"
        accent="Bài mới"
        description="Tạo nội dung showroom để xuất bản ở khu vực Tìm hiểu thêm ngoài public site."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.articles._form', ['submitLabel' => 'Tạo bài viết'])
    </form>
</x-admin-layout>
