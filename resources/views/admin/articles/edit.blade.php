<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.articles.index')"
        eyebrow="Content studio"
        title="Cập nhật"
        accent="Bài viết"
        :description="$article->title"
    />

    <form action="{{ route('admin.articles.update', $article) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        @include('admin.articles._form', ['submitLabel' => 'Lưu cập nhật'])
    </form>
</x-admin-layout>
