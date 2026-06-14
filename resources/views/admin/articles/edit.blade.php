<x-admin-layout>
    <x-admin.page-header
        eyebrow="Content studio"
        title="Cập nhật"
        accent="Bài viết"
        :description="$article->title"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center justify-center border border-zinc-800 px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.articles.update', $article) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        @include('admin.articles._form', ['submitLabel' => 'Lưu cập nhật'])
    </form>
</x-admin-layout>
