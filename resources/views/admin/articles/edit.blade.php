<x-admin-layout>
    <x-admin.page-header
        eyebrow="Content studio"
        title="Cập nhật"
        accent="Bài viết"
        :description="$article->title"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
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
