<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Phân trang" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            {{-- Mobile Pagination --}}
            <div class="flex flex-1 items-center justify-between gap-3 sm:hidden">
                @if ($paginator->onFirstPage())
                    <span data-bmw-pagination="mobile-btn" class="inline-flex cursor-default items-center rounded-none border border-white/10 bg-[#121215] px-4 py-2.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">
                        Trước
                    </span>
                @else
                    <button
                        type="button"
                        data-bmw-pagination="mobile-btn"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-none border border-white/10 bg-[#121215] px-4 py-2.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-[#1c69d4] hover:text-white"
                    >
                        Trước
                    </button>
                @endif

                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">
                    Trang <span class="text-white">{{ $paginator->currentPage() }}</span> / {{ $paginator->lastPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        data-bmw-pagination="mobile-btn"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-none border border-white/10 bg-[#121215] px-4 py-2.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-[#1c69d4] hover:text-white"
                    >
                        Sau
                    </button>
                @else
                    <span data-bmw-pagination="mobile-btn" class="inline-flex cursor-default items-center rounded-none border border-white/10 bg-[#121215] px-4 py-2.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">
                        Sau
                    </span>
                @endif
            </div>

            {{-- Desktop Pagination --}}
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">
                        Hiển thị
                        @if ($paginator->firstItem())
                            <span class="text-zinc-200">{{ $paginator->firstItem() }}</span>
                            đến
                            <span class="text-zinc-200">{{ $paginator->lastItem() }}</span>
                        @else
                            <span class="text-zinc-200">{{ $paginator->count() }}</span>
                        @endif
                        trong tổng số
                        <span class="text-zinc-200">{{ $paginator->total() }}</span>
                        kết quả
                    </p>
                </div>

                <div>
                    <span data-bmw-pagination="bar" class="relative z-0 inline-flex rounded-none border border-white/10 bg-[#121215]">
                        {{-- Previous Page Button --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true" aria-label="Trang trước" class="inline-flex cursor-default items-center rounded-none border-r border-white/10 px-3 py-2.5 text-zinc-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                wire:loading.attr="disabled"
                                aria-label="Trang trước"
                                class="inline-flex items-center rounded-none border-r border-white/10 px-3 py-2.5 text-zinc-400 transition-colors hover:bg-zinc-900 hover:text-white focus:outline-none"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span aria-disabled="true" class="inline-flex cursor-default items-center rounded-none border-r border-white/10 px-4 py-2.5 text-xs font-bold text-zinc-600">
                                    {{ $element }}
                                </span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page" class="inline-flex cursor-default items-center rounded-none border-r border-[#1c69d4] bg-[#1c69d4] px-4 py-2.5 text-xs font-black text-white">
                                                {{ $page }}
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                                wire:loading.attr="disabled"
                                                aria-label="Trang {{ $page }}"
                                                class="inline-flex items-center rounded-none border-r border-white/10 px-4 py-2.5 text-xs font-bold text-zinc-400 transition-colors hover:bg-zinc-900 hover:text-white focus:outline-none"
                                            >
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Button --}}
                        @if ($paginator->hasMorePages())
                            <button
                                type="button"
                                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                                wire:loading.attr="disabled"
                                aria-label="Trang sau"
                                class="inline-flex items-center rounded-none px-3 py-2.5 text-zinc-400 transition-colors hover:bg-zinc-900 hover:text-white focus:outline-none"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @else
                            <span aria-disabled="true" aria-label="Trang sau" class="inline-flex cursor-default items-center rounded-none px-3 py-2.5 text-zinc-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
