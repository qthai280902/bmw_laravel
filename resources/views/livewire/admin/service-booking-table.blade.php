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
        <form action="{{ route('admin.service-bookings.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-3.5 lg:grid-cols-[1.5fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm khách hàng / xe / gói dịch vụ">
                <input
                    id="service-search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Tên khách, SĐT, email, biển số hoặc gói chăm sóc..."
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

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer">
                    Lọc
                </button>
                <button type="button" wire:click="resetFilters" class="h-10 whitespace-nowrap rounded-xl border border-white/10 bg-[#131A29] px-4 text-center text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white cursor-pointer">
                    Xóa
                </button>
            </div>
        </form>
    </x-admin.card>

    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
        <div class="overflow-x-auto lg:overflow-x-visible" wire:loading.class="opacity-60">
            <table class="w-full lg:table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/60">
                        <th class="w-[14%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Thời điểm</th>
                        <th class="w-[22%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Khách hàng</th>
                        <th class="w-[29%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Xe &amp; Gói dịch vụ</th>
                        <th class="w-[15%] px-5 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                        <th class="w-[20%] px-5 py-3.5 text-right text-xs font-semibold text-zinc-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($bookings as $booking)
                        @php
                            $carModel = $booking->product?->name
                                ?? ($booking->meta_data['customer_car_model'] ?? 'Xe BMW của khách');
                            $servicePackage = $booking->servicePackageLabel();
                            $customerEmail = $booking->user?->email ?? $booking->guest_email;
                        @endphp
                        <tr wire:key="admin-service-booking-{{ $booking->id }}" class="group align-middle transition-colors hover:bg-white/[0.03]">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-bold tabular-nums text-white">{{ $booking->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-0.5 text-xs font-medium tabular-nums text-zinc-400">{{ $booking->appointment_date->format('H:i') }} · #{{ $booking->id }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-300">{{ $booking->guest_phone ?: ($customerEmail ?: 'Chưa có liên hệ') }}</p>
                                @if(filled($booking->guest_phone) && filled($customerEmail))
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $customerEmail }}</p>
                                @endif
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

                            <td class="whitespace-nowrap px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold {{ $booking->status->color() }}">
                                    {{ $booking->status->label() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if($booking->isTerminalStatus())
                                        <span class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-[#070A10] px-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                            {{ $booking->status === \App\Enums\AppointmentStatus::Completed ? 'Đã đóng · Hoàn tất' : 'Đã đóng · Đã hủy' }}
                                        </span>
                                    @else
                                        <div class="w-36">
                                            <x-ui.custom-select
                                                compact
                                                aria-label="Cập nhật trạng thái lịch chăm sóc xe #{{ $booking->id }}"
                                                :value="$booking->status->value"
                                                :options="$booking->allowedStatusOptions()"
                                                wire-action="updateStatus"
                                                :wire-record-id="$booking->id"
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
                                    description="Các yêu cầu bảo dưỡng định kỳ, phủ Ceramic, chăm sóc da Merino và chẩn đoán ISTA từ trang Dịch vụ sẽ xuất hiện tại đây."
                                    hint="Mẹo: Khi khách đặt dịch vụ từ /services hoặc /dich-vu, cố vấn dịch vụ có thể bấm biểu tượng Con Mắt để xem chi tiết & gửi thư xác nhận."
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
            $bookingCondition = is_array($booking->meta_data) ? ($booking->meta_data['customer_car_condition'] ?? null) : null;
            $officialSentAt = is_array($booking->meta_data) ? ($booking->meta_data['official_confirmation_sent_at'] ?? null) : null;
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
                                    <span class="rounded-full border px-3 py-0.5 text-[11px] font-semibold {{ $booking->status->color() }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                </div>
                                <h3 class="mt-2.5 truncate text-lg font-bold text-white">
                                    {{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-400">
                                    {{ $bookingCar }} · {{ $booking->type->label() }} · {{ $booking->appointment_date?->format('H:i — d/m/Y') }}
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
                                </div>
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
                            </section>

                            <x-admin.interaction-timeline
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
                                complete-hint="Đã chăm sóc & bàn giao xe"
                                cancel-label="Hủy hồ sơ"
                                completed-message="Lịch bảo dưỡng / chăm sóc xe đã hoàn tất bàn giao và khóa chuyển trạng thái."
                                cancelled-message="Lịch dịch vụ đã bị hủy — Không thể khôi phục hoặc duyệt lại."
                                :error-message="$errorMessage"
                            />

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
</div>
