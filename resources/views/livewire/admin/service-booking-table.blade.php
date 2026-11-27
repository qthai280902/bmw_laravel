@php
    $statusFilterOptions = ['' => 'Tất cả trạng thái'];
    $statusActionOptions = [];
    foreach ($statuses as $statusOption) {
        $statusFilterOptions[$statusOption->value] = $statusOption->label();
        $statusActionOptions[$statusOption->value] = $statusOption->label();
    }

    $typeFilterOptions = ['' => 'Tất cả dịch vụ'];
    foreach ($serviceTypes as $typeOption) {
        $typeFilterOptions[$typeOption->value] = $typeOption->label();
    }

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

<div @keydown.escape.window="$wire.closeInspector()">
    <x-admin.page-header
        eyebrow="Aftersales operations"
        title="Dịch vụ"
        accent="Chăm sóc xe"
        description="Điều phối lịch bảo dưỡng định kỳ, phủ Ceramic, chăm sóc nội thất da Merino và chẩn đoán điện tử ISTA."
    >
        <x-slot name="metric">
            <p class="text-[11px] font-semibold text-zinc-400">Tổng lịch dịch vụ</p>
            <p class="mt-0.5 text-2xl font-extrabold tabular-nums text-white">{{ $bookings->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Contextual Quick-Filter Strip -->
    <div class="mb-6 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <button
            type="button"
            wire:click="applyQuickFilter('pending')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'pending' && ! $onlyToday ? 'border-amber-400/70 bg-amber-500/15 shadow-lg shadow-amber-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-amber-400/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-amber-300">Chờ xử lý</p>
                <p class="mt-1 text-[11px] text-zinc-400">Cần xác nhận khoang xe</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['pending'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('confirmed')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'confirmed' && ! $onlyToday ? 'border-[#1C69D4]/70 bg-[#1C69D4]/15 shadow-lg shadow-[#1C69D4]/15' : 'border-white/[0.08] bg-[#0D121C] hover:border-[#1C69D4]/50 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-[#70A7FF]">Đã xác nhận</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã giữ kỹ thuật viên</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['confirmed'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('today')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $onlyToday && $status === '' ? 'border-sky-400/70 bg-sky-500/15 shadow-lg shadow-sky-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-sky-400/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-sky-300">Hôm nay</p>
                <p class="mt-1 text-[11px] text-zinc-400">Xe vào xưởng trong ngày</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['today'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('completed')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'completed' && ! $onlyToday ? 'border-emerald-500/70 bg-emerald-500/15 shadow-lg shadow-emerald-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-emerald-500/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-emerald-300">Hoàn thành</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã bàn giao xe</p>
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
        <form action="{{ route('admin.service-bookings.index') }}" method="GET" wire:submit.prevent="$refresh" class="space-y-3.5">
            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 lg:grid-cols-[1.45fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
                <x-admin.form-field name="search" label="Tìm kiếm mã kép / khách / xe / gói dịch vụ">
                    <input
                        id="service-search"
                        type="text"
                        name="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Mã SRV/CT/KH, tên, SĐT, biển số..."
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-2 focus:ring-[#1C69D4]/40"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="service_type" label="Nhóm dịch vụ">
                    <x-ui.custom-select
                        id="service-type-filter"
                        name="service_type"
                        :value="$serviceType"
                        :options="$typeFilterOptions"
                        wire-model="serviceType"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="status" label="Trạng thái">
                    <x-ui.custom-select
                        id="service-status-filter"
                        name="status"
                        :value="$status"
                        :options="$statusFilterOptions"
                        wire-model="status"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="sales_id" label="Sales / Cố vấn">
                    <x-ui.custom-select
                        id="service-sales-filter"
                        name="sales_id"
                        :value="$salesId"
                        :options="$salesFilterOptions"
                        wire-model="salesId"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="date_preset" label="Khoảng ngày hẹn">
                    <x-ui.custom-select
                        id="service-date-preset"
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
                        <input id="serviceDateFrom" type="date" name="date_from" wire:model.live="dateFrom" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    </x-admin.form-field>
                    <x-admin.form-field name="dateTo" label="Đến ngày">
                        <input id="serviceDateTo" type="date" name="date_to" wire:model.live="dateTo" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
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
                        <th class="w-[22%] px-4 py-3.5 text-xs font-semibold text-zinc-400">Xe &amp; Gói dịch vụ</th>
                        <th class="w-[17%] px-4 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                        <th class="w-[24%] px-4 py-3.5 text-right text-xs font-semibold text-zinc-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($bookings as $booking)
                        @php
                            $carModel = $booking->product?->name
                                ?? ($booking->meta_data['customer_car_model'] ?? 'Xe BMW của khách');
                            $servicePackage = $booking->servicePackageLabel();
                            $customerEmail = $booking->user?->email ?? $booking->guest_email;
                            $rowMeta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($booking->meta_data);
                            $hasPendingReschedule = $booking->hasPendingRescheduleReview();
                            $cancelAttribution = $booking->cancellationAttributionLabel();
                            $isCancelledServiceRow = $booking->status === \App\Enums\AppointmentStatus::Cancelled;
                        @endphp
                        <tr wire:key="admin-service-booking-{{ $booking->id }}" class="group align-middle transition-colors hover:bg-white/[0.03] {{ $isCancelledServiceRow ? 'opacity-60 hover:opacity-85' : '' }}">
                            <td class="whitespace-nowrap px-5 py-4">
                                @if($booking->customer_order_code)
                                    <p class="text-xs font-bold tabular-nums text-[#70A7FF]">{{ $booking->customer_order_code }}</p>
                                @endif
                                @if($booking->daily_sequence_code)
                                    <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-zinc-300">{{ $booking->daily_sequence_code }}</p>
                                @endif
                                @if($booking->customer_internal_code)
                                    <p class="mt-0.5 text-[10px] font-medium tabular-nums text-zinc-400">{{ $booking->customer_internal_code }}</p>
                                @endif
                                @php
                                    $prevServiceDate = is_string($rowMeta['previous_appointment_date'] ?? null) ? trim($rowMeta['previous_appointment_date']) : '';
                                @endphp
                                @if($hasPendingReschedule && $prevServiceDate !== '')
                                    <p class="mt-1 text-[11px] font-medium tabular-nums text-zinc-400 opacity-50 line-through" title="Lịch cũ trước khi khách dời">{{ $prevServiceDate }}</p>
                                    <p class="mt-0.5 text-xs font-bold tabular-nums text-amber-300" title="Lịch mới khách đề xuất">{{ $booking->appointment_date->format('d/m/Y · H:i') }}</p>
                                @else
                                    <p class="mt-1 text-xs font-semibold tabular-nums text-white">{{ $booking->appointment_date->format('d/m/Y · H:i') }}</p>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-300">{{ $booking->guest_phone ?: ($customerEmail ?: 'Chưa có liên hệ') }}</p>
                                @if(filled($booking->guest_phone) && filled($customerEmail))
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $customerEmail }}</p>
                                @endif
                                <p class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ filled($booking->assigned_sales_name) ? 'border-sky-500/25 bg-sky-500/10 text-sky-300' : 'border-white/10 bg-white/[0.04] text-zinc-400' }}">
                                        {{ filled($booking->assigned_sales_name) ? 'Sales: ' . $booking->assigned_sales_name : 'Chưa gán Sales' }}
                                    </span>
                                    @if($hasPendingReschedule)
                                        <span class="inline-flex items-center rounded-full border border-amber-400/40 bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-200">
                                            Khách vừa đổi lịch
                                        </span>
                                    @endif
                                    @if($booking->isArchived())
                                        <span class="inline-flex items-center rounded-full border border-zinc-500/40 bg-white/5 px-2 py-0.5 text-[10px] font-semibold text-zinc-300">
                                            Đã lưu trữ
                                        </span>
                                    @endif
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="truncate text-xs font-semibold text-white">{{ $carModel }}</span>
                                    <span class="shrink-0 whitespace-nowrap rounded-full border border-[#1C69D4]/25 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[10px] font-semibold text-[#70A7FF]">
                                        {{ $booking->type->label() }}
                                    </span>
                                </div>
                                @if($servicePackage)
                                    <p class="mt-1 truncate text-xs font-medium text-emerald-300">{{ $servicePackage }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-middle">
                                <div class="mx-auto flex min-w-[140px] max-w-[180px] flex-col items-center gap-1.5 text-center">
                                    <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold {{ $booking->status->color() }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                    @if($serviceOutcomeLabel = $booking->completionOutcomeLabel())
                                        <span class="inline-flex max-w-full items-center rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-300">
                                            {{ $serviceOutcomeLabel }}
                                        </span>
                                    @endif
                                    @if($serviceCompletedBy = $booking->completedAttributionLabel())
                                        <p class="max-w-full break-words text-[10px] font-medium leading-snug text-emerald-300/90" title="{{ $serviceCompletedBy }}">
                                            {{ $serviceCompletedBy }}
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
                                    @if($booking->isTerminalStatus())
                                        <span class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-[#070A10] px-2.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                            {{ $booking->status === \App\Enums\AppointmentStatus::Completed ? 'Đã đóng · Hoàn tất' : 'Đã đóng · Đã hủy' }}
                                        </span>
                                    @else
                                        <div class="w-32 {{ $hasPendingReschedule ? 'opacity-50' : '' }}" title="{{ $hasPendingReschedule ? 'Mở hồ sơ để xác nhận lịch mới trước khi đổi trạng thái' : '' }}">
                                            <x-ui.custom-select
                                                compact
                                                aria-label="Cập nhật trạng thái lịch chăm sóc xe #{{ $booking->id }}"
                                                :value="$booking->status->value"
                                                :options="$booking->allowedStatusOptions()"
                                                wire-action="handleInlineStatusChange"
                                                :wire-record-id="$booking->id"
                                                :disabled="$hasPendingReschedule"
                                            />
                                        </div>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="inspectBooking({{ $booking->id }})"
                                        data-testid="inspect-service-booking-{{ $booking->id }}"
                                        title="Xem hồ sơ dịch vụ & tác vụ"
                                        aria-label="Xem hồ sơ lịch chăm sóc xe #{{ $booking->id }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/20 hover:text-white cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6">
                                <x-admin.empty-state
                                    title="Chưa có lịch hẹn dịch vụ chăm sóc xe"
                                    description="Chưa có hồ sơ dịch vụ nào khớp bộ lọc hiện tại."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($bookings->hasPages())
        <div class="mt-8">
            {{ $bookings->links() }}
        </div>
    @endif

    <!-- Service Booking Quick Inspector Drawer (Teleported to body, Z-100/101, Independent Scroll & 2-Way Timeline) -->
    @if($this->inspectedBooking)
        @php
            $booking = $this->inspectedBooking;
            $bookingEmail = $booking->user?->email ?? $booking->guest_email;
            $bookingCar = $booking->product?->name ?? ($booking->meta_data['customer_car_model'] ?? 'Xe BMW của khách');
            $bookingMeta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($booking->meta_data);
            $bookingCondition = $bookingMeta['customer_car_condition'] ?? null;
            $officialSentAt = $bookingMeta['official_confirmation_sent_at'] ?? null;
            $timelineEvents = \App\Services\Crm\InteractionTimelineRecorder::timelineForAppointment($booking);
        @endphp
        <div
            wire:key="service-booking-inspector-drawer-{{ $booking->id }}"
            x-data="{
                init() { document.body.classList.add('overflow-hidden'); },
                destroy() { document.body.classList.remove('overflow-hidden'); },
                close() {
                    document.body.classList.remove('overflow-hidden');
                    $wire.closeInspector();
                }
            }"
            @keydown.escape.window="close()"
        >
            <template x-teleport="body">
                <div
                    class="fixed inset-0 z-[100]"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Hồ sơ Dịch vụ &amp; Tác vụ"
                >
                    <div
                        class="fixed inset-0 z-[100] bg-black/75 backdrop-blur-sm"
                        @click="close()"
                    ></div>

                    <div class="fixed inset-y-0 right-0 z-[101] flex h-dvh w-full max-w-xl flex-col overflow-hidden rounded-l-2xl border-l border-white/10 bg-[#0B101B] text-white shadow-2xl shadow-black/70">
                        <header class="shrink-0 flex items-start justify-between gap-4 border-b border-white/[0.08] bg-[#0D121C] px-6 py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/15 px-3 py-0.5 text-[10px] font-bold tracking-wider text-[#70A7FF]">
                                        DỊCH VỤ CHĂM SÓC XE #{{ $booking->id }}
                                    </span>
                                    @if($booking->customer_order_code)
                                        <span class="rounded-full border border-sky-500/30 bg-sky-500/15 px-3 py-0.5 text-[11px] font-bold tabular-nums text-sky-300">
                                            {{ $booking->customer_order_code }}
                                        </span>
                                    @endif
                                    @if($booking->daily_sequence_code)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-3 py-0.5 text-[11px] font-semibold tabular-nums text-zinc-200">
                                            {{ $booking->daily_sequence_code }}
                                        </span>
                                    @endif
                                    @if($booking->customer_internal_code)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-3 py-0.5 text-[11px] font-semibold tabular-nums text-zinc-300">
                                            {{ $booking->customer_internal_code }}
                                        </span>
                                    @endif
                                    <span class="rounded-full border px-3 py-0.5 text-[11px] font-semibold {{ $booking->status->color() }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                </div>
                                <h3 class="mt-2.5 truncate text-lg font-bold text-white">
                                    {{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}
                                </h3>
                                @php
                                    $bookingHasPendingReschedule = $booking->hasPendingRescheduleReview();
                                    $bookingPrevDate = is_string($bookingMeta['previous_appointment_date'] ?? null) ? trim($bookingMeta['previous_appointment_date']) : '';
                                @endphp
                                <p class="mt-0.5 text-xs text-zinc-400">
                                    {{ $bookingCar }} · {{ $booking->type->label() }} ·
                                    @if($bookingHasPendingReschedule && $bookingPrevDate !== '')
                                        <span class="opacity-50 line-through text-zinc-400 tabular-nums">{{ $bookingPrevDate }}</span>
                                        <span class="mx-1 text-amber-300">→</span>
                                        <strong class="text-amber-300 tabular-nums">{{ $booking->appointment_date?->format('H:i — d/m/Y') }}</strong>
                                    @else
                                        {{ $booking->appointment_date?->format('H:i — d/m/Y') }}
                                    @endif
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="close()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] text-lg font-bold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                aria-label="Đóng hồ sơ dịch vụ"
                            >
                                ×
                            </button>
                        </header>

                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 space-y-5">
                            <x-admin.crm-reschedule-alert-card
                                :record="$booking"
                                acknowledge-action="acknowledgeGuestReschedule"
                            />

                            <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                <p class="text-xs font-semibold text-[#38BDF8]">1. Thông tin khách hàng &amp; Gói dịch vụ</p>
                                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 text-xs">
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Số điện thoại</p>
                                        <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="font-bold tabular-nums text-white">{{ $booking->guest_phone ?: 'Chưa cung cấp' }}</span>
                                            @if(filled($booking->guest_phone))
                                                <a href="tel:{{ $booking->guest_phone }}" class="rounded-lg border border-emerald-500/30 bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300 hover:bg-emerald-500/25">Gọi</a>
                                                <button
                                                    type="button"
                                                    @click="navigator.clipboard?.writeText('{{ $booking->guest_phone }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                    class="rounded-lg border border-white/10 bg-[#131A29] px-2.5 py-0.5 text-[11px] font-semibold text-zinc-300 hover:border-[#1C69D4] cursor-pointer"
                                                    x-text="copied ? 'Đã copy' : 'Copy'"
                                                ></button>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Email</p>
                                        <p class="mt-1 truncate font-semibold text-zinc-200">{{ $bookingEmail ?: 'Chưa cung cấp' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-[11px] font-medium text-zinc-400">Gói chăm sóc xe đã chọn</p>
                                        <p class="mt-1 font-bold text-emerald-300">{{ $booking->servicePackageLabel() ?: $booking->type->label() }}</p>
                                    </div>
                                    @if($bookingCondition)
                                        <div class="sm:col-span-2">
                                            <p class="text-[11px] font-medium text-zinc-400">Biển số / Tình trạng xe</p>
                                            <p class="mt-1 text-zinc-300">{{ $bookingCondition }}</p>
                                        </div>
                                    @endif
                                    @if(filled($booking->notes))
                                        <div class="sm:col-span-2">
                                            <p class="text-[11px] font-medium text-zinc-400">Ghi chú thêm</p>
                                            <p class="mt-1 text-zinc-300">{{ $booking->notes }}</p>
                                        </div>
                                    @endif
                                    <div class="sm:col-span-2 border-t border-white/[0.06] pt-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Sales / Cố vấn phụ trách</p>
                                        @if(auth()->user()?->canManageSystem())
                                            <div class="mt-1.5">
                                                <x-ui.custom-select
                                                    compact
                                                    aria-label="Phân công Sales cho lịch dịch vụ #{{ $booking->id }}"
                                                    :value="$booking->assigned_to ? (string) $booking->assigned_to : ''"
                                                    :options="$salesAssignOptions"
                                                    wire-action="assignToSales"
                                                    :wire-record-id="$booking->id"
                                                    :disabled="$booking->status === \App\Enums\AppointmentStatus::Cancelled"
                                                />
                                            </div>
                                        @else
                                            <p class="mt-1 font-semibold text-sky-300">{{ $booking->assignedSales?->name ?? 'Chưa phân công' }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($bookingCancelLabel = $booking->cancellationAttributionLabel())
                                    <div class="border-t border-white/[0.07] pt-3 text-xs text-rose-300">
                                        <span class="font-semibold text-rose-400">Nguồn hủy hồ sơ:</span> {{ $bookingCancelLabel }}
                                    </div>
                                @endif
                            </section>

                            <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                <p class="text-xs font-semibold text-[#38BDF8]">2. Trạng thái Email &amp; Hậu mãi</p>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 text-xs">
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Thư tiếp nhận hệ thống</p>
                                        <p class="mt-1 font-semibold {{ $booking->confirmation_email_sent_at ? 'text-emerald-300' : 'text-zinc-500' }}">
                                            {{ $booking->confirmation_email_sent_at ? $booking->confirmation_email_sent_at->format('H:i · d/m/Y') : 'Chưa ghi nhận' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Thư xác nhận chính thức</p>
                                        <p class="mt-1 font-semibold {{ $officialSentAt ? 'text-emerald-300' : 'text-zinc-500' }}">
                                            {{ $officialSentAt ? \Illuminate\Support\Carbon::parse($officialSentAt)->format('H:i · d/m/Y') : 'Chưa gửi từ Showroom' }}
                                        </p>
                                    </div>
                                </div>
                                @if($bookingCancelLabel = $booking->cancellationAttributionLabel())
                                    <div class="border-t border-white/[0.07] pt-3 text-xs text-rose-300">
                                        <span class="font-semibold text-rose-400">Nguồn hủy hồ sơ:</span> {{ $bookingCancelLabel }}
                                    </div>
                                @endif
                            </section>

                            <x-admin.crm-interaction-timeline
                                :events="$timelineEvents"
                                heading="3. Lịch sử tương tác Khách hàng ↔ Hệ thống"
                            />

                            <x-admin.crm-action-hub
                                context-key="service-booking"
                                :record-id="$booking->id"
                                :status-value="$booking->status->value"
                                :status-label="$booking->status->label()"
                                confirm-label="Duyệt & Xác nhận"
                                confirm-hint="Xác nhận khoang xưởng & gửi thư"
                                complete-label="Hoàn tất"
                                complete-hint="Ghi nhận biên bản dịch vụ & bàn giao xe"
                                :complete-action="'openCompletionModal(' . $booking->id . ')'"
                                cancel-label="Hủy hồ sơ"
                                :completed-message="($booking->completedAttributionLabel() ? $booking->completedAttributionLabel() . ' — ' : '') . 'Lịch bảo dưỡng / chăm sóc xe đã hoàn tất bàn giao và khóa chuyển trạng thái.'"
                                cancelled-message="Lịch dịch vụ đã bị hủy — Không thể khôi phục hoặc duyệt lại."
                                :reschedule-locked="$bookingHasPendingReschedule"
                                :error-message="$errorMessage"
                            >
                                <x-slot name="extraLinks">
                                    @if($booking->status === \App\Enums\AppointmentStatus::Confirmed && ! $bookingHasPendingReschedule && filled($bookingEmail))
                                        <button
                                            type="button"
                                            wire:click="resendConfirmationEmail({{ $booking->id }})"
                                            wire:loading.attr="disabled"
                                            data-testid="resend-confirmation-service-booking-{{ $booking->id }}"
                                            class="flex h-9 w-full items-center justify-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 text-xs font-semibold text-emerald-200 transition-colors hover:bg-emerald-500/20 cursor-pointer disabled:opacity-50"
                                        >
                                            Gửi lại thư xác nhận dịch vụ (Khóa chống lặp 30s)
                                        </button>
                                    @endif
                                </x-slot>
                            </x-admin.crm-action-hub>

                            <x-admin.crm-drawer-archive-panel :record="$booking" />

                            @php
                                $servicePreviewItems = [
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'system_received']),
                                        'label' => 'Xem thư Hệ thống tiếp nhận',
                                        'toneClass' => 'border-[#1C69D4]/30 text-[#70A7FF] hover:border-[#1C69D4] hover:bg-[#1C69D4]/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'admin_confirmed']),
                                        'label' => 'Xác nhận xưởng dịch vụ',
                                        'toneClass' => 'border-emerald-500/30 text-emerald-300 hover:border-emerald-400 hover:bg-emerald-500/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'guest_rescheduled']),
                                        'label' => 'Xem thư Khách đổi lịch',
                                        'toneClass' => 'border-amber-500/30 text-amber-300 hover:border-amber-400 hover:bg-amber-500/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'reminder']),
                                        'label' => 'Xem thư Nhắc lịch 24h',
                                        'toneClass' => 'border-indigo-500/30 text-indigo-300 hover:border-indigo-400 hover:bg-indigo-500/15',
                                    ],
                                ];
                            @endphp
                            <x-admin.mail-preview-accordion
                                :key-id="'service-booking-'.$booking->id"
                                :items="$servicePreviewItems"
                            />
                        </div>

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

    {{-- Dedicated Service Completion Modal (Teleported to body, Z-120) --}}
    @if($completionTargetId !== null)
        @php
            $hasValidServiceOutcome = in_array(
                $completionOutcome,
                [\App\Models\Appointment::OUTCOME_SERVICE_DELIVERED, \App\Models\Appointment::OUTCOME_SERVICE_INSPECTED_ADVISED],
                true
            );
        @endphp
        <div
            wire:key="service-completion-modal-wrap-{{ $completionTargetId }}"
            x-data
            @keydown.escape.window="$wire.closeCompletionModal()"
        >
            <template x-teleport="body">
                <div
                    data-service-completion-modal="{{ $completionTargetId }}"
                    class="fixed inset-0 z-[120] flex items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="service-completion-modal-title"
                >
                    <div
                        wire:click="closeCompletionModal"
                        class="absolute inset-0 z-0 bg-black/80 backdrop-blur-sm transition-opacity"
                    ></div>

                    <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-white/15 bg-[#0B0F17] p-6 text-white shadow-2xl">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-300">
                            Hoàn tất Lịch Dịch vụ BMW · {{ $completionTargetReference }}
                        </p>
                        <h3 id="service-completion-modal-title" class="mt-1.5 text-lg font-bold text-white">
                            Biên bản hoàn tất Chăm sóc / Bảo dưỡng &amp; Bàn giao xe
                        </h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">
                            Xác nhận tình trạng bàn giao thực tế và ghi chú kỹ thuật để hệ thống gửi <strong class="text-white">Thư cảm ơn Dịch vụ BMW</strong> (tuyệt đối không gửi nhầm kịch bản xe mới / voucher mua xe).
                        </p>

                        <div class="mt-5 space-y-3" role="radiogroup" aria-label="Kết quả bàn giao dịch vụ">
                            <button
                                type="button"
                                wire:key="srv-option-delivered-{{ $completionTargetId }}"
                                data-service-outcome-option="service_delivered"
                                role="radio"
                                aria-checked="{{ $completionOutcome === 'service_delivered' ? 'true' : 'false' }}"
                                wire:click="selectCompletionOutcome('service_delivered')"
                                class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'service_delivered' ? 'border-emerald-400 bg-emerald-500/15 ring-1 ring-emerald-400/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25 hover:bg-[#111827]' }}"
                            >
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'service_delivered' ? 'border-emerald-400 bg-emerald-500 text-slate-950' : 'border-zinc-500 bg-transparent' }}">
                                    @if($completionOutcome === 'service_delivered')
                                        <span class="h-2 w-2 rounded-full bg-slate-950"></span>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-bold text-white">Đã hoàn tất dịch vụ &amp; bàn giao xe</p>
                                        <span class="rounded-full border border-emerald-400/40 bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-bold text-emerald-200">
                                            Biên bản nghiệm thu
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-300">
                                        Phương tiện đã hoàn thành đầy đủ hạng mục chăm sóc / bảo dưỡng và bàn giao trực tiếp cho khách hàng.
                                    </p>
                                </div>
                            </button>

                            <button
                                type="button"
                                wire:key="srv-option-advised-{{ $completionTargetId }}"
                                data-service-outcome-option="service_inspected_advised"
                                role="radio"
                                aria-checked="{{ $completionOutcome === 'service_inspected_advised' ? 'true' : 'false' }}"
                                wire:click="selectCompletionOutcome('service_inspected_advised')"
                                class="flex w-full items-start gap-3.5 rounded-xl border p-4 text-left transition-all cursor-pointer {{ $completionOutcome === 'service_inspected_advised' ? 'border-[#38BDF8] bg-[#1C69D4]/20 ring-1 ring-[#38BDF8]/50' : 'border-white/10 bg-[#0D121C] hover:border-white/25 hover:bg-[#111827]' }}"
                            >
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completionOutcome === 'service_inspected_advised' ? 'border-[#70A7FF] bg-[#1C69D4] text-white' : 'border-zinc-500 bg-transparent' }}">
                                    @if($completionOutcome === 'service_inspected_advised')
                                        <span class="h-2 w-2 rounded-full bg-white"></span>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-bold text-white">Đã kiểm tra &amp; tư vấn kỹ thuật thêm</p>
                                        <span class="rounded-full border border-white/15 bg-white/[0.06] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
                                            Khuyến nghị kỹ thuật
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-300">
                                        Đã hoàn tất chẩn đoán tiêu chuẩn BMW ISTA và gửi khuyến nghị hạng mục cần bảo dưỡng ở kỳ tiếp theo.
                                    </p>
                                </div>
                            </button>
                        </div>

                        <div class="mt-4 space-y-3 rounded-xl border border-white/10 bg-[#0D121C]/80 p-4">
                            <div>
                                <label for="service-completion-note" class="block text-xs font-semibold text-zinc-300">Ghi chú hạng mục thực hiện (tùy chọn)</label>
                                <textarea
                                    id="service-completion-note"
                                    data-service-completion-note
                                    wire:model="serviceCompletionNote"
                                    rows="2"
                                    maxlength="1000"
                                    placeholder="Ví dụ: Đã thay nhớt động cơ chính hãng, vệ sinh khoang máy & phủ bảo vệ sơn..."
                                    class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 py-2 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="service-actual-cost" class="block text-xs font-semibold text-zinc-300">Chi phí thực tế (VNĐ, tùy chọn)</label>
                                    <input
                                        id="service-actual-cost"
                                        data-service-actual-cost
                                        type="text"
                                        inputmode="numeric"
                                        wire:model="serviceActualCostInput"
                                        placeholder="Ví dụ: 3.800.000"
                                        class="mt-1.5 h-9 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                                    >
                                </div>

                                <div>
                                    <label for="service-reminder-months" class="block text-xs font-semibold text-zinc-300">Nhắc bảo dưỡng kỳ tới</label>
                                    <select
                                        id="service-reminder-months"
                                        data-service-reminder-months
                                        wire:model="serviceReminderMonths"
                                        class="mt-1.5 h-9 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs text-white focus:border-[#1C69D4] focus:outline-none"
                                    >
                                        <option value="0">Không đặt lịch nhắc</option>
                                        <option value="3">Sau 3 tháng</option>
                                        <option value="6">Sau 6 tháng</option>
                                        <option value="12">Sau 12 tháng</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        @error('completionOutcome')
                            <p class="mt-2 text-xs font-semibold text-rose-400">{{ $message }}</p>
                        @enderror

                        <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-white/10 pt-4">
                            <button
                                type="button"
                                data-service-modal-cancel
                                wire:click="closeCompletionModal"
                                wire:loading.attr="disabled"
                                class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white cursor-pointer"
                            >
                                Hủy bỏ
                            </button>
                            <button
                                type="button"
                                data-service-modal-confirm
                                wire:click="confirmCompletion"
                                wire:loading.attr="disabled"
                                wire:target="confirmCompletion"
                                @disabled(! $hasValidServiceOutcome)
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl px-5 text-xs font-bold text-white transition-colors {{ $hasValidServiceOutcome ? 'bg-emerald-600 hover:bg-emerald-500 cursor-pointer' : 'bg-zinc-700/70 text-zinc-400 opacity-60 cursor-not-allowed' }}"
                            >
                                <span wire:loading.remove wire:target="confirmCompletion">Hoàn tất dịch vụ &amp; gửi thư</span>
                                <span wire:loading wire:target="confirmCompletion">Đang xử lý...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
