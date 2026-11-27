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
    $salesAssignOptions = ['' => '-- Chưa phân công --'];
    foreach ($salesRoster as $salesMember) {
        $salesFilterOptions[(string) $salesMember->id] = $salesMember->name;
        $salesAssignOptions[(string) $salesMember->id] = $salesMember->name;
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

<div
    x-data="bmwSafeCrmPoll($wire, () => Boolean($wire.inspectedAppointmentId || $wire.completionTargetId || $wire.pendingArchiveAction))"
    @keydown.escape.window="$wire.closeAiConversationModal()"
>
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
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                <div class="sm:col-span-2 lg:col-span-2">
                    <x-admin.form-field name="search" label="Tìm kiếm mã kép / khách / xe">
                        <input
                            id="search"
                            type="text"
                            name="search"
                            wire:model.live.debounce.350ms="search"
                            placeholder="Mã BMW/CT/KH, tên, SĐT, xe..."
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-2 focus:ring-[#1C69D4]/40"
                        >
                    </x-admin.form-field>
                </div>

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

                <div class="flex items-center gap-2 lg:justify-end">
                    <button type="submit" class="h-10 flex-1 sm:flex-initial whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer">
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

    <x-admin.crm-archive-bar :archive-scope="$archiveScope" :archived-count="$quickStats['archived'] ?? 0" />

    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
        <div class="overflow-x-auto lg:overflow-x-visible" wire:loading.class="opacity-60">
            <table class="w-full lg:table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/60">
                        <th class="w-[16%] px-4 py-3.5 text-xs font-semibold text-zinc-400">Mã kép &amp; Thời điểm</th>
                        <th class="w-[21%] px-4 py-3.5 text-xs font-semibold text-zinc-400">Khách hàng &amp; Sales</th>
                        <th class="w-[22%] px-4 py-3.5 text-xs font-semibold text-zinc-400">Nhu cầu</th>
                        <th class="w-[17%] px-4 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                        <th class="w-[24%] px-4 py-3.5 text-right text-xs font-semibold text-zinc-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($appointments as $appointment)
                        @php
                            $customerName = $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai';
                            $customerEmail = $appointment->user?->email ?? $appointment->guest_email;
                            $rowMeta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
                            $hasPendingReschedule = $appointment->hasPendingRescheduleReview();
                            $rescheduleCount = $appointment->rescheduleCount();
                            $prevStatusLabel = $appointment->previousRescheduleStatusLabel();
                            $guestAttendanceConfirmed = $appointment->hasGuestAttendanceConfirmed();
                            $cancelAttribution = $appointment->cancellationAttributionLabel();
                            $rowCompletionOutcome = $appointment->completionOutcome();
                            $careVoucherCode = $appointment->careVoucherCode();
                            $isTerminalRow = $appointment->isTerminalStatus();
                            $terminalCellDim = $isTerminalRow ? 'opacity-55 transition-opacity group-hover:opacity-75' : '';
                            $hasAiChat = (bool) ($appointment->ai_conversation_id || filled($appointment->ai_visitor_id) || filled($appointment->ai_chat_summary));
                            $isHotLead = $appointment->lead_priority === \App\Models\Appointment::LEAD_PRIORITY_HOT;
                        @endphp
                        <tr wire:key="admin-appointment-{{ $appointment->id }}" class="group align-middle transition-colors hover:bg-white/[0.03]" data-terminal-row="{{ $isTerminalRow ? 'true' : 'false' }}">
                            <td class="whitespace-nowrap px-4 py-4 {{ $terminalCellDim }}">
                                @if($appointment->customer_order_code)
                                    <p class="text-xs font-bold tabular-nums text-[#70A7FF]">{{ $appointment->customer_order_code }}</p>
                                @endif
                                @if($appointment->daily_sequence_code)
                                    <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-zinc-300">{{ $appointment->daily_sequence_code }}</p>
                                @endif
                                @if($appointment->customer_internal_code)
                                    <p class="mt-0.5 text-[10px] font-medium tabular-nums text-zinc-400">{{ $appointment->customer_internal_code }}</p>
                                @endif
                                @php
                                    $prevRowDate = is_string($rowMeta['previous_appointment_date'] ?? null) ? trim($rowMeta['previous_appointment_date']) : '';
                                @endphp
                                @if($hasPendingReschedule && $prevRowDate !== '')
                                    <p class="mt-1 text-[11px] font-medium tabular-nums text-zinc-400 opacity-50 line-through" title="Lịch cũ trước khi khách dời">{{ $prevRowDate }}</p>
                                    <p class="mt-0.5 text-xs font-bold tabular-nums text-amber-300" title="Lịch mới khách đề xuất">{{ $appointment->appointment_date->format('d/m/Y · H:i') }}</p>
                                @else
                                    <p class="mt-1 text-xs font-semibold tabular-nums text-white">{{ $appointment->appointment_date->format('d/m/Y · H:i') }}</p>
                                @endif
                            </td>

                            <td class="px-5 py-4 {{ $terminalCellDim }}">
                                <p class="truncate text-sm font-bold text-white">{{ $customerName }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-300">{{ $appointment->guest_phone ?: ($customerEmail ?: 'Chưa có liên hệ') }}</p>
                                @if(filled($appointment->guest_phone) && filled($customerEmail))
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $customerEmail }}</p>
                                @endif
                                <p class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ filled($appointment->assigned_sales_name) ? 'border-sky-500/25 bg-sky-500/10 text-sky-300' : 'border-white/10 bg-white/[0.04] text-zinc-400' }}">
                                        {{ filled($appointment->assigned_sales_name) ? 'Sales: ' . $appointment->assigned_sales_name : 'Chưa gán Sales' }}
                                    </span>
                                    @if($hasPendingReschedule)
                                        <span class="inline-flex items-center rounded-full border border-amber-400/40 bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-200">
                                            {{ $rescheduleCount >= 2 ? "Chờ duyệt dời lịch lần {$rescheduleCount}" : 'Khách vừa đổi lịch' }}
                                        </span>
                                    @endif
                                    @if($guestAttendanceConfirmed && ! $isTerminalRow)
                                        <span class="inline-flex items-center rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300">
                                            Khách đã xác nhận tham dự
                                        </span>
                                    @endif
                                    @if($appointment->isArchived())
                                        <span class="inline-flex items-center rounded-full border border-zinc-500/40 bg-white/5 px-2 py-0.5 text-[10px] font-semibold text-zinc-300">
                                            Đã lưu trữ
                                        </span>
                                    @endif
                                </p>
                            </td>

                            <td class="px-4 py-4 {{ $terminalCellDim }}">
                                <p class="truncate text-xs font-semibold text-white" title="{{ $appointment->product?->name ?? ($appointment->servicePackageLabel() ?: 'Tư vấn tại Showroom') }}">
                                    {{ $appointment->product?->name ?? ($appointment->servicePackageLabel() ?: 'Tư vấn tại Showroom') }}
                                </p>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    @if($isHotLead)
                                        <span class="shrink-0 whitespace-nowrap rounded-full border border-rose-500/25 bg-rose-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-rose-300">
                                            HOT LEAD
                                        </span>
                                    @else
                                        <span class="shrink-0 whitespace-nowrap rounded-full border border-[#1C69D4]/25 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[10px] font-semibold text-[#70A7FF]">
                                            {{ $appointment->type->label() }}
                                        </span>
                                    @endif

                                    @if($hasAiChat)
                                        <span class="shrink-0 whitespace-nowrap rounded-full bg-sky-500/15 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-sky-300">ĐÃ CHAT AI</span>
                                    @endif
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[11px]">
                                    @if($appointment->confirmation_email_sent_at || filled($rowMeta['official_confirmation_sent_at'] ?? null))
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[10px] font-bold tracking-wider text-emerald-300">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400"></span>
                                            <span>ĐÃ GỬI MAIL XÁC NHẬN</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[10px] font-bold tracking-wider text-zinc-500">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-600"></span>
                                            <span>CHƯA GỬI MAIL</span>
                                        </span>
                                    @endif
                                </div>

                                @if(filled($appointment->ai_lead_note))
                                    <p class="mt-1 truncate text-[11px] text-zinc-400" title="{{ $appointment->ai_lead_note }}">
                                        {{ $appointment->ai_lead_note }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-middle {{ $terminalCellDim }}">
                                <div class="mx-auto flex min-w-[140px] max-w-[180px] flex-col items-center gap-1.5 text-center">
                                    @if($hasPendingReschedule && filled($prevStatusLabel) && $prevStatusLabel !== $appointment->status->label())
                                        <span class="text-[10px] font-medium text-zinc-400 opacity-50 line-through" title="Trạng thái trước khi dời lịch">
                                            {{ $prevStatusLabel }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold {{ $appointment->status->color() }}">
                                        {{ $appointment->status->label() }}
                                    </span>
                                    @if($rowCompletionOutcome === \App\Models\Appointment::OUTCOME_PURCHASED)
                                        <span class="inline-flex max-w-full items-center rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300">
                                            Đã mua xe
                                        </span>
                                        @if($careVoucherCode)
                                            <span
                                                title="Mã đặc quyền BMW Care: {{ $careVoucherCode }}"
                                                class="block max-w-full truncate rounded-md border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-semibold tabular-nums text-emerald-200"
                                            >
                                                {{ $careVoucherCode }}
                                            </span>
                                        @endif
                                    @elseif($rowCompletionOutcome === \App\Models\Appointment::OUTCOME_VISITED_NO_PURCHASE)
                                        <span class="inline-flex max-w-full items-center rounded-full border border-white/15 bg-white/[0.05] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
                                            Chưa mua xe
                                        </span>
                                    @elseif($rowOutcomeLabel = $appointment->completionOutcomeLabel())
                                        <span class="inline-flex max-w-full items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-300">
                                            {{ $rowOutcomeLabel }}
                                        </span>
                                    @endif
                                    @if($completedByText = $appointment->completedAttributionLabel())
                                        <p class="max-w-full break-words text-[10px] font-medium leading-snug text-emerald-300/90" title="{{ $completedByText }}">
                                            {{ $completedByText }}
                                        </p>
                                    @endif
                                    @if($cancelAttribution)
                                        <p class="max-w-full break-words text-[10px] font-medium leading-snug text-rose-300" title="{{ $cancelAttribution }}">
                                            {{ $cancelAttribution }}
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <td class="shrink-0 whitespace-nowrap px-4 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if($appointment->isTerminalStatus())
                                        <span class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-[#070A10] px-2.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                            {{ $appointment->status === \App\Enums\AppointmentStatus::Completed ? 'Đã đóng · Hoàn tất' : 'Đã đóng · Đã hủy' }}
                                        </span>
                                    @else
                                        <div class="w-32 {{ $hasPendingReschedule ? 'opacity-50' : '' }}" title="{{ $hasPendingReschedule ? 'Mở hồ sơ để xác nhận lịch mới trước khi đổi trạng thái' : '' }}">
                                            <x-ui.custom-select
                                                compact
                                                aria-label="Cập nhật trạng thái lịch hẹn #{{ $appointment->id }}"
                                                :value="$appointment->status->value"
                                                :options="$appointment->allowedStatusOptions()"
                                                wire-action="handleInlineStatusChange"
                                                :wire-record-id="$appointment->id"
                                                :disabled="$hasPendingReschedule"
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
                                @php
                                    $leadMetaHeader = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($lead->meta_data);
                                    $leadHasPendingReschedule = $lead->hasPendingRescheduleReview();
                                    $leadPrevDate = is_string($leadMetaHeader['previous_appointment_date'] ?? null) ? trim($leadMetaHeader['previous_appointment_date']) : '';
                                @endphp
                                <p class="mt-0.5 text-xs text-zinc-400">
                                    {{ $lead->product?->name ?? ($lead->servicePackageLabel() ?: 'Tư vấn chung tại Showroom') }} · {{ $lead->type->label() }} ·
                                    @if($leadHasPendingReschedule && $leadPrevDate !== '')
                                        <span class="opacity-50 line-through text-zinc-400 tabular-nums">{{ $leadPrevDate }}</span>
                                        <span class="mx-1 text-amber-300">→</span>
                                        <strong class="text-amber-300 tabular-nums">{{ $lead->appointment_date?->format('H:i — d/m/Y') }}</strong>
                                    @else
                                        {{ $lead->appointment_date?->format('H:i — d/m/Y') }}
                                    @endif
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
                            <x-admin.crm-reschedule-alert-card
                                :record="$lead"
                                acknowledge-action="acknowledgeGuestReschedule"
                            />

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
                                            <div class="mt-1.5">
                                                <x-ui.custom-select
                                                    compact
                                                    aria-label="Phân công Sales cho lịch hẹn #{{ $lead->id }}"
                                                    :value="$lead->assigned_to ? (string) $lead->assigned_to : ''"
                                                    :options="$salesAssignOptions"
                                                    wire-action="assignToSales"
                                                    :wire-record-id="$lead->id"
                                                    :disabled="$lead->status === \App\Enums\AppointmentStatus::Cancelled"
                                                />
                                            </div>
                                        @else
                                            <p class="mt-1 font-semibold text-sky-300">{{ $lead->assignedSales?->name ?? 'Chưa phân công' }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if($leadOutcomeLabel = $lead->completionOutcomeLabel())
                                    <div class="border-t border-white/[0.07] pt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <span class="font-semibold text-zinc-400">Kết quả đón tiếp tại Showroom:</span>
                                        @if($lead->completionOutcome() === \App\Models\Appointment::OUTCOME_PURCHASED)
                                            <span class="inline-flex items-center rounded-full border border-emerald-500/35 bg-emerald-500/15 px-3 py-0.5 text-[11px] font-bold text-emerald-300">
                                                {{ $leadOutcomeLabel }}{{ $lead->careVoucherCode() ? ' · ' . $lead->careVoucherCode() . ' (hạn ' . $lead->careVoucherExpiresLabel() . ')' : '' }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/[0.05] px-3 py-0.5 text-[11px] font-semibold text-zinc-200">
                                                {{ $leadOutcomeLabel }} · Chỉ tham quan / lái thử
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                @if(filled($lead->notes))
                                    <div class="border-t border-white/[0.07] pt-3 text-xs text-zinc-300">
                                        <span class="font-semibold text-zinc-400">Ghi chú khách hàng:</span> {{ $lead->notes }}
                                    </div>
                                @endif

                                @if($leadCancelLabel = $lead->cancellationAttributionLabel())
                                    <div class="border-t border-white/[0.07] pt-3 text-xs text-rose-300">
                                        <span class="font-semibold text-rose-400">Nguồn hủy hồ sơ:</span> {{ $leadCancelLabel }}
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

                            <!-- Khối 3: Lịch sử Tương tác 2 chiều Khách hàng <-> Hệ thống (Gom nhóm theo chu kỳ) -->
                            @php
                                $leadMetaDebug = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($lead->meta_data);
                                $leadDebugPayload = [
                                    'reference_code' => $lead->publicReferenceCode(),
                                    'internal_voucher_code' => $lead->internalVoucherCode(),
                                    'status' => $lead->status->value,
                                    'status_label' => $lead->status->label(),
                                    'manage_version' => $lead->currentManageVersion(),
                                    'guest_confirmed_at' => $leadMetaDebug['guest_confirmed_at'] ?? null,
                                    'reschedule_pending_review' => (bool) ($leadMetaDebug['reschedule_pending_review'] ?? false),
                                    'reschedule_count' => $lead->rescheduleCount(),
                                    'previous_status' => $leadMetaDebug['previous_status'] ?? null,
                                    'completion_outcome' => $leadMetaDebug['completion_outcome'] ?? null,
                                    'payment_method' => $leadMetaDebug['payment_method'] ?? null,
                                    'final_vehicle_price' => $leadMetaDebug['final_vehicle_price'] ?? null,
                                    'timeline' => $timelineEvents,
                                ];
                            @endphp
                            <x-admin.crm-interaction-timeline
                                :events="$timelineEvents"
                                :debug-payload="$leadDebugPayload"
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
                                complete-hint="Ghi nhận kết quả thực tế & gửi thư cảm ơn"
                                :complete-action="'openCompletionModal(' . $lead->id . ')'"
                                cancel-label="Hủy hồ sơ"
                                :completed-message="($lead->completedAttributionLabel() ? $lead->completedAttributionLabel() . ' — ' : '') . 'Lịch hẹn đã hoàn tất đón tiếp và khóa chuyển trạng thái.'"
                                cancelled-message="Lịch hẹn đã bị hủy — Không thể khôi phục hoặc duyệt lại."
                                :reschedule-locked="$leadHasPendingReschedule"
                                :error-message="$errorMessage"
                            >
                                <x-slot name="extraLinks">
                                    @if($lead->status === \App\Enums\AppointmentStatus::Confirmed && ! $leadHasPendingReschedule && filled($leadEmail))
                                        <button
                                            type="button"
                                            wire:click="resendConfirmationEmail({{ $lead->id }})"
                                            wire:loading.attr="disabled"
                                            data-testid="resend-confirmation-appointment-{{ $lead->id }}"
                                            class="flex h-9 w-full items-center justify-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 text-xs font-semibold text-emerald-200 transition-colors hover:bg-emerald-500/20 cursor-pointer disabled:opacity-50"
                                        >
                                            Gửi lại thư xác nhận chính thức (Khóa chống lặp 30s)
                                        </button>
                                    @endif
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

                            <x-admin.crm-drawer-archive-panel :record="$lead" />

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
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'completed_purchased']),
                                        'label' => 'Xem thư Cảm ơn mua xe (Voucher 1T)',
                                        'toneClass' => 'border-emerald-500/35 text-emerald-200 hover:border-emerald-400 hover:bg-emerald-500/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $lead, 'mode' => 'completed_visited']),
                                        'label' => 'Xem thư Cảm ơn tham quan',
                                        'toneClass' => 'border-sky-500/30 text-sky-200 hover:border-sky-400 hover:bg-sky-500/15',
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

    @if($completionTargetId !== null)
        @php
            $allowedModalOutcomes = match ($completionTargetCategory) {
                'service' => [\App\Models\Appointment::OUTCOME_SERVICE_DELIVERED, \App\Models\Appointment::OUTCOME_SERVICE_INSPECTED_ADVISED],
                'trade_in' => [\App\Models\Appointment::OUTCOME_TRADE_IN_CLOSED, \App\Models\Appointment::OUTCOME_TRADE_IN_APPRAISED_ONLY],
                default => [\App\Models\Appointment::OUTCOME_PURCHASED, \App\Models\Appointment::OUTCOME_VISITED_NO_PURCHASE],
            };
            $hasValidCompletionOutcome = in_array($completionOutcome, $allowedModalOutcomes, true);
        @endphp
        <div
            wire:key="completion-outcome-modal-wrap-{{ $completionTargetId }}"
            x-data
            @keydown.escape.window="$wire.closeCompletionModal()"
        >
            <template x-teleport="body">
                <div
                    data-completion-outcome-modal="{{ $completionTargetId }}"
                    data-completion-category="{{ $completionTargetCategory }}"
                    class="fixed inset-0 z-[120] flex items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="completion-outcome-modal-title"
                >
                    <div
                        wire:click="closeCompletionModal"
                        class="absolute inset-0 z-0 bg-black/80 backdrop-blur-sm transition-opacity"
                    ></div>

                    <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-white/15 bg-[#0B0F17] p-6 text-white shadow-2xl">
                        @if($completionTargetCategory === 'service')
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-300">
                                Hoàn tất lịch Dịch vụ BMW · {{ $completionTargetReference }}
                            </p>
                            <h3 id="completion-outcome-modal-title" class="mt-1.5 text-lg font-bold text-white">
                                Biên bản hoàn tất Chăm sóc / Bảo dưỡng xe
                            </h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">
                                Xác nhận tình trạng bàn giao phương tiện để gửi thư biên bản dịch vụ chính hãng (không chứa nội dung mua xe mới).
                            </p>

                            <div class="mt-5 space-y-3" role="radiogroup" aria-label="Kết quả bàn giao dịch vụ">
                                <button
                                    type="button"
                                    data-outcome-option="service_delivered"
                                    wire:click="selectCompletionOutcome('service_delivered')"
                                    class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'service_delivered' ? 'border-emerald-400 bg-emerald-500/15 ring-1 ring-emerald-400/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25' }}"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'service_delivered' ? 'border-emerald-400 bg-emerald-500 text-slate-950' : 'border-zinc-500' }}">
                                        @if($completionOutcome === 'service_delivered')<span class="h-2 w-2 rounded-full bg-slate-950"></span>@endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white">Đã hoàn tất dịch vụ &amp; bàn giao xe</p>
                                        <p class="mt-1 text-xs text-zinc-300">Xe đã nghiệm thu kỹ thuật và bàn giao cho khách hàng tại Xưởng dịch vụ BMW.</p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    data-outcome-option="service_inspected_advised"
                                    wire:click="selectCompletionOutcome('service_inspected_advised')"
                                    class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'service_inspected_advised' ? 'border-[#38BDF8] bg-[#1C69D4]/20 ring-1 ring-[#38BDF8]/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25' }}"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'service_inspected_advised' ? 'border-[#70A7FF] bg-[#1C69D4]' : 'border-zinc-500' }}">
                                        @if($completionOutcome === 'service_inspected_advised')<span class="h-2 w-2 rounded-full bg-white"></span>@endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white">Đã kiểm tra &amp; tư vấn kỹ thuật thêm</p>
                                        <p class="mt-1 text-xs text-zinc-300">Đã chẩn đoán kỹ thuật và gửi khuyến nghị hạng mục cần theo dõi kỳ tới.</p>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300">Ghi chú kỹ thuật / Hạng mục thực hiện (tùy chọn)</label>
                                    <textarea wire:model="completionNote" rows="2" placeholder="Ví dụ: Thay dầu động cơ BMW TwinPower Turbo, kiểm tra phanh..." class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3 py-2 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"></textarea>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300">Chi phí thực tế (VNĐ, tùy chọn)</label>
                                        <input type="text" inputmode="numeric" wire:model="completionActualCostInput" placeholder="Ví dụ: 4.500.000" class="mt-1.5 h-9 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300">Nhắc bảo dưỡng kỳ tới</label>
                                        <select wire:model="completionReminderMonths" class="mt-1.5 h-9 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3 text-xs text-white focus:border-[#1C69D4] focus:outline-none">
                                            <option value="0">Không đặt lịch nhắc</option>
                                            <option value="3">Sau 3 tháng</option>
                                            <option value="6">Sau 6 tháng</option>
                                            <option value="12">Sau 12 tháng</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @elseif($completionTargetCategory === 'trade_in')
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#70A7FF]">
                                Hoàn tất Thẩm định BMW Certified Trade-In · {{ $completionTargetReference }}
                            </p>
                            <h3 id="completion-outcome-modal-title" class="mt-1.5 text-lg font-bold text-white">
                                Ghi nhận kết quả thẩm định Thu cũ đổi mới
                            </h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">
                                Chọn kết quả thẩm định phương tiện để gửi thư tổng kết chuyên biệt BMW Certified Trade-In.
                            </p>

                            <div class="mt-5 space-y-3" role="radiogroup" aria-label="Kết quả thẩm định xe">
                                <button
                                    type="button"
                                    data-outcome-option="trade_in_closed"
                                    wire:click="selectCompletionOutcome('trade_in_closed')"
                                    class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'trade_in_closed' ? 'border-emerald-400 bg-emerald-500/15 ring-1 ring-emerald-400/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25' }}"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'trade_in_closed' ? 'border-emerald-400 bg-emerald-500 text-slate-950' : 'border-zinc-500' }}">
                                        @if($completionOutcome === 'trade_in_closed')<span class="h-2 w-2 rounded-full bg-slate-950"></span>@endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white">Đã thẩm định &amp; chốt phương án thu mua</p>
                                        <p class="mt-1 text-xs text-zinc-300">Khách hàng đồng ý mức định giá thu mua / nâng cấp lên dòng xe BMW mới.</p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    data-outcome-option="trade_in_appraised_only"
                                    wire:click="selectCompletionOutcome('trade_in_appraised_only')"
                                    class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'trade_in_appraised_only' ? 'border-[#38BDF8] bg-[#1C69D4]/20 ring-1 ring-[#38BDF8]/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25' }}"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'trade_in_appraised_only' ? 'border-[#70A7FF] bg-[#1C69D4]' : 'border-zinc-500' }}">
                                        @if($completionOutcome === 'trade_in_appraised_only')<span class="h-2 w-2 rounded-full bg-white"></span>@endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-white">Đã thẩm định giá — Khách cân nhắc thêm</p>
                                        <p class="mt-1 text-xs text-zinc-300">Bảo lưu kết quả kiểm định kỹ thuật và gửi thư cảm ơn thẩm định.</p>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-4">
                                <label class="block text-xs font-semibold text-zinc-300">Ghi chú thẩm định / Định giá (tùy chọn)</label>
                                <textarea wire:model="completionNote" rows="2" placeholder="Ghi chú tình trạng xe hoặc mức định giá đề xuất..." class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3 py-2 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"></textarea>
                            </div>
                        @else
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#70A7FF]">
                                Hoàn tất lịch hẹn đón tiếp · {{ $completionTargetReference }}
                            </p>
                            <h3 id="completion-outcome-modal-title" class="mt-1.5 text-lg font-bold text-white">
                                Ghi nhận kết quả đón tiếp &amp; kịch bản Email hậu mãi
                            </h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">
                                Vui lòng chọn <strong class="text-white">1 trong 2 kết quả thực tế</strong> sau buổi đón tiếp tại Showroom để hệ thống phát hành đúng kịch bản thư hậu mãi.
                            </p>

                            <div class="mt-5 space-y-3" role="radiogroup" aria-label="Kết quả đón tiếp tại Showroom">
                                <button
                                    type="button"
                                    wire:key="completion-option-purchased-{{ $completionTargetId }}"
                                    data-outcome-option="purchased"
                                    role="radio"
                                    aria-checked="{{ $completionOutcome === 'purchased' ? 'true' : 'false' }}"
                                    wire:click="selectCompletionOutcome('purchased')"
                                    class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'purchased' ? 'border-emerald-400 bg-emerald-500/15 ring-1 ring-emerald-400/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25 hover:bg-[#111827]' }}"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'purchased' ? 'border-emerald-400 bg-emerald-500 text-slate-950' : 'border-zinc-500 bg-transparent' }}">
                                        @if($completionOutcome === 'purchased')
                                            <span class="h-2 w-2 rounded-full bg-slate-950"></span>
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-bold text-white">Khách đã mua xe / Ký hợp đồng</p>
                                            <span class="rounded-full border border-emerald-400/40 bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-bold text-emerald-200">
                                                Tặng Voucher BMW Care 1 tháng
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs leading-relaxed text-zinc-300">
                                            Tự động sinh mã đặc quyền <strong class="text-emerald-300">BMW-CARE-XXXXXX</strong> (rửa xe Detailing &amp; chăm sóc bề mặt miễn phí 01 tháng) và gửi thư chúc mừng sở hữu xe.
                                        </p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    wire:key="completion-option-visited-{{ $completionTargetId }}"
                                    data-outcome-option="visited_no_purchase"
                                    role="radio"
                                    aria-checked="{{ $completionOutcome === 'visited_no_purchase' ? 'true' : 'false' }}"
                                    wire:click="selectCompletionOutcome('visited_no_purchase')"
                                    class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'visited_no_purchase' ? 'border-[#38BDF8] bg-[#1C69D4]/20 ring-1 ring-[#38BDF8]/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25 hover:bg-[#111827]' }}"
                                >
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'visited_no_purchase' ? 'border-[#70A7FF] bg-[#1C69D4] text-white' : 'border-zinc-500 bg-transparent' }}">
                                        @if($completionOutcome === 'visited_no_purchase')
                                            <span class="h-2 w-2 rounded-full bg-white"></span>
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-bold text-white">Khách chỉ tham quan / Lái thử — Chưa mua</p>
                                            <span class="rounded-full border border-white/15 bg-white/[0.06] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
                                                Không kèm Voucher ưu đãi
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs leading-relaxed text-zinc-300">
                                            Gửi thư cảm ơn khách hàng đã dành thời gian ghé thăm Showroom và thông tin Chuyên viên phụ trách (tuyệt đối không đính kèm voucher chăm sóc/rửa xe).
                                        </p>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-4 rounded-xl border px-3.5 py-2.5 text-xs {{ $completionOutcome === 'purchased' ? 'border-emerald-500/35 bg-emerald-500/10 text-emerald-200' : ($completionOutcome === 'visited_no_purchase' ? 'border-[#1C69D4]/40 bg-[#1C69D4]/10 text-sky-200' : 'border-amber-500/30 bg-amber-500/10 text-amber-200') }}">
                                @if($completionOutcome === 'purchased')
                                    <span class="font-bold">Đang chọn:</span> Khách đã mua xe — Hệ thống sẽ phát hành mã <strong>BMW-CARE-XXXXXX</strong> &amp; gửi thư chúc mừng sở hữu BMW.
                                @elseif($completionOutcome === 'visited_no_purchase')
                                    <span class="font-bold">Đang chọn:</span> Khách tham quan / chưa mua — Hệ thống sẽ gửi thư cảm ơn ghé thăm Showroom (không kèm Voucher).
                                @else
                                    <span class="font-bold">Chưa chọn kết quả:</span> Vui lòng bấm chọn 1 trong 2 kịch bản ở trên để kích hoạt nút xác nhận.
                                @endif
                            </div>

                            @if($completionOutcome === 'purchased')
                                <div class="mt-4 space-y-4 rounded-xl border border-white/10 bg-[#0D121C] p-4" data-purchased-payment-panel>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300">Giá bán chốt thực tế (VNĐ) *</label>
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            wire:model.live="completionFinalPriceInput"
                                            x-on:input="$event.target.value = window.bmwFormatDots($event.target.value)"
                                            placeholder="Ví dụ: 2.750.000.000"
                                            class="mt-1.5 h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-bold tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                                        >
                                        <p class="mt-1 text-[11px] text-zinc-400">Dùng chung để Kế toán hạch toán doanh thu thực thu (điền sẵn từ giá niêm yết, có thể điều chỉnh theo giá chốt).</p>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-xs font-semibold text-zinc-300">Hình thức thanh toán của khách hàng</p>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <button
                                                type="button"
                                                data-payment-method-option="cash"
                                                wire:click="$set('completionPaymentMethod', 'cash')"
                                                class="rounded-xl border px-3.5 py-2.5 text-left text-xs font-semibold transition-colors cursor-pointer {{ $completionPaymentMethod !== 'installment' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-300 hover:border-white/25' }}"
                                            >
                                                <span class="block font-bold">Thanh toán 100% (Trả thẳng)</span>
                                                <span class="mt-0.5 block text-[10px] text-zinc-400">Không phát sinh hợp đồng trả góp</span>
                                            </button>
                                            <button
                                                type="button"
                                                data-payment-method-option="installment"
                                                wire:click="$set('completionPaymentMethod', 'installment')"
                                                class="rounded-xl border px-3.5 py-2.5 text-left text-xs font-semibold transition-colors cursor-pointer {{ $completionPaymentMethod === 'installment' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-300 hover:border-white/25' }}"
                                            >
                                                <span class="block font-bold">Mua xe trả góp qua ngân hàng</span>
                                                <span class="mt-0.5 block text-[10px] text-zinc-400">Tự động tạo hồ sơ nháp BMW Financial</span>
                                            </button>
                                        </div>
                                    </div>

                                    @if($completionPaymentMethod === 'installment')
                                        @php
                                            $previewFinalInt = (int) (preg_replace('/[^\d]/', '', $completionFinalPriceInput) ?? 0);
                                            $previewDownInt = (int) (preg_replace('/[^\d]/', '', $completionDownPaymentInput) ?? 0);
                                            $previewLoanInt = max(0, $previewFinalInt - $previewDownInt);
                                        @endphp
                                        <div class="space-y-3 border-t border-white/10 pt-3.5" data-installment-modal-fields>
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="block text-[11px] font-semibold text-zinc-300">Trả trước đối ứng (VNĐ)</label>
                                                    <input
                                                        type="text"
                                                        inputmode="numeric"
                                                        wire:model.live="completionDownPaymentInput"
                                                        x-on:input="$event.target.value = window.bmwFormatDots($event.target.value)"
                                                        placeholder="Ví dụ: 800.000.000"
                                                        class="mt-1 h-9 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs font-semibold tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                                                    >
                                                    <p class="mt-1 text-[10px] text-[#70A7FF] tabular-nums">
                                                        Khoản vay dự kiến: <strong>{{ number_format($previewLoanInt, 0, ',', '.') }} đ</strong>
                                                    </p>
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-semibold text-zinc-300">Kỳ hạn vay (tháng)</label>
                                                    <input
                                                        type="number"
                                                        wire:model="completionTermMonths"
                                                        min="6"
                                                        max="120"
                                                        class="no-number-spinner mt-1 h-9 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none"
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-semibold text-zinc-300">Ngân hàng giải ngân *</label>
                                                <input
                                                    type="text"
                                                    wire:model="completionBankName"
                                                    placeholder="Chọn nhanh bên dưới hoặc nhập tên ngân hàng..."
                                                    class="mt-1 h-9 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                                                >
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach(['Techcombank BMW Financial', 'VPBank', 'TPBank', 'VIB', 'Shinhan Bank', 'Vietcombank'] as $bankPreset)
                                                        <button
                                                            type="button"
                                                            wire:click="$set('completionBankName', '{{ $bankPreset }}')"
                                                            class="rounded-lg border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[10px] font-semibold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-white cursor-pointer"
                                                        >
                                                            {{ $bankPreset }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        @error('completionOutcome')
                            <p class="mt-2 text-xs font-semibold text-rose-400">{{ $message }}</p>
                        @enderror

                        <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-white/10 pt-4">
                            <button
                                type="button"
                                data-completion-modal-cancel
                                wire:click="closeCompletionModal"
                                wire:loading.attr="disabled"
                                class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white cursor-pointer"
                            >
                                Hủy bỏ
                            </button>
                            <button
                                type="button"
                                data-completion-modal-confirm
                                wire:click="confirmCompletion"
                                wire:loading.attr="disabled"
                                wire:target="confirmCompletion"
                                @disabled(! $hasValidCompletionOutcome)
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl px-5 text-xs font-bold text-white transition-colors {{ $hasValidCompletionOutcome ? 'bg-[#1C69D4] hover:bg-[#1557B0] cursor-pointer' : 'bg-zinc-700/70 text-zinc-400 opacity-60 cursor-not-allowed' }}"
                            >
                                <span wire:loading.remove wire:target="confirmCompletion">Xác nhận hoàn tất &amp; gửi thư</span>
                                <span wire:loading wire:target="confirmCompletion">Đang xử lý...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
