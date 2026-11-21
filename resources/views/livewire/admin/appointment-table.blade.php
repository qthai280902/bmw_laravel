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

    $salesFilterOptions = [
        '' => 'Tất cả Sales',
        'mine' => 'Hồ sơ của tôi',
        'unassigned' => 'Chưa phân công',
    ];
    foreach ($salesRoster as $salesMember) {
        $salesFilterOptions[(string) $salesMember->id] = $salesMember->name;
    }

    $datePresetOptions = [
        '' => 'Mọi thời điểm',
        'today' => 'Hôm nay',
        '7d' => '7 ngày qua',
        '30d' => '30 ngày qua',
        'this_month' => 'Tháng này',
        'custom' => 'Tùy chọn ngày',
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
            <p class="text-[11px] font-semibold text-zinc-400">Tổng lead</p>
            <p class="mt-0.5 text-2xl font-extrabold tabular-nums text-white">{{ $appointments->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Contextual Quick-Filter Strip (4 Rounded Cards) -->
    <div class="mb-6 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <button
            type="button"
            wire:click="applyQuickFilter('pending')"
            class="group flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'pending' && $leadPriority === '' && ! $onlyToday ? 'border-amber-400/70 bg-amber-500/15 shadow-lg shadow-amber-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-amber-400/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-amber-300">Chờ xử lý</p>
                <p class="mt-1 text-[11px] text-zinc-400">Bấm để lọc việc cần gọi</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['pending'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('hot')"
            class="group flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $leadPriority === 'hot' && $status === '' && ! $onlyToday ? 'border-rose-500/70 bg-rose-500/15 shadow-lg shadow-rose-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-rose-500/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-rose-300">HOT LEAD</p>
                <p class="mt-1 text-[11px] text-zinc-400">Ưu tiên phản hồi 15p</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['hot'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('today')"
            class="group flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $onlyToday && $status === '' && $leadPriority === '' ? 'border-[#1C69D4]/70 bg-[#1C69D4]/15 shadow-lg shadow-[#1C69D4]/15' : 'border-white/[0.08] bg-[#0D121C] hover:border-[#1C69D4]/50 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-[#70A7FF]">Hôm nay</p>
                <p class="mt-1 text-[11px] text-zinc-400">Lịch đón khách trong ngày</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['today'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('completed')"
            class="group flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'completed' && $leadPriority === '' && ! $onlyToday ? 'border-emerald-500/70 bg-emerald-500/15 shadow-lg shadow-emerald-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-emerald-500/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-emerald-300">Hoàn thành</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã tiếp đón &amp; hậu mãi</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['completed'] ?? 0 }}</span>
        </button>
    </div>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-emerald-500/30 bg-emerald-500/15 px-5 py-3.5 text-xs font-semibold text-emerald-200">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-300 hover:bg-white/10 hover:text-white cursor-pointer">
                Đóng
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-rose-500/35 bg-rose-500/15 px-5 py-3.5 text-xs font-semibold text-rose-200">
            <span>{{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage', null)" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-rose-300 hover:bg-white/10 hover:text-white cursor-pointer">
                Đóng
            </button>
        </div>
    @endif

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.appointments.index') }}" method="GET" wire:submit.prevent="$refresh" class="space-y-3.5">
            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
                <x-admin.form-field name="search" label="Tìm kiếm mã kép / khách / xe">
                    <input
                        id="search"
                        type="text"
                        name="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Mã #SĐT_STT, dd/mm/yyyy/NN, tên, SĐT, xe..."
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-2 focus:ring-[#1C69D4]/40"
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

                <x-admin.form-field name="salesId" label="Sales phụ trách">
                    <x-ui.custom-select
                        id="salesId"
                        name="sales_id"
                        :value="$salesId"
                        :options="$salesFilterOptions"
                        wire-model="salesId"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="datePreset" label="Khoảng ngày hẹn">
                    <x-ui.custom-select
                        id="datePreset"
                        name="date_preset"
                        :value="$datePreset"
                        :options="$datePresetOptions"
                        wire-model="datePreset"
                    />
                </x-admin.form-field>

                <div class="flex items-center gap-2">
                    <button type="submit" class="h-10 whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer">
                        Lọc
                    </button>
                    <button type="button" wire:click="resetFilters" class="h-10 whitespace-nowrap rounded-xl border border-white/10 bg-[#131A29] px-4 text-center text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white cursor-pointer">
                        Xóa @if($activeFilterCount > 0)({{ $activeFilterCount }})@endif
                    </button>
                </div>
            </div>

            @if($datePreset === 'custom' || $dateFrom !== '' || $dateTo !== '')
                <div class="grid grid-cols-1 gap-3.5 border-t border-white/[0.06] pt-3.5 sm:grid-cols-2 lg:max-w-lg">
                    <x-admin.form-field name="dateFrom" label="Từ ngày">
                        <input id="dateFrom" type="date" name="date_from" wire:model.live="dateFrom" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    </x-admin.form-field>
                    <x-admin.form-field name="dateTo" label="Đến ngày">
                        <input id="dateTo" type="date" name="date_to" wire:model.live="dateTo" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    </x-admin.form-field>
                </div>
            @endif
        </form>
    </x-admin.card>

    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
        <div class="overflow-x-auto lg:overflow-x-visible" wire:loading.class="opacity-60">
            <table class="w-full lg:table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/60">
                        <th class="w-[17%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Mã kép &amp; Thời điểm</th>
                        <th class="w-[23%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Khách hàng &amp; Sales</th>
                        <th class="w-[26%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Nhu cầu</th>
                        <th class="w-[14%] px-5 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                        <th class="w-[20%] px-5 py-3.5 text-right text-xs font-semibold text-zinc-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($appointments as $appointment)
                        @php
                            $customerName = $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai';
                            $customerEmail = $appointment->user?->email ?? $appointment->guest_email;
                            $hasAiChat = (bool) ($appointment->ai_conversation_id || filled($appointment->ai_visitor_id) || filled($appointment->ai_chat_summary));
                            $isHotLead = $appointment->lead_priority === \App\Models\Appointment::LEAD_PRIORITY_HOT;
                        @endphp
                        <tr wire:key="admin-appointment-{{ $appointment->id }}" class="group align-middle transition-colors hover:bg-white/[0.03]">
                            <td class="whitespace-nowrap px-5 py-4">
                                @if($appointment->customer_order_code)
                                    <p class="text-xs font-bold tabular-nums text-[#70A7FF]">{{ $appointment->customer_order_code }}</p>
                                @endif
                                @if($appointment->daily_sequence_code)
                                    <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-zinc-300">{{ $appointment->daily_sequence_code }}</p>
                                @endif
                                @if($appointment->customer_internal_code)
                                    <p class="mt-0.5 text-[10px] font-medium tabular-nums text-zinc-400">{{ $appointment->customer_internal_code }}</p>
                                @endif
                                <p class="mt-1 text-xs font-semibold tabular-nums text-white">{{ $appointment->appointment_date->format('d/m/Y · H:i') }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $customerName }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-300">{{ $appointment->guest_phone ?: ($customerEmail ?: 'Chưa có liên hệ') }}</p>
                                @if(filled($appointment->guest_phone) && filled($customerEmail))
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $customerEmail }}</p>
                                @endif
                                <p class="mt-1.5">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ filled($appointment->assigned_sales_name) ? 'border-sky-500/25 bg-sky-500/10 text-sky-300' : 'border-white/10 bg-white/[0.04] text-zinc-400' }}">
                                        {{ filled($appointment->assigned_sales_name) ? 'Sales: ' . $appointment->assigned_sales_name : 'Chưa gán Sales' }}
                                    </span>
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="truncate text-xs font-semibold text-white">
                                        {{ $appointment->product?->name ?? ($appointment->servicePackageLabel() ?: 'Tư vấn tại Showroom') }}
                                    </span>
                                    @if($isHotLead)
                                        <span class="shrink-0 whitespace-nowrap rounded-full border border-rose-500/25 bg-rose-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-rose-300">
                                            HOT LEAD
                                        </span>
                                    @else
                                        <span class="shrink-0 whitespace-nowrap rounded-full border border-[#1C69D4]/25 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[10px] font-semibold text-[#70A7FF]">
                                            {{ $appointment->type->label() }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1.5 flex items-center gap-2 text-[11px]">
                                    @if($appointment->confirmation_email_sent_at)
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider text-emerald-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            <span>ĐÃ GỬI MAIL XÁC NHẬN</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider text-zinc-500">
                                            <span class="h-1.5 w-1.5 rounded-full bg-zinc-600"></span>
                                            <span>CHƯA GỬI MAIL</span>
                                        </span>
                                    @endif

                                    @if($hasAiChat)
                                        <span class="text-zinc-700">·</span>
                                        <span class="rounded-full bg-sky-500/15 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-sky-300">ĐÃ CHAT AI</span>
                                    @endif
                                </div>

                                @if(filled($appointment->ai_lead_note))
                                    <p class="mt-1 truncate text-[11px] text-zinc-400" title="{{ $appointment->ai_lead_note }}">
                                        {{ $appointment->ai_lead_note }}
                                    </p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if($appointment->isTerminalStatus())
                                        <span class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-[#070A10] px-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                            {{ $appointment->status === \App\Enums\AppointmentStatus::Completed ? 'Đã đóng · Hoàn tất' : 'Đã đóng · Đã hủy' }}
                                        </span>
                                    @else
                                        <div class="w-36">
                                            <x-ui.custom-select
                                                compact
                                                aria-label="Cập nhật trạng thái lịch hẹn #{{ $appointment->id }}"
                                                :value="$appointment->status->value"
                                                :options="$appointment->allowedStatusOptions()"
                                                wire-action="updateStatus"
                                                :wire-record-id="$appointment->id"
                                            />
                                        </div>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="inspectAppointment({{ $appointment->id }})"
                                        data-testid="inspect-appointment-{{ $appointment->id }}"
                                        title="Xem hồ sơ & tác vụ{{ $hasAiChat ? ' · Xem lịch sử chat AI' : '' }}"
                                        aria-label="Xem hồ sơ & tác vụ lịch hẹn #{{ $appointment->id }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/20 hover:text-white cursor-pointer"
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
                                    description="Chưa có yêu cầu đặt lịch nào khớp bộ lọc hiện tại."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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
            $officialSentAt = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($lead->meta_data)['official_confirmation_sent_at'] ?? null;
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
                        class="fixed inset-0 z-[100] bg-black/75 backdrop-blur-sm"
                        @click="close()"
                    ></div>

                    <div class="fixed inset-y-0 right-0 z-[101] flex h-dvh w-full max-w-xl flex-col overflow-hidden rounded-l-2xl border-l border-white/10 bg-[#0B101B] text-white shadow-2xl shadow-black/70">
                        <!-- Header cố định (shrink-0) -->
                        <header class="shrink-0 flex items-start justify-between gap-4 border-b border-white/[0.08] bg-[#0D121C] px-6 py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/15 px-3 py-0.5 text-[10px] font-bold tracking-wider text-[#70A7FF]">
                                        HỒ SƠ LỊCH HẸN #{{ $lead->id }}
                                    </span>
                                    @if($lead->customer_order_code)
                                        <span class="rounded-full border border-sky-500/30 bg-sky-500/15 px-3 py-0.5 text-[11px] font-bold tabular-nums text-sky-300">
                                            {{ $lead->customer_order_code }}
                                        </span>
                                    @endif
                                    @if($lead->daily_sequence_code)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-3 py-0.5 text-[11px] font-semibold tabular-nums text-zinc-200">
                                            {{ $lead->daily_sequence_code }}
                                        </span>
                                    @endif
                                    @if($lead->customer_internal_code)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-3 py-0.5 text-[11px] font-semibold tabular-nums text-zinc-300">
                                            {{ $lead->customer_internal_code }}
                                        </span>
                                    @endif
                                    @if($lead->lead_priority)
                                        <span class="rounded-full border px-3 py-0.5 text-[11px] font-semibold {{ $lead->leadPriorityColorClass() }}">
                                            {{ $lead->leadPriorityLabel() }}
                                        </span>
                                    @endif
                                    <span class="rounded-full border px-3 py-0.5 text-[11px] font-semibold {{ $lead->status->color() }}">
                                        {{ $lead->status->label() }}
                                    </span>
                                </div>
                                <h3 class="mt-2.5 truncate text-lg font-bold text-white">
                                    {{ $leadName }}
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-400">
                                    {{ $lead->product?->name ?? ($lead->servicePackageLabel() ?: 'Tư vấn chung tại Showroom') }} · {{ $lead->type->label() }} · {{ $lead->appointment_date?->format('H:i — d/m/Y') }}
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="close()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] text-lg font-bold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                aria-label="Đóng hồ sơ lịch hẹn"
                            >
                                ×
                            </button>
                        </header>

                        <!-- Thân cuộn độc lập (min-h-0 flex-1 overflow-y-auto overscroll-contain) -->
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 space-y-5">
                            <!-- Khối 1: Tóm tắt khách hàng & Ghi chú AI -->
                            <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                <p class="text-xs font-semibold text-[#38BDF8]">1. Tóm tắt khách hàng &amp; Ghi chú AI</p>
                                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 text-xs">
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Số điện thoại</p>
                                        <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="font-bold tabular-nums text-white">{{ $lead->guest_phone ?: 'Chưa cung cấp' }}</span>
                                            @if(filled($lead->guest_phone))
                                                <a href="tel:{{ $lead->guest_phone }}" class="rounded-lg border border-emerald-500/30 bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300 hover:bg-emerald-500/25">Gọi</a>
                                                <button
                                                    type="button"
                                                    @click="navigator.clipboard?.writeText('{{ $lead->guest_phone }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                    class="rounded-lg border border-white/10 bg-[#131A29] px-2.5 py-0.5 text-[11px] font-semibold text-zinc-300 hover:border-[#1C69D4] cursor-pointer"
                                                    x-text="copied ? 'Đã copy' : 'Copy'"
                                                ></button>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Email liên hệ</p>
                                        <p class="mt-1 truncate font-semibold text-zinc-200">{{ $leadEmail ?: 'Chưa cung cấp' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Nhu cầu &amp; Mẫu xe</p>
                                        <p class="mt-1 font-semibold text-white">{{ $lead->product?->name ?? ($lead->servicePackageLabel() ?: 'Tư vấn trực tiếp') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Nguồn &amp; Ý định Lead</p>
                                        <p class="mt-1 font-semibold text-zinc-300">{{ $lead->leadIntentLabel() }}</p>
                                    </div>
                                    <div class="sm:col-span-2 border-t border-white/[0.06] pt-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Sales phụ trách</p>
                                        @if(auth()->user()?->canManageSystem())
                                            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                                <select
                                                    aria-label="Phân công Sales cho lịch hẹn #{{ $lead->id }}"
                                                    wire:change="assignToSales({{ $lead->id }}, $event.target.value)"
                                                    class="h-9 flex-1 min-w-[180px] rounded-lg border border-white/15 bg-[#070A10] px-3 text-xs font-semibold text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                                >
                                                    <option value="">-- Chưa phân công --</option>
                                                    @foreach($salesRoster as $salesMember)
                                                        <option value="{{ $salesMember->id }}" @selected((int) $lead->assigned_to === (int) $salesMember->id)>
                                                            {{ $salesMember->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <p class="mt-1 font-semibold text-sky-300">{{ $lead->assignedSales?->name ?? 'Chưa phân công' }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if(filled($lead->notes))
                                    <div class="border-t border-white/[0.07] pt-3 text-xs text-zinc-300">
                                        <span class="font-semibold text-zinc-400">Ghi chú khách hàng:</span> {{ $lead->notes }}
                                    </div>
                                @endif

                                @if(filled($lead->ai_lead_note) || filled($lead->ai_chat_summary))
                                    <div class="border-t border-white/[0.07] pt-3 space-y-1.5 text-xs">
                                        @if(filled($lead->ai_lead_note))
                                            <p class="text-zinc-300"><span class="font-semibold text-[#38BDF8]">Nhận định AI:</span> {{ $lead->ai_lead_note }}</p>
                                        @endif
                                        @if(filled($lead->ai_chat_summary))
                                            <p class="text-zinc-400"><span class="font-semibold text-zinc-300">Tóm tắt hội thoại AI:</span> {{ $lead->ai_chat_summary }}</p>
                                        @endif
                                    </div>
                                @endif
                            </section>

                            <!-- Khối 2: Trạng thái Email & Hậu mãi -->
                            <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                <p class="text-xs font-semibold text-[#38BDF8]">2. Trạng thái Email &amp; Hậu mãi</p>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 text-xs">
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Thư tiếp nhận hệ thống</p>
                                        <p class="mt-1 font-semibold {{ $lead->confirmation_email_sent_at ? 'text-emerald-300' : 'text-zinc-500' }}">
                                            {{ $lead->confirmation_email_sent_at ? $lead->confirmation_email_sent_at->format('H:i · d/m/Y') : 'Chưa ghi nhận' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Thư xác nhận chính thức</p>
                                        <p class="mt-1 font-semibold {{ $officialSentAt ? 'text-emerald-300' : 'text-zinc-500' }}">
                                            {{ $officialSentAt ? \Illuminate\Support\Carbon::parse($officialSentAt)->format('H:i · d/m/Y') : 'Chưa gửi từ Showroom' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Thư nhắc lịch 24h</p>
                                        <p class="mt-1 font-semibold {{ $lead->reminder_sent_at ? 'text-indigo-300' : 'text-zinc-500' }}">
                                            {{ $lead->reminder_sent_at ? $lead->reminder_sent_at->format('H:i · d/m/Y') : 'Đang chờ đến khung 24h' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Thư hậu mãi (sau 2h)</p>
                                        <p class="mt-1 font-semibold {{ $lead->followup_email_sent_at ? 'text-amber-300' : 'text-zinc-500' }}">
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

                            <!-- Khối 4: Thao tác xử lý hồ sơ theo State Machine -->
                            <x-admin.crm-action-hub
                                context-key="appointment"
                                :record-id="$lead->id"
                                :status-value="$lead->status->value"
                                :status-label="$lead->status->label()"
                                confirm-label="Duyệt & Xác nhận"
                                confirm-hint="Gửi email xác nhận & VIP Pass"
                                complete-label="Hoàn tất"
                                complete-hint="Hoàn tất đón tiếp & hẹn thư hậu mãi 2h"
                                cancel-label="Hủy hồ sơ"
                                completed-message="Lịch hẹn đã hoàn tất đón tiếp — Quy trình xử lý đã đóng và khóa chuyển trạng thái."
                                cancelled-message="Lịch hẹn đã bị hủy — Không thể khôi phục hoặc duyệt lại."
                                :error-message="$errorMessage"
                            >
                                <x-slot name="extraLinks">
                                    @if($chatSession)
                                        <a
                                            href="{{ route('admin.ai-conversations.show', $chatSession) }}"
                                            class="flex h-10 w-full items-center justify-center rounded-xl bg-[#1C69D4] px-4 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0]"
                                        >
                                            Mở lịch sử Chat AI đầy đủ ({{ $chatSession->messages->count() }} tin nhắn)
                                        </a>
                                    @endif
                                </x-slot>
                            </x-admin.crm-action-hub>

                            <!-- Khối 5: Xem trước mẫu Email (Chỉ xem — Không gửi thư, thu gọn mặc định) -->
                            @php
                                $appointmentPreviewItems = [
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'system_received']),
                                        'label' => 'Xem thư Hệ thống tiếp nhận',
                                        'toneClass' => 'border-[#1C69D4]/30 text-[#70A7FF] hover:border-[#1C69D4] hover:bg-[#1C69D4]/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'admin_confirmed']),
                                        'label' => 'Xem thư Admin duyệt VIP',
                                        'toneClass' => 'border-emerald-500/30 text-emerald-300 hover:border-emerald-400 hover:bg-emerald-500/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'guest_rescheduled']),
                                        'label' => 'Xem thư Khách đổi lịch',
                                        'toneClass' => 'border-amber-500/30 text-amber-300 hover:border-amber-400 hover:bg-amber-500/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'reminder']),
                                        'label' => 'Xem thư Nhắc lịch 24h',
                                        'toneClass' => 'border-indigo-500/30 text-indigo-300 hover:border-indigo-400 hover:bg-indigo-500/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'followup']),
                                        'label' => 'Xem thư Hậu mãi trả góp',
                                        'toneClass' => 'border-amber-600/35 text-amber-200 hover:border-amber-500 hover:bg-amber-600/15',
                                        'spanClass' => 'sm:col-span-2',
                                    ],
                                ];
                            @endphp
                            <x-admin.mail-preview-accordion
                                :key-id="'appointment-'.$lead->id"
                                :items="$appointmentPreviewItems"
                            />

                            <!-- Timeline Chat AI (nếu có) -->
                            @if($chatSession && $chatSession->messages->isNotEmpty())
                                @php
                                    $latestStructuredMeta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray(
                                        $chatSession->messages
                                            ->reverse()
                                            ->first(fn ($msg) => ! $msg->isUserMessage() && \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($msg->meta_data) !== [])
                                            ?->meta_data
                                    );
                                    $drawerLeadLevel = (string) ($latestStructuredMeta['lead_level'] ?? '');
                                    $drawerLeadStyle = match ($drawerLeadLevel) {
                                        'hot' => 'border-rose-500/30 bg-rose-500/15 text-rose-300',
                                        'warm' => 'border-amber-500/30 bg-amber-500/15 text-amber-300',
                                        default => 'border-white/10 bg-white/5 text-zinc-300',
                                    };
                                    $drawerMetaProducts = is_array($latestStructuredMeta['recommended_product_ids'] ?? null)
                                        ? $latestStructuredMeta['recommended_product_ids']
                                        : [];
                                    $drawerMetaTools = is_array($latestStructuredMeta['tool_calls'] ?? null)
                                        ? $latestStructuredMeta['tool_calls']
                                        : [];
                                @endphp
                                <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                    <p class="text-xs font-semibold text-[#38BDF8]">BÀN GIAO NGỮ CẢNH AI · Hội thoại gần nhất với BMW AI</p>
                                    @if($latestStructuredMeta !== [])
                                        <div class="flex flex-wrap items-center gap-1.5 pb-1">
                                            @if(filled($latestStructuredMeta['intent'] ?? null))
                                                <span class="rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[10px] font-semibold text-[#70A7FF]">
                                                    INTENT: {{ $latestStructuredMeta['intent'] }}
                                                </span>
                                            @endif
                                            @if($drawerLeadLevel !== '')
                                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold {{ $drawerLeadStyle }}">
                                                    LEAD: {{ strtoupper($drawerLeadLevel) }}
                                                </span>
                                            @endif
                                            @if($drawerMetaProducts !== [])
                                                <span class="rounded-full border border-emerald-500/25 bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-300">
                                                    #{{ implode(', #', $drawerMetaProducts) }}
                                                </span>
                                            @endif
                                            @if(filled($latestStructuredMeta['recommended_service_package'] ?? null))
                                                <span class="rounded-full border border-sky-500/25 bg-sky-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-sky-300">
                                                    {{ $latestStructuredMeta['recommended_service_package'] }}
                                                </span>
                                            @endif
                                            @foreach($drawerMetaTools as $toolName)
                                                <span class="rounded-full border border-violet-500/25 bg-violet-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-violet-300">
                                                    TOOL: {{ $toolName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="space-y-2.5">
                                        @foreach($chatSession->messages->take(-6) as $chatMessage)
                                            <div
                                                wire:key="crm-chat-msg-{{ $chatMessage->id }}"
                                                class="rounded-xl border px-4 py-3 text-xs {{ $chatMessage->isUserMessage() ? 'border-[#1C69D4]/30 bg-[#1C69D4]/15 text-white' : 'border-white/[0.07] bg-[#0D121C] text-zinc-300' }}"
                                            >
                                                <p class="mb-1 text-[10px] font-semibold {{ $chatMessage->isUserMessage() ? 'text-[#70A7FF]' : 'text-zinc-400' }}">
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
                        <footer class="shrink-0 flex items-center justify-between gap-3 border-t border-white/[0.08] bg-[#0D121C] px-6 py-4">
                            <span class="text-xs text-zinc-500">Nhấn Esc hoặc click vùng tối để đóng</span>
                            <button
                                type="button"
                                @click="close()"
                                class="h-9 rounded-xl border border-white/10 bg-[#131A29] px-5 text-xs font-semibold text-zinc-200 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4] hover:text-white cursor-pointer"
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
