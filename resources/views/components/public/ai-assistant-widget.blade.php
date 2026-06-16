@php
    $assistantPrompts = [
        'Tư vấn xe sedan',
        'Tư vấn SUV/SAV',
        'Tư vấn BMW Motorrad',
        'Tìm BMW S1000RR',
        'So sánh BMW 330i và 530i',
        'Xem ưu đãi mới nhất',
    ];
@endphp

<div
    id="ai-showroom-assistant"
    data-ai-assistant-widget
    x-data="aiAssistantWidget(@js([
        'endpoint' => route('ai.showroom-assistant.ask'),
        'csrf' => csrf_token(),
        'fallback' => config('showroom_ai.fallback_message'),
        'ready' => 'Sẵn sàng tư vấn',
        'prompts' => $assistantPrompts,
    ]))"
    x-init="init()"
    x-cloak
    class="fixed z-[70] text-white"
    :class="sideTabVisible ? 'right-0 bottom-28 sm:bottom-auto sm:top-1/2 sm:-translate-y-1/2' : 'right-3 bottom-4 sm:right-5 sm:bottom-5'"
>
    <div class="sr-only">
        <p>BMW AI Assistant sẵn sàng tư vấn</p>
        @foreach ($assistantPrompts as $assistantPrompt)
            <p>{{ $assistantPrompt }}</p>
        @endforeach
    </div>

    <section
        x-show="panelOpen"
        x-transition.opacity.duration.150ms
        data-ai-panel
        class="ai-chat-panel flex h-[min(39rem,calc(100vh-2rem))] w-[calc(100vw-2rem)] max-w-[25rem] flex-col overflow-hidden border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/70 backdrop-blur-2xl sm:h-[min(39rem,calc(100vh-7rem))]"
        aria-label="BMW AI Assistant"
    >
        <header class="flex items-center gap-3 border-b border-white/10 bg-black/35 px-4 py-3">
            <x-public.ai-assistant-avatar size="h-10 w-10" />

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <p class="text-[11px] font-bold text-[#70A7FF]" x-text="config.ready">Sẵn sàng tư vấn</p>
                </div>
                <h2 class="mt-0.5 truncate text-sm font-extrabold tracking-normal text-white">
                    BMW AI Assistant
                </h2>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    data-ai-clear-conversation
                    class="flex h-9 w-9 items-center justify-center border border-zinc-800 text-xs font-black text-zinc-400 transition-colors hover:border-white hover:text-white"
                    @click="clearConversation()"
                    aria-label="Xóa cuộc trò chuyện"
                    title="Xóa cuộc trò chuyện"
                >
                    ↺
                </button>
                <button
                    type="button"
                    data-ai-minimize
                    class="flex h-9 w-9 items-center justify-center border border-zinc-800 text-sm font-black text-zinc-400 transition-colors hover:border-white hover:text-white"
                    @click="minimize()"
                    aria-label="Thu nhỏ trợ lý"
                    title="Thu nhỏ"
                >
                    –
                </button>
                <button
                    type="button"
                    data-ai-hide-side
                    class="flex h-9 w-9 items-center justify-center border border-[#1C69D4]/50 text-sm font-black text-[#70A7FF] transition-colors hover:border-[#70A7FF] hover:text-white"
                    @click="hideToSide()"
                    aria-label="Ẩn trợ lý vào cạnh trình duyệt"
                    title="Ẩn vào cạnh"
                >
                    ›
                </button>
            </div>
        </header>

        <div
            class="scrollbar-none min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-4 py-4"
            x-ref="messages"
            data-ai-message-list
        >
            <div
                x-show="!hasMessages"
                data-ai-intro-card
                class="border border-white/10 bg-black/35 p-4 shadow-xl shadow-black/20"
            >
                <p class="text-sm font-semibold leading-6 text-zinc-200">
                    Xin chào, tôi là trợ lý BMW. Tôi có thể tư vấn ô tô, BMW Motorrad, phụ kiện, ưu đãi và hướng dẫn đặt lịch lái thử.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-2">
                    <template x-for="prompt in config.prompts" :key="prompt">
                        <button
                            type="button"
                            class="border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-left text-xs font-bold leading-5 text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-white"
                            @click="sendSuggestion(prompt)"
                            x-text="prompt"
                        ></button>
                    </template>
                </div>
            </div>

            <template x-for="message in messages" :key="message.id">
                <article
                    data-ai-message
                    :data-ai-role="message.role"
                    class="flex gap-2"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div x-show="message.role === 'assistant'" class="mt-1 shrink-0">
                        <x-public.ai-assistant-avatar size="h-7 w-7" />
                    </div>

                    <div class="max-w-[82%]">
                        <p
                            x-show="message.role === 'assistant'"
                            class="mb-1 text-[11px] font-bold text-[#70A7FF]"
                        >
                            BMW AI
                        </p>

                        <div
                            class="ai-message-bubble px-4 py-3 text-sm leading-6 shadow-lg [overflow-wrap:anywhere]"
                            :class="message.role === 'user' ? 'bg-[#1C69D4] text-white shadow-[#1C69D4]/15' : 'bg-zinc-900/90 text-zinc-200 shadow-black/20'"
                        >
                            <template x-if="message.role === 'user'">
                                <p class="whitespace-pre-wrap break-words" x-text="message.text"></p>
                            </template>

                            <template x-if="message.role === 'assistant'">
                                <div class="space-y-3">
                                    <template x-for="(block, blockIndex) in message.blocks" :key="blockIndex">
                                        <div>
                                            <p x-show="block.type === 'paragraph'" class="leading-6">
                                                <template x-for="(part, partIndex) in block.parts" :key="partIndex">
                                                    <span
                                                        :class="part.type === 'strong' ? 'font-black text-white' : ''"
                                                        x-text="part.text"
                                                    ></span>
                                                </template>
                                            </p>

                                            <ul x-show="block.type === 'list'" class="list-disc space-y-1.5 pl-5 marker:text-[#70A7FF]">
                                                <template x-for="(item, itemIndex) in block.items" :key="itemIndex">
                                                    <li class="pl-1 leading-6">
                                                        <template x-for="(part, partIndex) in item.parts" :key="partIndex">
                                                            <span
                                                                :class="part.type === 'strong' ? 'font-black text-white' : ''"
                                                                x-text="part.text"
                                                            ></span>
                                                        </template>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div
                            x-show="message.role === 'assistant' && message.actions.length"
                            class="mt-2 flex flex-wrap gap-2"
                        >
                            <template x-for="action in message.actions" :key="action.label + action.url">
                                <a
                                    data-ai-action-chip
                                    class="inline-flex items-center justify-center border border-[#1C69D4]/45 bg-[#1C69D4]/10 px-3 py-2 text-xs font-bold text-[#70A7FF] transition-colors hover:border-[#70A7FF] hover:bg-[#1C69D4] hover:text-white"
                                    :href="action.url"
                                    x-text="action.label"
                                ></a>
                            </template>
                        </div>
                    </div>
                </article>
            </template>

            <div
                x-show="loading"
                data-ai-loading
                class="flex items-center gap-2 text-sm font-medium text-zinc-400"
            >
                <span class="ai-assistant-typing-dot h-1.5 w-1.5 rounded-full bg-[#70A7FF]"></span>
                <span class="ai-assistant-typing-dot h-1.5 w-1.5 rounded-full bg-[#70A7FF] [animation-delay:120ms]"></span>
                <span class="ai-assistant-typing-dot h-1.5 w-1.5 rounded-full bg-[#70A7FF] [animation-delay:240ms]"></span>
                <span>BMW AI đang trả lời...</span>
            </div>
        </div>

        <form class="border-t border-white/10 bg-black/35 p-3" @submit.prevent="send()">
            <label class="sr-only" for="ai-assistant-message">Nhập câu hỏi về BMW</label>
            <div class="flex items-end gap-2">
                <textarea
                    id="ai-assistant-message"
                    data-ai-input
                    x-model="input"
                    maxlength="600"
                    rows="1"
                    class="scrollbar-none max-h-28 min-h-11 min-w-0 flex-1 resize-none border border-zinc-800 bg-zinc-950/85 px-3 py-3 text-sm font-medium leading-5 text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:outline-none focus:ring-0"
                    placeholder="Nhập câu hỏi về BMW..."
                    @keydown.enter.exact.prevent="send()"
                    @keydown.shift.enter.stop
                ></textarea>
                <button
                    type="submit"
                    data-ai-send
                    class="flex h-11 w-11 shrink-0 items-center justify-center bg-[#1C69D4] text-sm font-black text-white transition-colors hover:bg-[#1554AA] disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="loading || !input.trim()"
                    aria-label="Gửi câu hỏi"
                >
                    →
                </button>
            </div>
        </form>
    </section>

    <button
        type="button"
        x-show="sideTabVisible"
        x-transition.opacity.duration.150ms
        data-ai-side-tab
        class="ai-assistant-side-tab flex items-center gap-2 border border-r-0 border-[#1C69D4]/45 bg-black/95 px-3 py-4 text-white shadow-2xl shadow-black/60 backdrop-blur-xl transition-colors hover:border-[#70A7FF]"
        @click="openPanel()"
        aria-label="Mở lại BMW AI Assistant"
    >
        <span class="text-lg font-black text-[#70A7FF]">‹</span>
        <span class="text-[11px] font-black tracking-[0.18em] [writing-mode:vertical-rl]">AI</span>
    </button>

    <div x-show="!panelOpen && !sideTabVisible" x-transition.opacity.duration.150ms class="flex items-end justify-end gap-3">
        <div
            class="hidden max-w-[12rem] border border-white/10 bg-black/90 px-4 py-3 text-sm font-semibold leading-5 text-zinc-200 shadow-2xl shadow-black/50 backdrop-blur-xl sm:block"
            data-ai-greeting-bubble
        >
            Bạn cần hỗ trợ gì?
        </div>

        <button
            type="button"
            data-ai-launcher
            class="ai-assistant-launcher flex items-center gap-3 border border-white/10 bg-black/95 p-3 shadow-2xl shadow-black/60 backdrop-blur-xl transition-colors hover:border-[#1C69D4]"
            @click="openPanel()"
            aria-label="Mở BMW AI Assistant"
        >
            <x-public.ai-assistant-avatar size="h-11 w-11" />
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-extrabold text-white">Trợ lý BMW</span>
                <span class="mt-0.5 block text-xs font-semibold text-[#70A7FF]">Sẵn sàng tư vấn</span>
            </span>
        </button>
    </div>
</div>
