@php
    $statusFilterOptions = ['' => 'Tất cả trạng thái'];
    $statusActionOptions = [];
    foreach ($statuses as $statusOption) {
        $statusFilterOptions[$statusOption->value] = $statusOption->label();
        $statusActionOptions[$statusOption->value] = $statusOption->label();
    }

    $typeFilterOptions = ['' => 'Tất cả loại'];
    foreach ($types as $typeOption) {
        $typeFilterOptions[$typeOption->value] = $typeOption->label();
    }

    $priorityFilterOptions = [
        '' => 'Tất cả mức độ',
        'hot' => 'HOT LEAD',
        'warm' => 'TIỀM NĂNG',
        'normal' => 'THAM KHẢO',
    ];
@endphp

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
        <form action="{{ route('admin.appointments.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-3 lg:grid-cols-[1.35fr_1fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm khách hàng / xe">
                <input
                    id="search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Tên, SĐT, email hoặc mẫu xe..."
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <x-ui.custom-select
                    id="status"
                    name="status"
                    :value="$status"
                    :options="$statusFilterOptions"
                    wire-model="status"
                />
            </x-admin.form-field>

            <x-admin.form-field name="type" label="Loại yêu cầu">
                <x-ui.custom-select
                    id="type"
                    name="type"
                    :value="$type"
                    :options="$typeFilterOptions"
                    wire-model="type"
                />
            </x-admin.form-field>

            <x-admin.form-field name="leadPriority" label="Phân loại Lead AI">
                <x-ui.custom-select
                    id="leadPriority"
                    name="priority"
                    :value="$leadPriority"
                    :options="$priorityFilterOptions"
                    wire-model="leadPriority"
                />
            </x-admin.form-field>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 whitespace-nowrap rounded-none border border-zinc-700 bg-zinc-900 px-4 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer">
                    Lọc
                </button>
                <button type="button" wire:click="resetFilters" class="h-10 whitespace-nowrap rounded-none border border-zinc-800 px-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 transition-colors hover:border-zinc-600 hover:text-white cursor-pointer">
                    Xóa
                </button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full min-w-[1140px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Thời điểm</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Khách hàng</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Nhu cầu</th>
                        <th class="min-w-[140px] px-5 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Trạng thái</th>
                        <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Điều phối</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($appointments as $appointment)
                        <tr wire:key="admin-appointment-{{ $appointment->id }}" class="group align-top transition-colors hover:bg-zinc-900/60">
                            <td class="whitespace-nowrap px-5 py-5">
                                <p class="text-sm font-black tabular-nums tracking-wider text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-1 text-[11px] font-bold tabular-nums tracking-wider text-zinc-500">{{ $appointment->appointment_date->format('H:i') }}</p>
                            </td>
                            <td class="px-5 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-400">{{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chưa có liên hệ' }}</p>
                                @if(filled($appointment->guest_phone) && filled($appointment->user?->email ?? $appointment->guest_email))
                                    <p class="mt-0.5 text-[11px] tabular-nums text-zinc-500">{{ $appointment->guest_phone }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-5">
                                <div class="space-y-2">
                                    <!-- Dòng 1: Tên xe nổi bật + Loại lịch hẹn & Phân loại AI -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-white">
                                            {{ $appointment->product?->name ?? 'Chưa gắn sản phẩm' }}
                                        </span>
                                        <span class="whitespace-nowrap rounded-none border border-[#1C69D4]/30 bg-[#1C69D4]/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-[#70A7FF]">
                                            {{ $appointment->type->label() }}
                                        </span>
                                        @if($appointment->lead_priority)
                                            <span class="whitespace-nowrap rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] {{ $appointment->leadPriorityColorClass() }}">
                                                {{ $appointment->leadPriorityLabel() }}
                                            </span>
                                        @endif
                                        @if($appointment->ai_conversation_id || filled($appointment->ai_chat_summary))
                                            <span class="whitespace-nowrap rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-[#70A7FF]">
                                                ĐÃ CHAT AI
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Dòng 2: Tóm tắt AI / ghi chú 1-2 dòng -->
                                    @php
                                        $summaryLine = filled($appointment->ai_chat_summary)
                                            ? $appointment->ai_chat_summary
                                            : ($appointment->ai_lead_note ? 'AI: '.$appointment->ai_lead_note : null);
                                    @endphp
                                    @if($summaryLine)
                                        <p class="line-clamp-2 max-w-lg text-xs leading-relaxed text-zinc-400">
                                            {{ $summaryLine }}
                                        </p>
                                    @elseif($appointment->meta_data)
                                        <p class="line-clamp-1 max-w-lg text-[11px] text-zinc-500">
                                            {{ collect($appointment->meta_data)->filter()->take(2)->map(fn ($value, $key) => Str::headline($key).': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' · ') }}
                                        </p>
                                    @endif

                                    <!-- Dòng 3: Badge trạng thái email + thanh hành động gọn trên 1 hàng -->
                                    <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                        @if($appointment->confirmation_email_sent_at)
                                            <span class="whitespace-nowrap rounded-none border border-emerald-500/35 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-emerald-300">
                                                ĐÃ GỬI MAIL XÁC NHẬN · {{ $appointment->confirmation_email_sent_at->format('H:i d/m') }}
                                            </span>
                                        @else
                                            <span class="whitespace-nowrap rounded-none border border-zinc-800 bg-zinc-950 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-500">
                                                CHƯA GỬI MAIL
                                            </span>
                                        @endif

                                        @if($appointment->ai_conversation_id || filled($appointment->ai_visitor_id))
                                            <button
                                                type="button"
                                                wire:click="inspectAiConversation({{ $appointment->id }})"
                                                class="whitespace-nowrap rounded-none border border-white/10 bg-zinc-950 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer"
                                            >
                                                Xem lịch sử chat AI
                                            </button>
                                        @endif

                                        @if(filled($appointment->user?->email ?? $appointment->guest_email))
                                            <a
                                                href="{{ route('admin.mail-preview.appointment', $appointment) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="whitespace-nowrap rounded-none border border-zinc-800 bg-black px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-400 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]"
                                            >
                                                Xem mẫu Email xác nhận
                                            </a>
                                            <button
                                                type="button"
                                                wire:click="resendConfirmationEmail({{ $appointment->id }})"
                                                class="whitespace-nowrap rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                            >
                                                Gửi lại Email xác nhận
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="min-w-[140px] whitespace-nowrap px-5 py-5 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-none border px-3 py-1 text-[9px] font-black uppercase tracking-[0.16em] {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="min-w-[170px] whitespace-nowrap px-5 py-5 text-right">
                                <div class="ml-auto w-40">
                                    <x-ui.custom-select
                                        compact
                                        aria-label="Cập nhật trạng thái lịch hẹn #{{ $appointment->id }}"
                                        :value="$appointment->status->value"
                                        :options="$statusActionOptions"
                                        wire-action="updateStatus"
                                        :wire-record-id="$appointment->id"
                                    />
                                </div>
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
