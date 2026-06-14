<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">BMW stories</p>
                <h1 class="mt-3 text-5xl font-black uppercase leading-none tracking-normal text-white md:text-7xl">
                    Tìm hiểu thêm
                </h1>
                <p class="mt-5 max-w-2xl text-sm font-medium leading-6 text-zinc-500">
                    Cập nhật ưu đãi, sự kiện showroom, chương trình bán hàng và các trải nghiệm BMW đã được đội ngũ quản trị xuất bản.
                </p>
            </div>
        </div>
    </x-slot>

    <section class="bg-zinc-950 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex gap-3 overflow-x-auto border-b border-zinc-900 pb-5">
                <a href="{{ route('articles.index') }}" class="shrink-0 border px-4 py-2 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request('category') ? 'border-zinc-800 text-zinc-500 hover:text-white' : 'border-[#1C69D4] bg-[#1C69D4]/10 text-white' }}">
                    Tất cả
                </a>
                @foreach($categories as $value => $label)
                    <a href="{{ route('articles.index', ['category' => $value]) }}" class="shrink-0 border px-4 py-2 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request('category') === $value ? 'border-[#1C69D4] bg-[#1C69D4]/10 text-white' : 'border-zinc-800 text-zinc-500 hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if($articles->isEmpty())
                <div class="border border-zinc-800 bg-zinc-900/30 px-6 py-20 text-center">
                    <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Content studio</p>
                    <h2 class="mt-4 text-3xl font-black uppercase tracking-normal text-white">Chưa có bài viết được xuất bản</h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm font-medium leading-6 text-zinc-500">
                        Khu vực này sẽ hiển thị các bài viết ưu đãi, sự kiện và tin tức showroom sau khi admin xuất bản.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($articles as $article)
                        <article class="group flex h-full flex-col overflow-hidden border border-zinc-900 bg-black transition-colors hover:border-[#1C69D4]">
                            <a href="{{ route('articles.show', $article) }}" class="block">
                                <div class="aspect-[16/10] bg-zinc-900">
                                    @if($article->coverImageUrl())
                                        <img src="{{ $article->coverImageUrl() }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center border-b border-zinc-900 text-[10px] font-black uppercase tracking-[0.35em] text-zinc-700">
                                            BMW showroom
                                        </div>
                                    @endif
                                </div>
                            </a>

                            <div class="flex flex-1 flex-col p-7">
                                <div class="flex-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">{{ $article->categoryLabel() }}</p>
                                    <a href="{{ route('articles.show', $article) }}" class="mt-4 block">
                                        <h2 class="text-2xl font-black uppercase leading-tight tracking-normal text-white transition-colors group-hover:text-[#70A7FF]">
                                            {{ $article->title }}
                                        </h2>
                                    </a>
                                    <p class="mt-4 text-sm font-medium leading-6 text-zinc-500">
                                        {{ $article->excerpt ?? Str::limit(strip_tags($article->body), 150) }}
                                    </p>
                                </div>

                                <div class="mt-8 flex items-center justify-between border-t border-zinc-900 pt-5">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">
                                        {{ $article->published_at?->format('d/m/Y') }}
                                    </p>
                                    <span class="text-[10px] font-black uppercase tracking-[0.22em] text-white">
                                        Đọc tiếp
                                    </span>
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
    </section>
</x-app-layout>
