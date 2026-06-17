<x-admin-layout>
    <x-admin.page-header
        eyebrow="AI Conversation Detail"
        title="{{ $session->displayLabel() }}"
        description="Timeline trao đổi giữa khách và BMW AI Assistant, kèm metadata an toàn để đội showroom follow-up."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.ai-conversations.index') }}" class="inline-flex items-center justify-center border border-zinc-800 px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <x-admin.card class="!p-0 overflow-hidden">
            <div class="border-b border-zinc-900 bg-black/45 px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Timeline chat</p>
                <p class="mt-2 text-sm font-semibold text-zinc-300">{{ $session->message_count }} message đã ghi nhận</p>
            </div>

            <div class="space-y-5 p-5">
                @forelse ($session->messages as $message)
                    <article class="flex {{ $message->isUserMessage() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-3xl border {{ $message->isUserMessage() ? 'border-[#1C69D4]/30 bg-[#1C69D4]/10' : 'border-zinc-800 bg-zinc-950/90' }} p-5">
                            <div class="mb-3 flex flex-wrap items-center gap-3">
                                <span class="text-[10px] font-black uppercase tracking-[0.22em] {{ $message->isUserMessage() ? 'text-[#70A7FF]' : 'text-zinc-500' }}">
                                    {{ $message->isUserMessage() ? 'Khách' : 'BMW AI' }}
                                </span>
                                <span class="text-xs font-medium text-zinc-600">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                                @if ($message->response_reason)
                                    <x-admin.badge tone="{{ $message->response_reason === 'ok' ? 'emerald' : 'yellow' }}">
                                        {{ $message->response_reason }}
                                    </x-admin.badge>
                                @endif
                            </div>

                            <div class="whitespace-pre-wrap break-words text-sm font-medium leading-7 text-zinc-200">{{ $message->content }}</div>

                            @if ($message->page_path || $message->provider || $message->latency_ms)
                                <dl class="mt-4 grid grid-cols-1 gap-2 border-t border-white/10 pt-4 text-xs text-zinc-600 sm:grid-cols-3">
                                    <div>
                                        <dt class="font-black uppercase tracking-[0.16em] text-zinc-700">Page</dt>
                                        <dd class="mt-1 break-words">{{ $message->page_path ?? 'n/a' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-black uppercase tracking-[0.16em] text-zinc-700">Provider</dt>
                                        <dd class="mt-1">{{ $message->provider ?? 'n/a' }} {{ $message->model ? '· '.$message->model : '' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-black uppercase tracking-[0.16em] text-zinc-700">Latency</dt>
                                        <dd class="mt-1">{{ $message->latency_ms ? $message->latency_ms.'ms' : 'n/a' }}</dd>
                                    </div>
                                </dl>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-admin.empty-state
                        title="Chưa có message"
                        description="Session này đã được tạo nhưng chưa ghi nhận nội dung hội thoại."
                    />
                @endforelse
            </div>
        </x-admin.card>

        <aside class="space-y-6">
            <x-admin.card class="!p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Visitor profile</p>

                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Display</dt>
                        <dd class="mt-1 font-black text-white">{{ $session->displayLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">IP</dt>
                        <dd class="mt-1 font-semibold text-zinc-300">{{ $session->maskedIp() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Visitor ID</dt>
                        <dd class="mt-1 font-mono text-xs text-zinc-400">{{ $session->maskedVisitorId() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Seen</dt>
                        <dd class="mt-1 text-zinc-300">
                            {{ optional($session->first_seen_at)->format('d/m/Y H:i') ?? 'n/a' }}
                            <span class="text-zinc-700">→</span>
                            {{ optional($session->last_seen_at)->format('d/m/Y H:i') ?? 'n/a' }}
                        </dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card class="!p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Customer link</p>

                <div class="mt-5 space-y-3 text-sm font-semibold text-zinc-300">
                    <p>{{ $session->linkedSourceLabel() }}</p>
                    @if ($session->customer_email)
                        <p class="text-zinc-500">{{ $session->customer_email }}</p>
                    @endif
                    @if ($session->customer_phone)
                        <p class="text-zinc-500">{{ $session->customer_phone }}</p>
                    @endif
                    @if ($session->link_confidence)
                        <x-admin.badge tone="{{ $session->link_confidence === 'visitor_id' ? 'emerald' : 'yellow' }}">{{ $session->link_confidence }}</x-admin.badge>
                    @endif
                </div>

                @if ($linkedRecord)
                    <div class="mt-5 border-t border-white/10 pt-5">
                        @if ($session->linked_source_type === 'accessory_order')
                            <a href="{{ route('admin.accessory-orders.show', $linkedRecord) }}" class="inline-flex w-full justify-center bg-[#1C69D4] px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:bg-white hover:text-black">
                                Mở đơn liên quan
                            </a>
                        @else
                            <p class="text-xs font-medium leading-5 text-zinc-500">Lịch hẹn liên quan đang nằm trong danh sách appointments.</p>
                        @endif
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card class="!p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Status</p>
                <form method="POST" action="{{ route('admin.ai-conversations.update-status', $session) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="w-full border border-zinc-800 bg-black/60 px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($session->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full border border-white bg-white px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                        Cập nhật
                    </button>
                </form>
            </x-admin.card>
        </aside>
    </div>
</x-admin-layout>
