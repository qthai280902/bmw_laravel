<div wire:poll.15s="refreshDigest" x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
    <button
        type="button"
        @click="open = !open"
        aria-label="Mở trung tâm thông báo Showroom"
        class="relative inline-flex items-center gap-2 rounded-none border border-white/10 bg-[#121215] px-3 py-1.5 text-xs font-bold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-white cursor-pointer"
    >
        <svg class="h-4 w-4 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        <span class="hidden text-[10px] font-black uppercase tracking-[0.2em] md:inline">Thông báo</span>

        @if($unreadCount > 0)
            <span class="rounded-none bg-[#1C69D4] px-1.5 py-0.5 text-[10px] font-black tabular-nums leading-none text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif

        @if($hasUnreadHotLead)
            <span class="h-2 w-2 rounded-none bg-rose-500 animate-pulse shadow-[0_0_8px_rgba(244,63,94,0.85)]" title="Có HOT LEAD mới"></span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-[340px] sm:w-[420px] rounded-none border border-white/10 bg-[#121215] text-white shadow-2xl"
    >
        <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-[#09090b] px-4 py-3.5">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">BMW Operations</p>
                <p class="mt-0.5 text-xs font-black uppercase tracking-wider text-white">Trung tâm thông báo</p>
            </div>

            @if($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="rounded-none border border-white/10 bg-zinc-900 px-2.5 py-1.5 text-[9px] font-black uppercase tracking-[0.18em] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-white cursor-pointer"
                >
                    Đọc tất cả
                </button>
            @endif
        </div>

        <div class="grid grid-cols-3 border-b border-white/10 bg-black/60">
            <button
                type="button"
                wire:click="setFilter('all')"
                class="rounded-none px-3 py-2.5 text-[10px] font-black uppercase tracking-[0.18em] transition-colors cursor-pointer {{ $filter === 'all' ? 'border-b-2 border-[#1C69D4] bg-[#1C69D4]/15 text-white' : 'text-zinc-500 hover:text-zinc-200' }}"
            >
                Tất cả
            </button>
            <button
                type="button"
                wire:click="setFilter('unread')"
                class="rounded-none border-l border-white/10 px-3 py-2.5 text-[10px] font-black uppercase tracking-[0.18em] transition-colors cursor-pointer {{ $filter === 'unread' ? 'border-b-2 border-[#1C69D4] bg-[#1C69D4]/15 text-white' : 'text-zinc-500 hover:text-zinc-200' }}"
            >
                Chưa đọc ({{ $unreadCount }})
            </button>
            <button
                type="button"
                wire:click="setFilter('hot')"
                class="rounded-none border-l border-white/10 px-3 py-2.5 text-[10px] font-black uppercase tracking-[0.18em] transition-colors cursor-pointer {{ $filter === 'hot' ? 'border-b-2 border-rose-500 bg-rose-500/15 text-rose-300' : 'text-zinc-500 hover:text-rose-300' }}"
            >
                HOT LEAD
            </button>
        </div>

        <div class="max-h-96 divide-y divide-white/10 overflow-y-auto overscroll-contain">
            @forelse($notifications as $item)
                <div
                    wire:key="admin-notif-{{ $item['id'] }}"
                    class="p-4 transition-colors hover:bg-zinc-900/70 {{ $item['is_unread'] ? 'bg-[#09090b] border-l-2 border-l-[#1C69D4]' : 'bg-[#121215]/70 opacity-80' }}"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if($item['kind'] === 'mail_delivery_failure')
                                <span class="rounded-none border border-rose-500/40 bg-rose-500/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-rose-300">
                                    CẢNH BÁO MAIL
                                </span>
                            @elseif($item['kind'] === 'accessory_order')
                                <span class="rounded-none border border-amber-500/40 bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-amber-300">
                                    ĐƠN PHỤ KIỆN
                                </span>
                            @elseif($item['lead_priority'] === 'hot')
                                <span class="rounded-none border border-rose-500/40 bg-rose-500/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-rose-400">
                                    HOT LEAD
                                </span>
                            @elseif($item['lead_priority'] === 'warm')
                                <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-[#70A7FF]">
                                    TIỀM NĂNG
                                </span>
                            @endif

                            @if($item['has_ai_chat'])
                                <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-[#70A7FF]">
                                    ĐÃ CHAT AI
                                </span>
                            @endif
                        </div>

                        <span class="shrink-0 text-[10px] font-medium text-zinc-500">
                            {{ $item['created_at_human'] }}
                        </span>
                    </div>

                    <a
                        href="{{ $item['action_url'] }}"
                        wire:click="markAsRead('{{ $item['id'] }}')"
                        class="mt-2 block group"
                    >
                        <p class="text-xs font-black uppercase tracking-wider text-white group-hover:text-[#70A7FF]">
                            {{ $item['title'] }}
                            @if(filled($item['phone']))
                                <span class="ml-1 font-mono text-[11px] font-normal text-zinc-400">· {{ $item['phone'] }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-[11px] font-semibold text-zinc-300">{{ $item['product_name'] }}</p>
                        <p class="mt-0.5 text-[11px] text-zinc-500">{{ $item['subtitle'] }}</p>
                    </a>

                    @if(filled($item['ai_lead_note']))
                        <p class="mt-1.5 truncate text-[10px] text-zinc-400">AI: {{ $item['ai_lead_note'] }}</p>
                    @endif

                    @if($item['is_unread'])
                        <div class="mt-2.5 flex items-center justify-end">
                            <button
                                type="button"
                                wire:click="markAsRead('{{ $item['id'] }}')"
                                class="rounded-none border border-white/10 bg-black px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.16em] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-white cursor-pointer"
                            >
                                Đánh dấu đã đọc
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-8 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-400">Không có thông báo phù hợp</p>
                    <p class="mt-1 text-[11px] text-zinc-600">Các yêu cầu lái thử, HOT LEAD và đơn phụ kiện mới sẽ xuất hiện tại đây.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
