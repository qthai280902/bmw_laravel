<x-admin-layout>
    <x-admin.page-header
        eyebrow="Content studio"
        title="Bài viết"
        accent="Showroom"
        description="Quản trị nội dung ưu đãi, sự kiện, chương trình bán hàng và trải nghiệm BMW hiển thị ngoài public site."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng bài viết</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $articles->total() }}</p>
        </x-slot>

        <x-slot name="actions">
            <a href="{{ route('admin.articles.create') }}" class="inline-flex items-center justify-center border border-white bg-white px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                Viết bài mới
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.card class="mb-6 !p-5">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-[1.4fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm">
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Tiêu đề hoặc tóm tắt..."
                    class="w-full border-zinc-800 bg-black px-4 py-3 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="category" label="Chuyên mục">
                <select id="category" name="category" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả chuyên mục</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <select id="status" name="status" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="border border-zinc-700 bg-zinc-900 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]">
                    Lọc
                </button>
                <a href="{{ route('admin.articles.index') }}" class="border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                    Xóa
                </a>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Bài viết</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Chuyên mục</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Trạng thái</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Xuất bản</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($articles as $article)
                        <tr class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-14 w-24 shrink-0 border border-zinc-800 bg-black">
                                        @if($article->coverImageUrl())
                                            <img src="{{ $article->coverImageUrl() }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[9px] font-black uppercase tracking-widest text-zinc-700">
                                                BMW
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black uppercase tracking-wider text-white group-hover:text-[#70A7FF]">{{ $article->title }}</p>
                                        <p class="mt-1 truncate text-xs font-medium text-zinc-600">{{ $article->excerpt ?: $article->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-xs font-black uppercase tracking-widest text-[#70A7FF]">{{ $article->categoryLabel() }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] {{ $article->statusColorClass() }}">
                                    {{ $article->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-xs font-bold uppercase tracking-widest text-zinc-500">
                                {{ $article->published_at?->format('d/m/Y H:i') ?? 'Chưa xuất bản' }}
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center justify-end gap-5">
                                    @if($article->isPublished())
                                        <a href="{{ route('articles.show', $article) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:text-white">
                                            Public
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.articles.edit', $article) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:text-white">
                                        Sửa
                                    </a>
                                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="admin-delete-form" data-confirm-message="Xóa bài viết “{{ $article->title }}”?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-700 transition-colors hover:text-rose-400">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Chưa có bài viết"
                                    description="Tạo bài viết đầu tiên để hiển thị nội dung ưu đãi, sự kiện và tin tức showroom ngoài website."
                                    :href="route('admin.articles.create')"
                                    action="Viết bài mới"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($articles->hasPages())
        <div class="mt-8">
            {{ $articles->links() }}
        </div>
    @endif
</x-admin-layout>
