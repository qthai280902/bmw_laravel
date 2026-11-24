<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.articles.index')"
        eyebrow="Content studio"
        title="Viết"
        accent="Bài mới"
        description="Tạo nội dung showroom để xuất bản ở khu vực Tìm hiểu thêm ngoài public site."
    />

    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.articles._form', ['submitLabel' => 'Tạo bài viết'])
    </form>
</x-admin-layout>
