@php
    $assistantPrompts = [
        'Tư vấn xe theo ngân sách',
        'So sánh BMW 330i và 530i',
        'Xem ưu đãi mới nhất',
        'Hướng dẫn đặt lịch tư vấn',
    ];
@endphp

<div
    id="ai-showroom-assistant"
    data-ai-assistant-widget
    data-ai-draggable="true"
    x-data="aiAssistantWidget(@js([
        'endpoint' => route('ai.showroom-assistant.ask'),
        'csrf' => csrf_token(),
        'fallback' => config('showroom_ai.fallback_message'),
        'greeting' => 'Trợ lý ảo AI của BMW Showroom xin chào. Tôi có thể giúp bạn chọn xe, tìm phụ kiện, xem ưu đãi hoặc hướng dẫn đặt lịch tư vấn.',
        'ready' => 'Trợ lý ảo AI sẵn sàng',
        'prompts' => $assistantPrompts,
    ]))"
    x-init="init()"
    :style="positionStyle"
    class="fixed z-[70] w-[min(23rem,calc(100vw-1.5rem))] text-white"
>
    <div class="sr-only">
        <p>Trợ lý ảo AI sẵn sàng</p>
        <p>Trợ lý ảo AI của BMW Showroom xin chào</p>
        @foreach ($assistantPrompts as $assistantPrompt)
            <p>{{ $assistantPrompt }}</p>
        @endforeach
    </div>

    <section
        x-show="panelOpen"
        x-transition.opacity.duration.150ms
        class="border border-zinc-800 bg-black/95 shadow-2xl shadow-black/60 backdrop-blur-xl"
        aria-label="Trợ lý ảo AI BMW Showroom"
    >
        <div
            data-ai-drag-handle
            class="flex cursor-move items-center gap-3 border-b border-zinc-900 px-4 py-3"
            title="Kéo để di chuyển trợ lý"
            @pointerdown="startDrag($event)"
        >
            <x-public.ai-assistant-avatar />
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#70A7FF]">BMW AI</p>
                <p class="mt-1 text-xs font-black uppercase leading-4 tracking-[0.08em] text-white sm:truncate sm:tracking-[0.16em]" x-text="readyText">
                    Trợ lý ảo AI sẵn sàng
                </p>
                <div class="mt-2 flex items-center gap-2 text-[10px] font-bold uppercase leading-4 tracking-[0.08em] text-zinc-500 sm:tracking-[0.18em]">
                    <span class="h-1.5 w-1.5 bg-emerald-400"></span>
                    <span>Trợ lý ảo AI sẵn sàng</span>
                </div>
            </div>
            <button
                type="button"
                data-ai-no-drag
                class="border border-zinc-800 px-3 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-zinc-500 transition-colors hover:border-white hover:text-white"
                @click="minimize()"
                aria-label="Thu gọn trợ lý AI"
            >
                Thu gọn
            </button>
        </div>

        <div class="scrollbar-none max-h-[18rem] space-y-3 overflow-y-auto px-4 py-4" x-ref="messages">
            <template x-for="(message, index) in messages" :key="index">
                <div
                    class="border px-4 py-3 text-sm font-medium leading-6"
                    :class="message.role === 'user' ? 'ml-8 border-[#1C69D4]/40 bg-[#1C69D4]/10 text-white' : 'mr-8 border-zinc-800 bg-zinc-950 text-zinc-300'"
                >
                    <p x-text="message.text"></p>
                </div>
            </template>

            <div x-show="loading" class="mr-8 border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm font-medium text-zinc-400">
                Đang chuẩn bị câu trả lời...
            </div>
        </div>

        <div class="border-t border-zinc-900 px-4 py-3">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <template x-for="prompt in prompts" :key="prompt">
                    <button
                        type="button"
                        class="border border-zinc-800 px-3 py-2 text-left text-[10px] font-black uppercase leading-4 tracking-[0.16em] text-zinc-400 transition-colors hover:border-[#1C69D4] hover:text-white"
                        @click="sendSuggestion(prompt)"
                        x-text="prompt"
                    ></button>
                </template>
            </div>

            <div x-show="responseSuggestions.length" class="mt-3 flex flex-wrap gap-2">
                <template x-for="suggestion in responseSuggestions" :key="suggestion.label + suggestion.url">
                    <a
                        class="border border-zinc-800 px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-[#70A7FF] transition-colors hover:border-[#1C69D4] hover:text-white"
                        :href="suggestion.url"
                        x-text="suggestion.label"
                    ></a>
                </template>
            </div>
        </div>

        <form class="flex gap-2 border-t border-zinc-900 p-4" @submit.prevent="send()">
            <label class="sr-only" for="ai-assistant-message">Nhập câu hỏi cho trợ lý AI</label>
            <input
                id="ai-assistant-message"
                data-ai-no-drag
                type="text"
                x-model="input"
                maxlength="600"
                class="min-w-0 flex-1 border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm font-medium text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:outline-none focus:ring-0"
                placeholder="Bạn muốn tìm BMW nào?"
            >
            <button
                type="submit"
                data-ai-no-drag
                class="border border-[#1C69D4] bg-[#1C69D4] px-4 py-3 text-[10px] font-black uppercase tracking-[0.18em] text-white transition-colors hover:bg-[#1554AA] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="loading"
            >
                Gửi
            </button>
        </form>
    </section>

    <div x-show="!panelOpen" class="flex items-end justify-end gap-3">
        <div
            x-show="greetingVisible"
            class="max-w-[15rem] border border-zinc-800 bg-black/95 px-4 py-3 text-xs font-semibold leading-5 text-zinc-300 shadow-2xl shadow-black/50"
        >
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]" x-text="readyText">
                Trợ lý ảo AI sẵn sàng
            </p>
            <p class="mt-2">Trợ lý ảo AI của BMW Showroom xin chào.</p>
        </div>
        <button
            type="button"
            data-ai-drag-handle
            class="flex cursor-move items-center gap-3 border border-zinc-800 bg-black/95 p-3 shadow-2xl shadow-black/60 transition-colors hover:border-[#1C69D4]"
            @click="openPanel()"
            @pointerdown="startDrag($event)"
            aria-label="Mở trợ lý ảo AI BMW"
            title="Kéo để di chuyển, bấm để mở trợ lý"
        >
            <x-public.ai-assistant-avatar />
            <span class="hidden text-left sm:block">
                <span class="block text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">Trợ lý BMW</span>
                <span class="mt-1 block text-xs font-black uppercase tracking-[0.14em] text-white">Trợ lý ảo AI sẵn sàng</span>
            </span>
        </button>
    </div>
</div>
