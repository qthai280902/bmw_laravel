<x-app-layout>
    <article class="bg-zinc-950 text-white">
        <section class="border-b border-zinc-900 py-16 sm:py-20">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <a href="{{ route('articles.index') }}" class="mb-8 inline-flex border border-zinc-800 px-5 py-3 text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                    Quay lại danh sách
                </a>

                <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $article->categoryLabel() }}</p>
                <h1 class="mt-5 text-4xl font-black uppercase leading-none tracking-normal text-white md:text-6xl">
                    {{ $article->title }}
                </h1>
                <div class="mt-6 flex flex-wrap items-center gap-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">
                    <span>{{ $article->published_at?->format('d/m/Y H:i') }}</span>
                    @if($article->user)
                        <span>By {{ $article->user->name }}</span>
                    @endif
                </div>

                @if($article->excerpt)
                    <p class="mt-8 max-w-3xl text-lg font-medium leading-8 text-zinc-400">{{ $article->excerpt }}</p>
                @endif
            </div>
        </section>

        @if($article->coverImageUrl())
            <section class="bg-black">
                <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <img src="{{ $article->coverImageUrl() }}" alt="{{ $article->title }}" class="max-h-[620px] w-full object-cover">
                </div>
            </section>
        @endif

        <section class="py-16 sm:py-20">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-[minmax(0,760px)_1fr] lg:px-8">
                <div class="space-y-7 text-base font-medium leading-8 text-zinc-300">
                    @foreach(preg_split('/\R{2,}/', trim($article->body)) as $paragraph)
                        @if(filled($paragraph))
                            <p>{{ $paragraph }}</p>
                        @endif
                    @endforeach
                </div>

                <aside class="lg:border-l lg:border-zinc-900 lg:pl-10">
                    <div class="sticky top-28">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">Chuyên mục</p>
                        <p class="mt-3 text-sm font-black uppercase tracking-widest text-white">{{ $article->categoryLabel() }}</p>

                        @if($relatedArticles->isNotEmpty())
                            <div class="mt-10 border-t border-zinc-900 pt-8">
                                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">Bài liên quan</p>
                                <div class="space-y-5">
                                    @foreach($relatedArticles as $relatedArticle)
                                        <a href="{{ route('articles.show', $relatedArticle) }}" class="block border border-zinc-900 bg-black p-5 transition-colors hover:border-[#1C69D4]">
                                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#1C69D4]">{{ $relatedArticle->published_at?->format('d/m/Y') }}</p>
                                            <h2 class="mt-3 text-sm font-black uppercase leading-5 tracking-normal text-white">{{ $relatedArticle->title }}</h2>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </section>
    </article>
</x-app-layout>
