<div>
    <x-admin.page-header
        eyebrow="CRM pipeline"
        title="Lịch hẹn"
        accent="Showroom"
        description="Theo dõi đăng ký lái thử, báo giá, tư vấn, trade-in và dịch vụ sau bán hàng."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng lead</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $appointments->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-xs font-bold text-emerald-300">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 hover:text-white">
                Đóng
            </button>
        </div>
    @endif

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.appointments.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr_1fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm khách hàng / xe">
                <input
                    id="search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Tên, SĐT, email hoặc mẫu xe..."
                    class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <select id="status" name="status" wire:model.live="status" class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">
                            {{ $statusOption->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="type" label="Loại yêu cầu">
                <select id="type" name="type" wire:model.live="type" class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả loại</option>
                    @foreach($types as $typeOption)
                        <option value="{{ $typeOption->value }}">
                            {{ $typeOption->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="leadPriority" label="Phân loại Lead AI">
                <select id="leadPriority" name="priority" wire:model.live="leadPriority" class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả mức độ</option>
                    <option value="hot">HOT LEAD</option>
                    <option value="warm">TIỀM NĂNG</option>
                    <option value="normal">THAM KHẢO</option>
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="rounded-none border border-zinc-700 bg-zinc-900 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]">
                    Lọc
                </button>
                <button type="button" wire:click="resetFilters" class="rounded-none border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                    Xóa
                </button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full min-w-[1120px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Thời điểm</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Khách hàng</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Nhu cầu</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Trạng thái</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Điều phối</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($appointments as $appointment)
                        <tr wire:key="admin-appointment-{{ $appointment->id }}" class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-widest text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600">{{ $appointment->appointment_date->format('H:i') }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-500">{{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chưa có liên hệ' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">{{ $appointment->type->label() }}</p>
                                    @if($appointment->lead_priority)
                                        <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em] {{ $appointment->leadPriorityColorClass() }}">
                                            {{ $appointment->leadPriorityLabel() }}
                                        </span>
                                    @endif
                                    @if($appointment->ai_conversation_id || filled($appointment->ai_chat_summary))
                                        <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em] text-[#70A7FF]">
                                            ĐÃ CHAT AI
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-2 max-w-xs truncate text-xs font-black uppercase tracking-wider text-zinc-300">{{ $appointment->product?->name ?? 'Chưa gắn sản phẩm' }}</p>
                                @if($appointment->ai_lead_note)
                                    <p class="mt-1.5 max-w-sm truncate text-[11px] font-medium text-zinc-400">
                                        AI: {{ $appointment->ai_lead_note }}
                                    </p>
                                @elseif($appointment->meta_data)
                                    <p class="mt-2 max-w-sm truncate text-[10px] font-medium text-zinc-600">
                                        {{ collect($appointment->meta_data)->filter()->take(2)->map(fn ($value, $key) => Str::headline($key).': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' | ') }}
                                    </p>
                                @endif
                                @if(filled($appointment->ai_chat_summary))
                                    <p class="mt-1 max-w-sm truncate text-[11px] font-medium text-[#70A7FF]/90">
                                        {{ $appointment->ai_chat_summary }}
                                    </p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @if($appointment->ai_conversation_id || filled($appointment->ai_visitor_id))
                                        <button
                                            type="button"
                                            wire:click="inspectAiConversation({{ $appointment->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-none border border-white/15 bg-[#121215] px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.18em] text-zinc-200 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]"
                                        >
                                            Xem lịch sử chat AI
                                        </button>
                                    @endif

                                    @if(filled($appointment->user?->email ?? $appointment->guest_email))
                                        <a
                                            href="{{ route('admin.mail-preview.appointment', $appointment) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1.5 rounded-none border border-zinc-800 bg-black px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.18em] text-zinc-400 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]"
                                        >
                                            Xem mẫu Email xác nhận
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="rounded-none border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <select
                                    aria-label="Cập nhật trạng thái lịch hẹn #{{ $appointment->id }}"
                                    wire:change="updateStatus({{ $appointment->id }}, $event.target.value)"
                                    class="rounded-none border-zinc-800 bg-black px-3 py-2 text-[9px] font-black uppercase tracking-[0.18em] text-zinc-400 transition-colors hover:text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                >
                                    @foreach($statuses as $statusOption)
                                        <option value="{{ $statusOption->value }}" @selected($appointment->status === $statusOption)>
                                            {{ $statusOption->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Chưa có lịch hẹn"
                                    description="Các đăng ký lái thử, tư vấn và dịch vụ từ website sẽ xuất hiện tại đây."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($appointments->hasPages())
        <div class="mt-8">
            {{ $appointments->links() }}
        </div>
    @endif

    @if($this->inspectedAppointment)
        @php
            $lead = $this->inspectedAppointment;
            $chatSession = $this->inspectedAiSession;
        @endphp
        <div
            class="fixed inset-0 z-[90] flex items-stretch justify-end bg-black/80 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-label="Lịch sử trò chuyện AI của khách hàng"
        >
            <div class="fixed inset-0" wire:click="closeAiConversationModal"></div>

            <div class="relative z-10 flex h-full w-full max-w-xl flex-col rounded-none border-l border-white/10 bg-[#09090b] text-white shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-white/10 bg-[#121215] px-6 py-5">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">
                                BÀN GIAO NGỮ CẢNH AI
                            </span>
                            @if($lead->lead_priority)
                                <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em] {{ $lead->leadPriorityColorClass() }}">
                                    {{ $lead->leadPriorityLabel() }}
                                </span>
                            @endif
                        </div>
                        <h3 class="mt-2 truncate text-lg font-black uppercase tracking-wider text-white">
                            {{ $lead->user?->name ?? $lead->guest_name ?? 'Khách vãng lai' }}
                        </h3>
                        <p class="mt-1 text-xs text-zinc-400">
                            {{ $lead->product?->name ?? 'Yêu cầu chung' }} · {{ $lead->type->label() }}
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeAiConversationModal"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-none border border-white/10 bg-black text-sm font-black text-zinc-400 transition-colors hover:border-white hover:text-white"
                        aria-label="Đóng lịch sử chat AI"
                    >
                        ×
                    </button>
                </header>

                @if(filled($lead->ai_chat_summary) || filled($lead->ai_lead_note))
                    <div class="border-b border-white/10 bg-zinc-950 px-6 py-4 space-y-2">
                        @if(filled($lead->ai_chat_summary))
                            <p class="text-xs leading-relaxed text-zinc-300">
                                <span class="font-black uppercase tracking-wider text-[#70A7FF]">Tóm tắt hội thoại:</span>
                                {{ $lead->ai_chat_summary }}
                            </p>
                        @endif
                        @if(filled($lead->ai_lead_note))
                            <p class="text-xs leading-relaxed text-zinc-400">
                                <span class="font-black uppercase tracking-wider text-zinc-300">Ghi chú AI Sales:</span>
                                {{ $lead->ai_lead_note }}
                            </p>
                        @endif
                    </div>
                @endif

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    @if($chatSession && $chatSession->messages->isNotEmpty())
                        @foreach($chatSession->messages as $chatMessage)
                            <div
                                wire:key="crm-chat-msg-{{ $chatMessage->id }}"
                                class="flex {{ $chatMessage->isUserMessage() ? 'justify-end' : 'justify-start' }}"
                            >
                                <div class="max-w-[88%] rounded-none border px-4 py-3 {{ $chatMessage->isUserMessage() ? 'border-[#1C69D4]/40 bg-[#1C69D4]/15 text-white' : 'border-white/10 bg-[#121215] text-zinc-200' }}">
                                    <div class="mb-1 flex items-center justify-between gap-4 text-[9px] font-black uppercase tracking-[0.2em]">
                                        <span class="{{ $chatMessage->isUserMessage() ? 'text-[#70A7FF]' : 'text-zinc-400' }}">
                                            {{ $chatMessage->isUserMessage() ? 'Khách hàng' : 'BMW AI Assistant' }}
                                        </span>
                                        <span class="text-zinc-600">{{ $chatMessage->created_at?->format('d/m H:i') }}</span>
                                    </div>
                                    <p class="whitespace-pre-wrap text-xs leading-relaxed">{{ $chatMessage->content }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="rounded-none border border-white/10 bg-[#121215] p-6 text-center text-xs text-zinc-400">
                            Chưa có bản ghi tin nhắn chi tiết cho lịch hẹn này.
                        </div>
                    @endif
                </div>

                <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 bg-[#121215] px-6 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($chatSession)
                            <a
                                href="{{ route('admin.ai-conversations.show', $chatSession) }}"
                                class="rounded-none border border-[#1C69D4] bg-[#1C69D4]/15 px-4 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white"
                            >
                                Mở hồ sơ phiên AI đầy đủ
                            </a>
                        @endif

                        @if(filled($lead->user?->email ?? $lead->guest_email))
                            <a
                                href="{{ route('admin.mail-preview.appointment', $lead) }}"
                                target="_blank"
                                rel="noopener"
                                class="rounded-none border border-white/15 bg-black px-4 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]"
                            >
                                Xem mẫu Email xác nhận
                            </a>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="closeAiConversationModal"
                        class="rounded-none border border-white/10 bg-black px-5 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white hover:text-white"
                    >
                        Đóng
                    </button>
                </footer>
            </div>
        </div>
    @endif
</div>
