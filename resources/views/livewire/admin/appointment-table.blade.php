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

<div @keydown.escape.window="$wire.closeAiConversationModal()">
    <x-admin.page-header
        eyebrow="CRM pipeline"
        title="Lịch hẹn"
        accent="Showroom"
        description="Theo dõi đăng ký lái thử, báo giá, tư vấn, trade-in và điều phối đón tiếp khách hàng."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng lead</p>
            <p class="mt-1 text-2xl font-black tabular-nums text-white">{{ $appointments->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Contextual Quick-Filter Strip -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <button
            type="button"
            wire:click="applyQuickFilter('pending')"
            class="group flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'pending' && $leadPriority === '' && ! $onlyToday ? 'border-amber-400 bg-amber-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-amber-400/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">Chờ xử lý</p>
                <p class="mt-1 text-[11px] text-zinc-400">Bấm để lọc việc cần gọi</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['pending'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('hot')"
            class="group flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $leadPriority === 'hot' && $status === '' && ! $onlyToday ? 'border-rose-500 bg-rose-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-rose-500/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-400">HOT LEAD</p>
                <p class="mt-1 text-[11px] text-zinc-400">Ưu tiên phản hồi 15p</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['hot'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('today')"
            class="group flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $onlyToday && $status === '' && $leadPriority === '' ? 'border-[#1C69D4] bg-[#1C69D4]/15' : 'border-[#242B38] bg-[#0B0F17] hover:border-[#1C69D4]' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">Hôm nay</p>
                <p class="mt-1 text-[11px] text-zinc-400">Lịch đón khách trong ngày</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['today'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('completed')"
            class="group flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'completed' && $leadPriority === '' && ! $onlyToday ? 'border-emerald-500 bg-emerald-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-emerald-500/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400">Hoàn thành</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã tiếp đón & hậu mãi</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['completed'] ?? 0 }}</span>
        </button>
    </div>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-xs font-bold text-emerald-300">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 hover:text-white cursor-pointer">
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
        <div class="overflow-x-auto lg:overflow-x-visible" wire:loading.class="opacity-60">
            <table class="w-full lg:table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-[#242B38] bg-[#0B0F17]/90">
                        <th class="w-[14%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Thời điểm</th>
                        <th class="w-[22%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Khách hàng</th>
                        <th class="w-[29%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Nhu cầu</th>
                        <th class="w-[15%] px-4 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Trạng thái</th>
                        <th class="w-[20%] px-4 py-3.5 text-right text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($appointments as $appointment)
                        @php
                            $customerName = $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai';
                            $customerEmail = $appointment->user?->email ?? $appointment->guest_email;
                            $hasAiChat = (bool) ($appointment->ai_conversation_id || filled($appointment->ai_visitor_id) || filled($appointment->ai_chat_summary));
                            $isHotLead = $appointment->lead_priority === \App\Models\Appointment::LEAD_PRIORITY_HOT;
                        @endphp
                        <tr wire:key="admin-appointment-{{ $appointment->id }}" class="group align-middle transition-colors hover:bg-[#0B0F17]/70">
                            <td class="whitespace-nowrap px-4 py-4">
                                <p class="text-sm font-black tabular-nums tracking-wider text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-zinc-500">{{ $appointment->appointment_date->format('H:i') }} · #{{ $appointment->id }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $customerName }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-400">{{ $appointment->guest_phone ?: ($customerEmail ?: 'Chưa có liên hệ') }}</p>
                                @if(filled($appointment->guest_phone) && filled($customerEmail))
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $customerEmail }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="truncate text-xs font-bold uppercase tracking-wider text-white">
                                        {{ $appointment->product?->name ?? ($appointment->servicePackageLabel() ?: 'Tư vấn tại Showroom') }}
                                    </span>
                                    @if($isHotLead)
                                        <span class="shrink-0 whitespace-nowrap rounded-none border border-rose-500/40 bg-rose-500/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-rose-400">
                                            HOT LEAD
                                        </span>
                                    @else
                                        <span class="shrink-0 whitespace-nowrap rounded-none border border-[#1C69D4]/30 bg-[#1C69D4]/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-[#70A7FF]">
                                            {{ $appointment->type->label() }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1.5 flex items-center gap-2 text-[10px]">
                                    @if($appointment->confirmation_email_sent_at)
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-400">
                                            <span class="h-1.5 w-1.5 bg-emerald-400"></span>
                                            <span>ĐÃ GỬI MAIL XÁC NHẬN</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-medium text-zinc-500">
                                            <span class="h-1.5 w-1.5 bg-zinc-600"></span>
                                            <span>CHƯA GỬI MAIL</span>
                                        </span>
                                    @endif

                                    @if($hasAiChat)
                                        <span class="text-zinc-700">·</span>
                                        <span class="font-bold text-[#70A7FF]">ĐÃ CHAT AI</span>
                                    @endif
                                </div>

                                @if(filled($appointment->ai_lead_note))
                                    <p class="mt-1 truncate text-[11px] text-zinc-400" title="{{ $appointment->ai_lead_note }}">
                                        {{ $appointment->ai_lead_note }}
                                    </p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-none border px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.16em] {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <div class="w-32">
                                        <x-ui.custom-select
                                            compact
                                            aria-label="Cập nhật trạng thái lịch hẹn #{{ $appointment->id }}"
                                            :value="$appointment->status->value"
                                            :options="$statusActionOptions"
                                            wire-action="updateStatus"
                                            :wire-record-id="$appointment->id"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="inspectAppointment({{ $appointment->id }})"
                                        data-testid="inspect-appointment-{{ $appointment->id }}"
                                        title="Xem hồ sơ & tác vụ{{ $hasAiChat ? ' · Xem lịch sử chat AI' : '' }}"
                                        aria-label="Xem hồ sơ & tác vụ lịch hẹn #{{ $appointment->id }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-none border border-[#242B38] bg-[#0B0F17] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/15 hover:text-[#70A7FF] cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        @if($hasAiChat)
                                            <span class="sr-only">Xem lịch sử chat AI</span>
                                        @endif
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6">
                                <x-admin.empty-state
                                    title="Chưa có lịch hẹn phù hợp"
                                    description="Các yêu cầu lái thử, tư vấn mua xe và báo giá từ website sẽ tự động đổ về bảng điều phối này."
                                    hint="Mẹo: Nhấn nút 'Xóa' ở thanh bộ lọc hoặc chọn một thẻ chỉ số nhanh phía trên để xem toàn bộ lịch hẹn."
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

    <!-- Quick Inspector Slide-Over Drawer (Teleported to body, Z-100/101, Independent Scroll & 2-Way Timeline) -->
    @if($this->inspectedAppointment)
        @php
            $lead = $this->inspectedAppointment;
            $chatSession = $this->inspectedAiSession;
            $leadName = $lead->user?->name ?? $lead->guest_name ?? 'Khách vãng lai';
            $leadEmail = $lead->user?->email ?? $lead->guest_email;
            $officialSentAt = is_array($lead->meta_data) ? ($lead->meta_data['official_confirmation_sent_at'] ?? null) : null;
            $timelineEvents = \App\Services\Crm\InteractionTimelineRecorder::timelineForAppointment($lead);
        @endphp
        <div
            wire:key="appointment-inspector-drawer-{{ $lead->id }}"
            x-data="{
                init() { document.body.classList.add('overflow-hidden'); },
                destroy() { document.body.classList.remove('overflow-hidden'); },
                close() {
                    document.body.classList.remove('overflow-hidden');
                    $wire.closeAiConversationModal();
                }
            }"
            @keydown.escape.window="close()"
        >
            <template x-teleport="body">
                <div
                    class="fixed inset-0 z-[100]"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Hồ sơ Lịch hẹn &amp; Tác vụ"
                >
                    <div
                        class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm"
                        @click="close()"
                    ></div>

                    <div class="fixed inset-y-0 right-0 z-[101] flex h-dvh w-full max-w-xl flex-col rounded-none border-l border-[#1C69D4] bg-[#0B0F17] text-white shadow-2xl">
                        <!-- Header cố định (shrink-0) -->
                        <header class="shrink-0 flex items-start justify-between gap-4 border-b border-[#242B38] bg-[#050505] px-6 py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">
                                        HỒ SƠ LỊCH HẸN #{{ $lead->id }}
                                    </span>
                                    @if($lead->lead_priority)
                                        <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] {{ $lead->leadPriorityColorClass() }}">
                                            {{ $lead->leadPriorityLabel() }}
                                        </span>
                                    @endif
                                    <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] {{ $lead->status->color() }}">
                                        {{ $lead->status->label() }}
                                    </span>
                                </div>
                                <h3 class="mt-2 truncate text-lg font-black uppercase tracking-wider text-white">
                                    {{ $leadName }}
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-400">
                                    {{ $lead->product?->name ?? ($lead->servicePackageLabel() ?: 'Tư vấn chung tại Showroom') }} · {{ $lead->type->label() }} · {{ $lead->appointment_date?->format('H:i — d/m/Y') }}
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="close()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none border border-[#242B38] bg-[#0B0F17] text-lg font-black text-zinc-300 transition-colors hover:border-white hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                aria-label="Đóng hồ sơ lịch hẹn"
                            >
                                ×
                            </button>
                        </header>

                        <!-- Thân cuộn độc lập (min-h-0 flex-1 overflow-y-auto overscroll-contain) -->
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 space-y-6">
                            <!-- Khối 1: Tóm tắt khách hàng & Ghi chú AI -->
                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">1. Tóm tắt khách hàng &amp; Ghi chú AI</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Số điện thoại</p>
                                        <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="font-bold tabular-nums text-white">{{ $lead->guest_phone ?: 'Chưa cung cấp' }}</span>
                                            @if(filled($lead->guest_phone))
                                                <a href="tel:{{ $lead->guest_phone }}" class="border border-[#242B38] bg-[#0B0F17] px-2 py-0.5 text-[9px] font-bold uppercase text-emerald-400 hover:border-emerald-500">Gọi</a>
                                                <button
                                                    type="button"
                                                    @click="navigator.clipboard?.writeText('{{ $lead->guest_phone }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                    class="border border-[#242B38] bg-[#0B0F17] px-2 py-0.5 text-[9px] font-bold uppercase text-zinc-300 hover:border-[#1C69D4] cursor-pointer"
                                                    x-text="copied ? 'Đã copy' : 'Copy'"
                                                ></button>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Email liên hệ</p>
                                        <p class="mt-1 truncate font-semibold text-zinc-200">{{ $leadEmail ?: 'Chưa cung cấp' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Nhu cầu &amp; Mẫu xe</p>
                                        <p class="mt-1 font-semibold text-white">{{ $lead->product?->name ?? ($lead->servicePackageLabel() ?: 'Tư vấn trực tiếp') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Nguồn &amp; Ý định Lead</p>
                                        <p class="mt-1 font-semibold text-zinc-300">{{ $lead->leadIntentLabel() }}</p>
                                    </div>
                                </div>

                                @if(filled($lead->notes))
                                    <div class="border-t border-white/10 pt-3 text-xs text-zinc-300">
                                        <span class="font-bold text-zinc-400">Ghi chú khách hàng:</span> {{ $lead->notes }}
                                    </div>
                                @endif

                                @if(filled($lead->ai_lead_note) || filled($lead->ai_chat_summary))
                                    <div class="border-t border-white/10 pt-3 space-y-1.5 text-xs">
                                        @if(filled($lead->ai_lead_note))
                                            <p class="text-zinc-300"><span class="font-bold text-[#70A7FF]">Nhận định AI:</span> {{ $lead->ai_lead_note }}</p>
                                        @endif
                                        @if(filled($lead->ai_chat_summary))
                                            <p class="text-zinc-400"><span class="font-bold text-zinc-300">Tóm tắt hội thoại AI:</span> {{ $lead->ai_chat_summary }}</p>
                                        @endif
                                    </div>
                                @endif
                            </section>

                            <!-- Khối 2: Trạng thái Email & Hậu mãi -->
                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-2.5">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">2. Trạng thái Email &amp; Hậu mãi</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 text-xs">
                                    <div class="border border-white/5 bg-[#0B0F17] p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Thư tiếp nhận hệ thống</p>
                                        <p class="mt-1 font-bold {{ $lead->confirmation_email_sent_at ? 'text-emerald-400' : 'text-zinc-500' }}">
                                            {{ $lead->confirmation_email_sent_at ? $lead->confirmation_email_sent_at->format('H:i · d/m/Y') : 'Chưa ghi nhận' }}
                                        </p>
                                    </div>
                                    <div class="border border-white/5 bg-[#0B0F17] p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Thư xác nhận chính thức</p>
                                        <p class="mt-1 font-bold {{ $officialSentAt ? 'text-emerald-400' : 'text-zinc-500' }}">
                                            {{ $officialSentAt ? \Illuminate\Support\Carbon::parse($officialSentAt)->format('H:i · d/m/Y') : 'Chưa gửi từ Showroom' }}
                                        </p>
                                    </div>
                                    <div class="border border-white/5 bg-[#0B0F17] p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Thư nhắc lịch 24h</p>
                                        <p class="mt-1 font-bold {{ $lead->reminder_sent_at ? 'text-indigo-300' : 'text-zinc-500' }}">
                                            {{ $lead->reminder_sent_at ? $lead->reminder_sent_at->format('H:i · d/m/Y') : 'Đang chờ đến khung 24h' }}
                                        </p>
                                    </div>
                                    <div class="border border-white/5 bg-[#0B0F17] p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Thư hậu mãi (sau 2h)</p>
                                        <p class="mt-1 font-bold {{ $lead->followup_email_sent_at ? 'text-amber-300' : 'text-zinc-500' }}">
                                            {{ $lead->followup_email_sent_at ? $lead->followup_email_sent_at->format('H:i · d/m/Y') : 'Sẽ gửi khi Hoàn thành' }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <!-- Khối 3: Lịch sử Tương tác 2 chiều Khách hàng <-> Hệ thống -->
                            <x-admin.interaction-timeline
                                :events="$timelineEvents"
                                heading="3. Lịch sử tương tác Khách hàng ↔ Hệ thống"
                            />

                            <!-- Khối 4: Trung tâm Thao tác nhanh & Xem trước 5 Mẫu Email (Action Hub) -->
                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">4. Trung tâm Thao tác &amp; Xem trước Email</p>

                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                    @if(filled($leadEmail))
                                        <button
                                            type="button"
                                            @click="$wire.resendConfirmationEmail({{ $lead->id }})"
                                            class="sm:col-span-2 flex items-center justify-center gap-2 rounded-none border border-emerald-500/60 bg-emerald-600/20 px-4 py-3 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300 transition-colors hover:bg-emerald-600 hover:text-white cursor-pointer"
                                        >
                                            <span>Gửi thư Xác nhận chính thức từ Showroom</span>
                                        </button>
                                    @endif

                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'system_received']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-[#1C69D4]/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-[#70A7FF] transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/15"
                                    >
                                        Xem thư Hệ thống tiếp nhận
                                    </a>

                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'admin_confirmed']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-emerald-500/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-300 transition-colors hover:border-emerald-400 hover:bg-emerald-500/15"
                                    >
                                        Xem thư Admin duyệt VIP
                                    </a>

                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'guest_rescheduled']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-amber-500/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-amber-300 transition-colors hover:border-amber-400 hover:bg-amber-500/15"
                                    >
                                        Xem thư Khách đổi lịch
                                    </a>

                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'reminder']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-indigo-500/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-300 transition-colors hover:border-indigo-400 hover:bg-indigo-500/15"
                                    >
                                        Xem thư Nhắc lịch 24h
                                    </a>

                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'followup']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="sm:col-span-2 flex items-center justify-center rounded-none border border-amber-600/45 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-amber-200 transition-colors hover:border-amber-500 hover:bg-amber-600/15"
                                    >
                                        Xem thư Hậu mãi trả góp
                                    </a>

                                    @if($chatSession)
                                        <a
                                            href="{{ route('admin.ai-conversations.show', $chatSession) }}"
                                            class="sm:col-span-2 flex items-center justify-center rounded-none border border-[#1C69D4] bg-[#1C69D4]/15 px-4 py-2.5 text-[10px] font-black uppercase tracking-[0.18em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white"
                                        >
                                            Mở lịch sử Chat AI đầy đủ ({{ $chatSession->messages->count() }} tin nhắn)
                                        </a>
                                    @endif
                                </div>
                            </section>

                            <!-- Timeline Chat AI (nếu có) -->
                            @if($chatSession && $chatSession->messages->isNotEmpty())
                                @php
                                    $latestStructuredMeta = $chatSession->messages
                                        ->reverse()
                                        ->first(fn ($msg) => ! $msg->isUserMessage() && is_array($msg->meta_data) && $msg->meta_data !== [])
                                        ?->meta_data ?? [];
                                    $drawerLeadLevel = (string) ($latestStructuredMeta['lead_level'] ?? '');
                                    $drawerLeadStyle = match ($drawerLeadLevel) {
                                        'hot' => 'border-rose-500/40 bg-rose-500/15 text-rose-300',
                                        'warm' => 'border-amber-500/40 bg-amber-500/15 text-amber-300',
                                        default => 'border-white/15 bg-white/5 text-zinc-300',
                                    };
                                    $drawerMetaProducts = is_array($latestStructuredMeta['recommended_product_ids'] ?? null)
                                        ? $latestStructuredMeta['recommended_product_ids']
                                        : [];
                                    $drawerMetaTools = is_array($latestStructuredMeta['tool_calls'] ?? null)
                                        ? $latestStructuredMeta['tool_calls']
                                        : [];
                                @endphp
                                <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">BÀN GIAO NGỮ CẢNH AI · Hội thoại gần nhất với BMW AI</p>
                                    @if($latestStructuredMeta !== [])
                                        <div class="flex flex-wrap items-center gap-1.5 pb-1">
                                            @if(filled($latestStructuredMeta['intent'] ?? null))
                                                <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-[#70A7FF]">
                                                    INTENT: {{ $latestStructuredMeta['intent'] }}
                                                </span>
                                            @endif
                                            @if($drawerLeadLevel !== '')
                                                <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] {{ $drawerLeadStyle }}">
                                                    LEAD: {{ strtoupper($drawerLeadLevel) }}
                                                </span>
                                            @endif
                                            @if($drawerMetaProducts !== [])
                                                <span class="rounded-none border border-emerald-500/35 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.14em] text-emerald-300">
                                                    #{{ implode(', #', $drawerMetaProducts) }}
                                                </span>
                                            @endif
                                            @if(filled($latestStructuredMeta['recommended_service_package'] ?? null))
                                                <span class="rounded-none border border-sky-500/35 bg-sky-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.14em] text-sky-300">
                                                    {{ $latestStructuredMeta['recommended_service_package'] }}
                                                </span>
                                            @endif
                                            @foreach($drawerMetaTools as $toolName)
                                                <span class="rounded-none border border-violet-500/35 bg-violet-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.14em] text-violet-300">
                                                    TOOL: {{ $toolName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="space-y-2.5">
                                        @foreach($chatSession->messages->take(-6) as $chatMessage)
                                            <div
                                                wire:key="crm-chat-msg-{{ $chatMessage->id }}"
                                                class="rounded-none border px-3.5 py-2.5 text-xs {{ $chatMessage->isUserMessage() ? 'border-[#1C69D4]/40 bg-[#1C69D4]/15 text-white' : 'border-white/10 bg-[#0B0F17] text-zinc-300' }}"
                                            >
                                                <p class="mb-1 text-[9px] font-black uppercase tracking-[0.18em] {{ $chatMessage->isUserMessage() ? 'text-[#70A7FF]' : 'text-zinc-500' }}">
                                                    {{ $chatMessage->isUserMessage() ? 'Khách hàng' : 'BMW AI Assistant' }} · {{ $chatMessage->created_at?->format('d/m H:i') }}
                                                </p>
                                                <p class="whitespace-pre-wrap leading-relaxed">{{ $chatMessage->content }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </div>

                        <!-- Footer cố định (shrink-0) -->
                        <footer class="shrink-0 flex items-center justify-between gap-3 border-t border-[#242B38] bg-[#050505] px-6 py-4">
                            <span class="text-[10px] font-semibold text-zinc-500">Nhấn Esc hoặc click vùng tối để đóng</span>
                            <button
                                type="button"
                                @click="close()"
                                class="rounded-none border border-[#242B38] bg-[#0B0F17] px-5 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-200 transition-colors hover:border-white hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                            >
                                Đóng
                            </button>
                        </footer>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
