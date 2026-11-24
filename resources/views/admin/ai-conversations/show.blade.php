<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.ai-conversations.index')"
        eyebrow="AI Conversation Detail"
        title="{{ $session->displayLabel() }}"
        description="Timeline trao đổi giữa khách và BMW AI Assistant, kèm metadata an toàn để đội showroom follow-up."
    />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_24rem]">
        <x-admin.card class="!p-0 overflow-hidden">
            <div class="border-b border-white/[0.08] bg-[#070A10]/60 px-6 py-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-zinc-400">Timeline chat</p>
                <p class="mt-2 text-sm font-semibold text-zinc-300">{{ $session->message_count }} message đã ghi nhận</p>
            </div>

            <div class="space-y-5 p-6">
                @forelse ($session->messages as $message)
                    <article class="flex {{ $message->isUserMessage() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-3xl rounded-2xl border {{ $message->isUserMessage() ? 'border-[#1C69D4]/30 bg-[#1C69D4]/10' : 'border-white/[0.08] bg-[#070A10]/90' }} p-5 shadow-md shadow-black/20">
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

                            @php
                                $structuredMeta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($message->meta_data);
                            @endphp
                            @if (! $message->isUserMessage() && $structuredMeta !== [])
                                @php
                                    $metaLeadLevel = (string) ($structuredMeta['lead_level'] ?? 'cold');
                                    $metaLeadStyle = match ($metaLeadLevel) {
                                        'hot' => 'border-rose-500/40 bg-rose-500/15 text-rose-300',
                                        'warm' => 'border-amber-500/40 bg-amber-500/15 text-amber-300',
                                        default => 'border-white/15 bg-white/5 text-zinc-300',
                                    };
                                    $metaProducts = is_array($structuredMeta['recommended_product_ids'] ?? null)
                                        ? $structuredMeta['recommended_product_ids']
                                        : [];
                                    $metaToolCalls = is_array($structuredMeta['tool_calls'] ?? null)
                                        ? $structuredMeta['tool_calls']
                                        : [];
                                @endphp
                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/[0.08] pt-3">
                                    @if (filled($structuredMeta['intent'] ?? null))
                                        <span class="rounded-full border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#70A7FF]">
                                            INTENT: {{ $structuredMeta['intent'] }}
                                        </span>
                                    @endif

                                    @if (filled($structuredMeta['lead_level'] ?? null))
                                        <span class="rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $metaLeadStyle }}">
                                            LEAD: {{ strtoupper($metaLeadLevel) }}
                                        </span>
                                    @endif

                                    @if ($metaProducts !== [])
                                        <span class="rounded-full border border-emerald-500/35 bg-emerald-500/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-300">
                                            SẢN PHẨM #{{ implode(', #', $metaProducts) }}
                                        </span>
                                    @endif

                                    @if (filled($structuredMeta['recommended_service_package'] ?? null))
                                        <span class="rounded-full border border-sky-500/35 bg-sky-500/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-sky-300">
                                            GÓI DỊCH VỤ: {{ $structuredMeta['recommended_service_package'] }}
                                        </span>
                                    @endif

                                    @foreach ($metaToolCalls as $toolName)
                                        <span class="rounded-full border border-violet-500/35 bg-violet-500/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-violet-300">
                                            TOOL: {{ $toolName }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

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
                        <dd class="mt-1 tabular-nums text-xs text-zinc-400">{{ $session->maskedVisitorId() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Seen</dt>
                        <dd class="mt-1 text-zinc-300">
                            {{ optional($session->first_seen_at)->format('d/m/Y H:i') ?? 'n/a' }}
                            <span class="text-zinc-700">→</span>
                            {{ optional($session->last_seen_at)->format('d/m/Y H:i') ?? 'n/a' }}
                        </dd>
                    </div>
                    @if ($session->contextProductLabel())
                        <div class="border-t border-zinc-800/80 pt-3">
                            <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">Sản phẩm đang xem</dt>
                            <dd class="mt-1.5 font-bold text-white">
                                {{ $session->contextProductLabel() }}
                            </dd>
                            @if ($session->contextProduct && $session->contextProduct->is_active)
                                <dd class="mt-2 flex flex-wrap gap-3 text-[11px] font-bold">
                                    <a href="{{ route('products.show', $session->contextProduct->slug) }}" target="_blank" class="text-[#70A7FF] hover:text-white">
                                        Xem public →
                                    </a>
                                    <a href="{{ route('admin.products.edit', $session->contextProduct) }}" class="text-zinc-400 hover:text-white">
                                        Mở trong Admin →
                                    </a>
                                </dd>
                            @endif
                        </div>
                    @endif
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
                    <div class="mt-5 border-t border-white/[0.08] pt-5">
                        @if ($session->linked_source_type === 'accessory_order')
                            <a href="{{ route('admin.accessory-orders.show', $linkedRecord) }}" class="inline-flex w-full justify-center rounded-xl bg-[#1C69D4] px-5 py-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6]">
                                Mở đơn liên quan
                            </a>
                        @else
                            <p class="text-xs font-medium leading-5 text-zinc-500">Lịch hẹn liên quan đang nằm trong danh sách appointments.</p>
                        @endif
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card class="!p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-zinc-400">Status</p>
                <form method="POST" action="{{ route('admin.ai-conversations.update-status', $session) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <x-ui.custom-select
                        name="status"
                        :value="$session->status"
                        :options="$statusOptions"
                    />
                    <button type="submit" class="w-full rounded-xl bg-white px-5 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white cursor-pointer">
                        Cập nhật
                    </button>
                </form>
            </x-admin.card>
        </aside>
    </div>
</x-admin-layout>
