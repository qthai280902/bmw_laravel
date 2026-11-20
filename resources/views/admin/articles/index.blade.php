<x-admin-layout>
    <x-admin.page-header
        eyebrow="Xưởng Nội Dung"
        title="Bài viết"
        accent="Showroom"
        description="Quản trị nội dung ưu đãi, sự kiện, chương trình bán hàng và trải nghiệm BMW hiển thị ngoài public site."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">Tổng bài viết</p>
            <p class="mt-1 text-2xl font-bold text-white">{{ $articles->total() }}</p>
        </x-slot>

        <x-slot name="actions">
            <a href="{{ route('admin.articles.create') }}" class="inline-flex items-center justify-center rounded-xl border border-[#1C69D4] bg-[#1C69D4] px-5 py-2.5 text-xs font-semibold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6]">
                Viết bài mới
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.card class="mb-6 !p-5">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[1.4fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm">
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Tiêu đề hoặc tóm tắt..."
                    class="h-10 w-full rounded-lg border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="category" label="Chuyên mục">
                <x-ui.custom-select
                    id="category"
                    name="category"
                    :value="(string) request('category', '')"
                    :options="array_merge(['' => 'Tất cả chuyên mục'], $categories)"
                />
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <x-ui.custom-select
                    id="status"
                    name="status"
                    :value="(string) request('status', '')"
                    :options="array_merge(['' => 'Tất cả trạng thái'], $statuses)"
                />
            </x-admin.form-field>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 whitespace-nowrap rounded-lg bg-[#1C69D4] px-4 text-xs font-semibold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer">
                    Lọc
                </button>
                <a href="{{ route('admin.articles.index') }}" class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-lg border border-white/10 bg-white/[0.03] px-4 text-xs font-medium text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                    Xóa
                </a>
            </div>
        </form>
    </x-admin.card>

    <!-- Table Container Strictly Bound to 1 Frame -->
    <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
        <table class="table-fixed w-full border-collapse text-left">
            <colgroup>
                <col class="w-[42%]">
                <col class="w-[18%]">
                <col class="w-[12%]">
                <col class="w-[14%]">
                <col class="w-[14%]">
            </colgroup>
            <thead>
                <tr class="border-b border-white/[0.08] bg-[#070A10]/70">
                    <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Bài viết</th>
                    <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Chuyên mục</th>
                    <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Trạng thái</th>
                    <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Xuất bản</th>
                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-zinc-400">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.06]">
                @forelse($articles as $article)
                    <tr class="group transition-colors hover:bg-[#131A29]/60">
                        <!-- Cột Bài viết: 42% với min-w-0 và truncate -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($article->coverImageUrl())
                                    <img src="{{ $article->coverImageUrl() }}" alt="{{ $article->title }}" class="w-16 h-10 rounded-lg object-cover shrink-0 border border-white/10">
                                @else
                                    <div class="w-16 h-10 shrink-0 rounded-lg border border-white/10 bg-[#070A10] flex items-center justify-center text-[10px] font-bold text-zinc-500">
                                        BMW
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.articles.edit', $article) }}" 
                                       class="truncate block font-semibold text-white group-hover:text-[#70A7FF] transition-colors" 
                                       title="{{ $article->title }}">
                                        {{ $article->title }}
                                    </a>
                                    <p class="truncate text-xs text-zinc-400 mt-0.5" 
                                       title="{{ $article->excerpt ?: $article->slug }}">
                                        {{ $article->excerpt ?: $article->slug }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <!-- Cột Chuyên mục: 18% với truncate -->
                        <td class="px-6 py-4">
                            <span class="truncate block text-xs font-medium text-[#70A7FF]" title="{{ $article->categoryLabel() }}">
                                {{ $article->categoryLabel() }}
                            </span>
                        </td>
                        <!-- Cột Trạng thái: 12% -->
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ $article->statusColorClass() }}">
                                {{ $article->statusLabel() }}
                            </span>
                        </td>
                        <!-- Cột Xuất bản: 14% -->
                        <td class="whitespace-nowrap px-6 py-4 text-xs tabular-nums text-zinc-400">
                            {{ $article->published_at?->format('d/m/Y H:i') ?? 'Chưa xuất bản' }}
                        </td>
                        <!-- Cột Thao tác: 14% -->
                        <td class="px-6 py-4 text-right">
                            <div class="whitespace-nowrap flex items-center justify-end gap-3 text-xs">
                                @if($article->isPublished())
                                    <a href="{{ route('articles.show', $article) }}" class="font-medium text-zinc-400 transition-colors hover:text-white">
                                        Public
                                    </a>
                                @endif
                                <a href="{{ route('admin.articles.edit', $article) }}" class="font-medium text-zinc-400 transition-colors hover:text-white">
                                    Sửa
                                </a>
                                <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="admin-delete-form inline" data-confirm-message="Xóa bài viết “{{ $article->title }}”?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-zinc-500 transition-colors hover:text-rose-400 cursor-pointer">
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

    @if($articles->hasPages())
        <div class="mt-6">
            {{ $articles->links() }}
        </div>
    @endif
</x-admin-layout>
