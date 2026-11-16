@php
    $assistantPrompts = [
        'Báo giá BMW 330i',
        'Đặt lịch lái thử',
        'Chính sách bảo hành',
        'Tư vấn BMW Motorrad',
        'Tìm BMW S1000RR',
        'So sánh BMW 330i và 530i',
    ];
@endphp

<div
    id="ai-showroom-assistant"
    data-ai-assistant-widget
    x-data="aiAssistantWidget(@js([
        'endpoint' => route('ai.showroom-assistant.ask'),
        'streamEndpoint' => route('ai.showroom-assistant.stream'),
        'resetEndpoint' => route('ai.showroom-assistant.reset'),
        'csrf' => csrf_token(),
        'fallback' => config('showroom_ai.fallback_message'),
        'ready' => 'Sẵn sàng tư vấn',
        'prompts' => $assistantPrompts,
    ]))"
    x-init="init()"
    @keydown.escape.window="if (panelOpen) minimize()"
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
        class="ai-chat-panel flex h-[min(640px,calc(100vh-2rem))] w-[calc(100vw-1.5rem)] max-w-[420px] flex-col overflow-hidden border border-white/10 border-t-2 border-t-[#1C69D4] bg-zinc-950/95 shadow-2xl shadow-black/90 backdrop-blur-2xl sm:h-[min(640px,calc(100vh-5rem))] sm:w-[420px]"
        aria-label="BMW AI Assistant"
    >
        <header class="flex items-center gap-3 border-b border-white/10 bg-black/35 px-4 py-3">
            <x-public.ai-assistant-avatar size="h-10 w-10" />

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="ai-assistant-ready-pulse h-2 w-2 bg-emerald-400"></span>
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
                    :disabled="!hasMessages && !loading"
                    class="flex h-9 items-center gap-1.5 border border-zinc-800 px-2.5 text-[10px] font-black uppercase tracking-wider text-zinc-400 transition-colors hover:border-white hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    @click="clearConversation()"
                    aria-label="Làm mới hội thoại"
                    title="Làm mới hội thoại"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0v2.43l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">Làm mới</span>
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
                <!-- <button
                    type="button"
                    data-ai-hide-side
                    class="flex h-9 w-9 items-center justify-center border border-[#1C69D4]/50 text-sm font-black text-[#70A7FF] transition-colors hover:border-[#70A7FF] hover:text-white"
                    @click="hideToSide()"
                    aria-label="Ẩn trợ lý vào cạnh trình duyệt"
                    title="Ẩn vào cạnh"
                >
                    ›
                </button> -->
            </div>
        </header>

        <div
            x-show="activePageContext && (activePageContext.product_name || activePageContext.product_slug)"
            data-ai-context-badge
            class="flex items-center justify-between gap-2 border-b border-white/10 bg-[#121215] px-4 py-2 text-[11px]"
        >
            <span class="min-w-0 truncate font-semibold text-zinc-300">
                Đang xem: <span class="font-bold text-[#70A7FF]" x-text="activePageContext.product_name || activePageContext.product_slug"></span>
            </span>
            <span class="shrink-0 text-[10px] font-black uppercase tracking-wider text-zinc-500">Ngữ cảnh</span>
        </div>

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
                    class="flex"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div :class="message.role === 'user' ? 'max-w-[85%]' : 'w-full max-w-[94%]'">
                        <div
                            x-show="message.role === 'assistant'"
                            class="mb-1.5 flex items-center gap-1.5"
                        >
                            <span class="h-1.5 w-1.5 bg-[#70A7FF]"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.14em] text-[#70A7FF]">BMW AI</span>
                        </div>

                        <div
                            class="ai-message-bubble px-4 py-3 text-[13.5px] leading-6 shadow-lg [overflow-wrap:anywhere]"
                            :class="message.role === 'user' ? 'bg-[#1C69D4] text-white shadow-[#1C69D4]/15' : 'border border-zinc-800/90 bg-zinc-900/90 text-zinc-200 shadow-black/20'"
                        >
                            <template x-if="message.role === 'user'">
                                <div class="space-y-2">
                                    <template x-if="message.imagePreviewUrl">
                                        <div class="overflow-hidden border border-white/25 bg-black/30">
                                            <img :src="message.imagePreviewUrl" alt="Ảnh đính kèm" class="max-h-36 w-full object-cover">
                                        </div>
                                    </template>
                                    <template x-if="!message.imagePreviewUrl && message.imageName">
                                        <p class="inline-flex items-center gap-1.5 border border-white/25 bg-black/25 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white/90" x-text="'📷 ' + message.imageName"></p>
                                    </template>
                                    <p class="whitespace-pre-wrap break-words" x-text="message.text"></p>
                                </div>
                            </template>

                            <template x-if="message.role === 'assistant'">
                                <div class="space-y-2.5">
                                    <template x-for="(block, blockIndex) in message.blocks" :key="blockIndex">
                                        <div>
                                            <p
                                                x-show="block.type === 'paragraph' && (!block.parts.length || (message.revealedChars ?? message.totalChars ?? 0) > (block.parts[0]?.start ?? 0))"
                                                class="leading-6"
                                            >
                                                <template x-for="(part, partIndex) in block.parts" :key="partIndex">
                                                    <span>
                                                        <template x-if="part.type === 'link' && visiblePartText(part, message.revealedChars).length > 0">
                                                            <a :href="part.url" class="inline-flex items-center gap-1 text-[#70A7FF] underline hover:text-white font-medium transition-colors" x-text="visiblePartText(part, message.revealedChars)"></a>
                                                        </template>
                                                        <template x-if="part.type === 'strong' && visiblePartText(part, message.revealedChars).length > 0">
                                                            <span class="font-bold text-white" x-text="visiblePartText(part, message.revealedChars)"></span>
                                                        </template>
                                                        <template x-if="part.type === 'text' && visiblePartText(part, message.revealedChars).length > 0">
                                                            <span x-text="visiblePartText(part, message.revealedChars)"></span>
                                                        </template>
                                                    </span>
                                                </template>
                                            </p>

                                            <ul x-show="block.type === 'list'" class="list-disc space-y-1.5 pl-4 marker:text-[#70A7FF]">
                                                <template x-for="(item, itemIndex) in block.items" :key="itemIndex">
                                                    <li
                                                        x-show="item.parts.length && (message.revealedChars ?? message.totalChars ?? 0) > (item.parts[0]?.start ?? 0)"
                                                        class="pl-1 leading-6"
                                                    >
                                                        <template x-for="(part, partIndex) in item.parts" :key="partIndex">
                                                            <span>
                                                                <template x-if="part.type === 'link' && visiblePartText(part, message.revealedChars).length > 0">
                                                                    <a :href="part.url" class="inline-flex items-center gap-1 text-[#70A7FF] underline hover:text-white font-medium transition-colors" x-text="visiblePartText(part, message.revealedChars)"></a>
                                                                </template>
                                                                <template x-if="part.type === 'strong' && visiblePartText(part, message.revealedChars).length > 0">
                                                                    <span class="font-bold text-white" x-text="visiblePartText(part, message.revealedChars)"></span>
                                                                </template>
                                                                <template x-if="part.type === 'text' && visiblePartText(part, message.revealedChars).length > 0">
                                                                    <span x-text="visiblePartText(part, message.revealedChars)"></span>
                                                                </template>
                                                            </span>
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
                            x-show="message.role === 'assistant' && message.actions.length && (message.revealedChars ?? message.totalChars ?? 0) >= (message.totalChars ?? 0)"
                            class="mt-2.5 grid grid-cols-2 gap-1.5"
                        >
                            <template x-for="(action, actionIndex) in message.actions" :key="action.label + action.url">
                                <a
                                    data-ai-action-chip
                                    :class="message.actions.length % 2 === 1 && actionIndex === 0 ? 'col-span-2 border-l-2 border-l-[#1C69D4]' : 'col-span-1'"
                                    class="group flex items-center justify-between gap-2.5 border border-zinc-800/90 bg-zinc-900/90 px-3 py-2 text-left transition-all hover:border-[#1C69D4] hover:bg-[#121215]"
                                    :href="action.url"
                                >
                                    <span class="min-w-0">
                                        <span class="block truncate text-[9px] font-black uppercase tracking-[0.14em] text-[#70A7FF]" x-text="action.subtitle || 'BMW Showroom'"></span>
                                        <span class="mt-0.5 block truncate text-xs font-bold text-white" x-text="action.label"></span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 text-[#70A7FF] transition-transform group-hover:translate-x-0.5 group-hover:text-white" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                    </svg>
                                </a>
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
                <span class="ai-assistant-typing-dot h-2 w-2 bg-[#70A7FF] animate-bounce [animation-delay:0ms]"></span>
                <span class="ai-assistant-typing-dot h-2 w-2 bg-[#70A7FF] animate-bounce [animation-delay:150ms]"></span>
                <span class="ai-assistant-typing-dot h-2 w-2 bg-[#70A7FF] animate-bounce [animation-delay:300ms]"></span>
                <span class="text-xs text-zinc-400 ml-1">BMW AI đang trả lời...</span>
            </div>
        </div>

        <form class="border-t border-white/10 bg-black/35 p-3" @submit.prevent="send()">
            <div x-show="imageError" class="mb-2 border border-rose-500/40 bg-rose-950/50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-300" x-text="imageError"></div>

            <div x-show="selectedImagePreview" class="mb-2 flex items-center justify-between gap-2 border border-white/15 bg-zinc-900/90 p-1.5">
                <div class="flex min-w-0 items-center gap-2.5">
                    <img :src="selectedImagePreview" alt="Xem trước ảnh" class="h-10 w-10 shrink-0 border border-white/10 object-cover">
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-bold text-white" x-text="selectedImageName || 'Ảnh đính kèm'"></p>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-[#70A7FF]">Sẵn sàng nhận diện</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="clearSelectedImage()"
                    class="flex h-7 w-7 shrink-0 items-center justify-center border border-zinc-700 text-xs font-black text-zinc-400 transition-colors hover:border-white hover:text-white"
                    aria-label="Xóa ảnh đính kèm"
                >
                    ×
                </button>
            </div>

            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                x-ref="imageInput"
                data-ai-image-input
                class="hidden"
                @change="handleImageSelect($event)"
            >

            <label class="sr-only" for="ai-assistant-message">Nhập câu hỏi về BMW</label>
            <div class="flex items-end gap-2">
                <button
                    type="button"
                    data-ai-attach-image
                    @click="openImagePicker()"
                    :disabled="loading || streaming"
                    class="flex h-11 w-11 shrink-0 items-center justify-center border border-zinc-800 bg-zinc-950/85 text-zinc-400 transition-colors hover:border-[#1C69D4] hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Đính kèm hình ảnh xe hoặc phụ kiện"
                    title="Đính kèm hình ảnh"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </button>
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
                <template x-if="streaming || loading">
                    <button
                        type="button"
                        data-ai-stop
                        @click="abortStreaming()"
                        class="flex h-11 shrink-0 items-center justify-center border border-rose-500/50 bg-rose-950/50 px-3 text-[10px] font-black uppercase tracking-wider text-rose-300 transition-colors hover:bg-rose-600 hover:text-white"
                        aria-label="Dừng phản hồi"
                    >
                        Dừng
                    </button>
                </template>
                <template x-if="!streaming && !loading">
                    <button
                        type="submit"
                        data-ai-send
                        class="flex h-11 w-11 shrink-0 items-center justify-center bg-[#1C69D4] text-sm font-black text-white transition-colors hover:bg-[#1554AA] disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!input.trim() && !selectedImage"
                        aria-label="Gửi câu hỏi"
                    >
                        →
                    </button>
                </template>
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
            class="ai-assistant-launcher flex items-center gap-3 border border-white/10 bg-black/95 p-3 shadow-2xl shadow-black/60 backdrop-blur-xl transition-all duration-300 hover:border-[#1C69D4] hover:shadow-[0_0_25px_rgba(28,105,212,0.4)]"
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
