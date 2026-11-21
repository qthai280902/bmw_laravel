<section class="bg-zinc-950 py-16 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10 flex flex-col gap-4 border-b border-zinc-900 pb-6 sm:flex-row sm:items-center sm:justify-between">
            <nav aria-label="Lọc bài viết theo chuyên mục" class="w-full sm:w-auto">
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:gap-3">
                    <a
                        href="{{ route('articles.index') }}"
                        wire:click.prevent="selectCategory('')"
                        @if($activeCategory === '') aria-current="page" @endif
                        class="inline-flex min-h-11 items-center justify-center rounded-none border px-4 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-[0.18em] transition-colors sm:min-h-0 sm:px-5 {{ $activeCategory !== '' ? 'border-zinc-800 text-zinc-500 hover:border-zinc-600 hover:text-white' : 'border-[#1C69D4] bg-[#1C69D4]/10 text-white shadow-[inset_0_-2px_0_#1C69D4]' }}"
                    >
                        Tất cả
                    </a>
                    @foreach($categories as $value => $label)
                        <a
                            href="{{ route('articles.index', ['category' => $value]) }}"
                            wire:click.prevent="selectCategory('{{ $value }}')"
                            wire:key="article-category-{{ $value }}"
                            @if($activeCategory === $value) aria-current="page" @endif
                            class="inline-flex min-h-11 items-center justify-center rounded-none border px-4 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-[0.18em] transition-colors sm:min-h-0 sm:px-5 {{ $activeCategory === $value ? 'border-[#1C69D4] bg-[#1C69D4]/10 text-white shadow-[inset_0_-2px_0_#1C69D4]' : 'border-zinc-800 text-zinc-500 hover:border-zinc-600 hover:text-white' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </nav>

            @if($activeCategory !== '')
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="shrink-0 self-start rounded-none border border-zinc-800 bg-[#121215] px-4 py-3 text-[10px] font-black uppercase tracking-[0.18em] text-zinc-400 transition-colors hover:border-white hover:text-white sm:self-auto"
                >
                    Xem tất cả
                </button>
            @endif
        </div>

        <div wire:loading.flex class="mb-6 items-center gap-2 text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">
            <span class="h-1.5 w-1.5 animate-pulse bg-[#1C69D4]"></span>
            Đang tải nội dung...
        </div>

        <div wire:loading.class="opacity-50 pointer-events-none" class="transition-opacity duration-200">
            @if($articles->isEmpty())
                <div class="rounded-none border border-zinc-800 bg-zinc-900/30 px-6 py-20 text-center">
                    <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Content studio</p>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-white">Chưa có bài viết được xuất bản</h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm font-medium leading-relaxed text-zinc-400">
                        {{ $activeCategory !== '' ? 'Không tìm thấy bài viết khớp chuyên mục hiện tại. Hãy xem tất cả chuyên mục.' : 'Khu vực này sẽ hiển thị các bài viết ưu đãi, sự kiện và tin tức showroom sau khi admin xuất bản.' }}
                    </p>
                    @if($activeCategory !== '')
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="mt-8 inline-flex rounded-none border border-white px-8 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black"
                        >
                            Xem tất cả bài viết
                        </button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($articles as $article)
                        <article wire:key="article-card-{{ $article->id }}" class="group flex h-full flex-col overflow-hidden rounded-none border border-zinc-900 bg-black transition-colors duration-300 hover:border-[#1C69D4]/50">
                            <a href="{{ route('articles.show', $article) }}" class="block">
                                <div class="aspect-[16/10] overflow-hidden bg-zinc-900">
                                    <img src="{{ $article->editorialImageUrl() }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                                </div>
                            </a>

                            <div class="flex flex-1 flex-col p-7">
                                <div class="flex-1">
                                    <span class="inline-block rounded-none border border-blue-500/20 bg-blue-950/40 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-[#70A7FF]">
                                        {{ $article->categoryLabel() }}
                                    </span>
                                    <a href="{{ route('articles.show', $article) }}" class="mt-4 block">
                                        <h2 class="text-xl font-bold leading-snug tracking-tight text-white transition-colors group-hover:text-[#70A7FF]">
                                            {{ $article->title }}
                                        </h2>
                                    </a>
                                    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-zinc-400">
                                        {{ $article->excerpt ?? Str::limit(strip_tags($article->body), 150) }}
                                    </p>
                                </div>

                                <div class="mt-8 flex items-center justify-between border-t border-zinc-900 pt-5">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">
                                        {{ $article->published_at?->format('d/m/Y') }}
                                    </p>
                                    <a href="{{ route('articles.show', $article) }}" class="text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:text-[#70A7FF] group-hover:text-[#70A7FF]">
                                        Đọc tiếp
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-14">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
