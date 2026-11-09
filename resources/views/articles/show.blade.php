<x-app-layout>
    <article class="bg-zinc-950 text-white">
        <section class="border-b border-zinc-900 py-16 sm:py-20">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <a href="{{ route('articles.index') }}" class="mb-8 inline-flex border border-zinc-800 px-5 py-3 text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                    Quay lại danh sách
                </a>

                <div class="mb-5">
                    <span class="inline-block border border-blue-500/20 bg-blue-950/40 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-[#70A7FF]">
                        {{ $article->categoryLabel() }}
                    </span>
                </div>

                <h1 class="mb-6 text-3xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $article->title }}
                </h1>

                <div class="mt-4 flex flex-wrap items-center gap-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">
                    <span>{{ $article->published_at?->format('d/m/Y H:i') }}</span>
                    @if($article->user)
                        <span>By {{ $article->user->name }}</span>
                    @endif
                </div>

                @if($article->excerpt)
                    <p class="my-8 max-w-3xl border-l-2 border-[#1C69D4] bg-zinc-900/40 py-3 pl-5 text-lg font-medium leading-relaxed text-zinc-300 sm:text-xl">
                        {{ $article->excerpt }}
                    </p>
                @endif
            </div>
        </section>

        <section class="bg-black">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <img src="{{ $article->editorialImageUrl() }}" alt="{{ $article->title }}" class="max-h-[620px] w-full object-cover">
            </div>
        </section>

        <section class="py-16 sm:py-20">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-[minmax(0,760px)_1fr] lg:px-8">
                <div class="article-content text-base font-normal leading-relaxed text-zinc-400">
                    @foreach(preg_split('/\R{2,}/', trim($article->body)) as $block)
                        @if(filled($block))
                            @if(Str::startsWith($block, '### '))
                                <h3 class="mt-8 mb-3 text-xl font-bold tracking-tight text-zinc-100">{{ Str::after($block, '### ') }}</h3>
                            @elseif(Str::startsWith($block, '## '))
                                <h2 class="mt-12 mb-4 border-b border-zinc-800 pb-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ Str::after($block, '## ') }}</h2>
                            @else
                                <p class="mb-6 text-base font-normal leading-relaxed text-zinc-400">{{ $block }}</p>
                            @endif
                        @endif
                    @endforeach
                </div>

                <aside class="lg:border-l lg:border-zinc-900 lg:pl-10">
                    <div class="sticky top-28">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">Chuyên mục</p>
                        <p class="mt-3 text-sm font-bold tracking-normal text-white">{{ $article->categoryLabel() }}</p>

                        @if($relatedArticles->isNotEmpty())
                            <div class="mt-10 border-t border-zinc-900 pt-8">
                                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">Bài liên quan</p>
                                <div class="space-y-5">
                                    @foreach($relatedArticles as $relatedArticle)
                                        <a href="{{ route('articles.show', $relatedArticle) }}" class="block border border-zinc-900 bg-black p-5 transition-colors hover:border-[#1C69D4]">
                                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#1C69D4]">{{ $relatedArticle->published_at?->format('d/m/Y') }}</p>
                                            <h2 class="mt-3 text-sm font-bold leading-relaxed tracking-normal text-white">{{ $relatedArticle->title }}</h2>
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
