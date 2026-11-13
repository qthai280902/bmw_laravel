<x-app-layout>
    <article class="bg-zinc-950 text-white min-h-screen">
        <!-- Hero & Editorial Header -->
        <section class="border-b border-zinc-900/80 py-12 sm:py-16 bg-ambient-showroom relative">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <a href="{{ route('articles.index') }}" class="mb-8 inline-flex items-center gap-2 border border-zinc-800 bg-[#121215]/80 px-4 py-2.5 text-xs font-semibold text-zinc-400 transition-colors hover:border-zinc-700 hover:text-white">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Quay lại danh sách
                </a>

                <div class="mb-4">
                    <span class="inline-block border border-blue-500/20 bg-blue-950/40 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-[#70A7FF]">
                        {{ $article->categoryLabel() }}
                    </span>
                </div>

                <h1 class="mb-6 text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                    {{ $article->title }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-zinc-400">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ $article->published_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($article->user)
                        <span class="text-zinc-600">•</span>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Tác giả: {{ $article->user->name }}</span>
                        </div>
                    @endif
                </div>

                @if($article->excerpt)
                    <div class="mt-8 border-l-2 border-[#1C69D4] bg-[#121215] p-5 sm:p-6 shadow-xl">
                        <p class="text-base sm:text-lg font-medium leading-relaxed text-zinc-200">
                            {{ $article->excerpt }}
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <!-- Cover Image -->
        <section class="bg-black/60 border-b border-zinc-900">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden border border-zinc-800 shadow-2xl">
                    <img src="{{ $article->editorialImageUrl() }}" alt="{{ $article->title }}" class="max-h-[580px] w-full object-cover">
                </div>
            </div>
        </section>

        <!-- Main Content & Sidebar -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-[minmax(0,760px)_1fr] lg:px-8">
                <!-- Left: Markdown Body Content -->
                <div class="article-content text-base font-normal leading-relaxed text-zinc-300">
                    {!! \Illuminate\Support\Str::markdown($article->body) !!}
                </div>

                <!-- Right: Sticky Sidebar -->
                <aside class="space-y-8 lg:border-l lg:border-zinc-900 lg:pl-8">
                    <div class="sticky top-28 space-y-6"
                         x-data="{
                             headings: [],
                             init() {
                                 this.$nextTick(() => {
                                     const h2s = document.querySelectorAll('.article-content h2');
                                     h2s.forEach((h, idx) => {
                                         if (!h.id) {
                                             h.id = 'section-' + idx;
                                         }
                                         this.headings.push({ id: h.id, text: h.innerText.replace(/^—\s*/, '').trim() });
                                     });
                                 });
                             }
                         }">
                        <!-- Dynamic TOC if headings exist -->
                        <template x-if="headings.length > 0">
                            <div class="border border-zinc-800 bg-card-luxury p-5 shadow-lg">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-3 bg-[#1C69D4]"></span>
                                    Mục lục bài viết
                                </h4>
                                <nav class="space-y-2">
                                    <template x-for="item in headings" :key="item.id">
                                        <a :href="'#' + item.id" 
                                           class="block text-xs text-zinc-400 hover:text-white hover:translate-x-1 transition-all line-clamp-1 py-1 border-b border-zinc-900/60 last:border-0" 
                                           x-text="item.text"></a>
                                    </template>
                                </nav>
                            </div>
                        </template>

                        <!-- Consultation & Test Drive Card -->
                        <div class="border border-zinc-800 bg-card-luxury p-5 shadow-lg">
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-[#70A7FF] mb-2">Trải nghiệm thượng lưu</span>
                            <h3 class="text-base font-bold text-white mb-2">Đăng ký lái thử cùng chuyên gia</h3>
                            <p class="text-xs text-zinc-400 leading-relaxed mb-4">
                                Trực tiếp cảm nhận cảm giác lái thuần chất và công nghệ vận hành đỉnh cao từ các mẫu xe BMW mới nhất.
                            </p>
                            <a href="{{ route('appointments.create') }}" class="block w-full py-2.5 px-4 text-center bg-gradient-to-r from-[#1C69D4] to-[#154fa0] hover:from-[#2575e6] hover:to-[#1c69d4] text-white text-xs font-bold uppercase tracking-wider shadow-md hover:shadow-blue-900/40 transition-all">
                                Đăng ký lái thử
                            </a>
                        </div>

                        <!-- Related Articles -->
                        @if($relatedArticles->isNotEmpty())
                            <div class="border border-zinc-800 bg-card-luxury p-5 shadow-lg">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <span class="w-1.5 h-3 bg-[#1C69D4]"></span>
                                    Bài viết liên quan
                                </h4>
                                <div class="space-y-4">
                                    @foreach($relatedArticles as $relatedArticle)
                                        <a href="{{ route('articles.show', $relatedArticle) }}" class="block group border-b border-zinc-900/80 pb-3 last:border-0 last:pb-0">
                                            <span class="text-[10px] font-semibold text-[#70A7FF]">{{ $relatedArticle->published_at?->format('d/m/Y') }}</span>
                                            <h5 class="mt-1 text-xs font-semibold leading-snug text-zinc-300 group-hover:text-white transition-colors line-clamp-2">
                                                {{ $relatedArticle->title }}
                                            </h5>
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
