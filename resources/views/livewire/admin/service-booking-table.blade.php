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
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng lịch dịch vụ</p>
            <p class="mt-1 text-2xl font-black tabular-nums text-white">{{ $bookings->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Contextual Quick-Filter Strip -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <button
            type="button"
            wire:click="applyQuickFilter('pending')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'pending' && ! $onlyToday ? 'border-amber-400 bg-amber-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-amber-400/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">Chờ xử lý</p>
                <p class="mt-1 text-[11px] text-zinc-400">Cần xác nhận khoang xe</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['pending'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('confirmed')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'confirmed' && ! $onlyToday ? 'border-[#1C69D4] bg-[#1C69D4]/15' : 'border-[#242B38] bg-[#0B0F17] hover:border-[#1C69D4]' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">Đã xác nhận</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã giữ kỹ thuật viên</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['confirmed'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('today')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $onlyToday && $status === '' ? 'border-cyan-400 bg-cyan-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-cyan-400/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-400">Hôm nay</p>
                <p class="mt-1 text-[11px] text-zinc-400">Xe vào xưởng trong ngày</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['today'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('completed')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'completed' && ! $onlyToday ? 'border-emerald-500 bg-emerald-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-emerald-500/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400">Hoàn thành</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã bàn giao xe</p>
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
        <form action="{{ route('admin.service-bookings.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-3 lg:grid-cols-[1.5fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm khách hàng / xe / gói dịch vụ">
                <input
                    id="service-search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Tên khách, SĐT, email, biển số hoặc gói chăm sóc..."
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
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
                        <th class="w-[29%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Xe &amp; Gói dịch vụ</th>
                        <th class="w-[15%] px-4 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Trạng thái</th>
                        <th class="w-[20%] px-4 py-3.5 text-right text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($bookings as $booking)
                        @php
                            $carModel = $booking->product?->name
                                ?? ($booking->meta_data['customer_car_model'] ?? 'Xe BMW của khách');
                            $servicePackage = $booking->servicePackageLabel();
                            $customerEmail = $booking->user?->email ?? $booking->guest_email;
                        @endphp
                        <tr wire:key="admin-service-booking-{{ $booking->id }}" class="group align-middle transition-colors hover:bg-[#0B0F17]/70">
                            <td class="whitespace-nowrap px-4 py-4">
                                <p class="text-sm font-black tabular-nums tracking-wider text-white">{{ $booking->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-zinc-500">{{ $booking->appointment_date->format('H:i') }} · #{{ $booking->id }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-400">{{ $booking->guest_phone ?: ($customerEmail ?: 'Chưa có liên hệ') }}</p>
                                @if(filled($booking->guest_phone) && filled($customerEmail))
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">{{ $customerEmail }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="truncate text-xs font-bold uppercase tracking-wider text-white">{{ $carModel }}</span>
                                    <span class="shrink-0 whitespace-nowrap rounded-none border border-[#1C69D4]/30 bg-[#1C69D4]/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-[#70A7FF]">
                                        {{ $booking->type->label() }}
                                    </span>
                                </div>
                                @if($servicePackage)
                                    <p class="mt-1 truncate text-[11px] font-semibold text-emerald-400">{{ $servicePackage }}</p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-none border px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.16em] {{ $booking->status->color() }}">
                                    {{ $booking->status->label() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <div class="w-32">
                                        <x-ui.custom-select
                                            compact
                                            aria-label="Cập nhật trạng thái lịch chăm sóc xe #{{ $booking->id }}"
                                            :value="$booking->status->value"
                                            :options="$statusActionOptions"
                                            wire-action="updateStatus"
                                            :wire-record-id="$booking->id"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="inspectBooking({{ $booking->id }})"
                                        data-testid="inspect-service-booking-{{ $booking->id }}"
                                        title="Xem hồ sơ dịch vụ & tác vụ"
                                        aria-label="Xem hồ sơ lịch chăm sóc xe #{{ $booking->id }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-none border border-[#242B38] bg-[#0B0F17] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/15 hover:text-[#70A7FF] cursor-pointer"
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
    </x-admin.card>

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
                        class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm"
                        @click="close()"
                    ></div>

                    <div class="fixed inset-y-0 right-0 z-[101] flex h-dvh w-full max-w-xl flex-col rounded-none border-l border-[#1C69D4] bg-[#0B0F17] text-white shadow-2xl">
                        <header class="shrink-0 flex items-start justify-between gap-4 border-b border-[#242B38] bg-[#050505] px-6 py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">
                                        DỊCH VỤ CHĂM SÓC XE #{{ $booking->id }}
                                    </span>
                                    <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] {{ $booking->status->color() }}">
                                        {{ $booking->status->label() }}
                                    </span>
                                </div>
                                <h3 class="mt-2 truncate text-lg font-black uppercase tracking-wider text-white">
                                    {{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-400">
                                    {{ $bookingCar }} · {{ $booking->type->label() }} · {{ $booking->appointment_date?->format('H:i — d/m/Y') }}
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="close()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none border border-[#242B38] bg-[#0B0F17] text-lg font-black text-zinc-300 transition-colors hover:border-white hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                aria-label="Đóng hồ sơ dịch vụ"
                            >
                                ×
                            </button>
                        </header>

                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 space-y-6">
                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">1. Thông tin khách hàng &amp; Gói dịch vụ</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Số điện thoại</p>
                                        <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="font-bold tabular-nums text-white">{{ $booking->guest_phone ?: 'Chưa cung cấp' }}</span>
                                            @if(filled($booking->guest_phone))
                                                <a href="tel:{{ $booking->guest_phone }}" class="border border-[#242B38] bg-[#0B0F17] px-2 py-0.5 text-[9px] font-bold uppercase text-emerald-400 hover:border-emerald-500">Gọi</a>
                                                <button
                                                    type="button"
                                                    @click="navigator.clipboard?.writeText('{{ $booking->guest_phone }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                    class="border border-[#242B38] bg-[#0B0F17] px-2 py-0.5 text-[9px] font-bold uppercase text-zinc-300 hover:border-[#1C69D4] cursor-pointer"
                                                    x-text="copied ? 'Đã copy' : 'Copy'"
                                                ></button>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Email</p>
                                        <p class="mt-1 truncate font-semibold text-zinc-200">{{ $bookingEmail ?: 'Chưa cung cấp' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Gói chăm sóc xe đã chọn</p>
                                        <p class="mt-1 font-bold text-emerald-400">{{ $booking->servicePackageLabel() ?: $booking->type->label() }}</p>
                                    </div>
                                    @if($bookingCondition)
                                        <div class="sm:col-span-2">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Biển số / Tình trạng xe</p>
                                            <p class="mt-1 text-zinc-300">{{ $bookingCondition }}</p>
                                        </div>
                                    @endif
                                    @if(filled($booking->notes))
                                        <div class="sm:col-span-2">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Ghi chú thêm</p>
                                            <p class="mt-1 text-zinc-300">{{ $booking->notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-2.5">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">2. Trạng thái Email &amp; Hậu mãi</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 text-xs">
                                    <div class="border border-white/5 bg-[#0B0F17] p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Thư tiếp nhận hệ thống</p>
                                        <p class="mt-1 font-bold {{ $booking->confirmation_email_sent_at ? 'text-emerald-400' : 'text-zinc-500' }}">
                                            {{ $booking->confirmation_email_sent_at ? $booking->confirmation_email_sent_at->format('H:i · d/m/Y') : 'Chưa ghi nhận' }}
                                        </p>
                                    </div>
                                    <div class="border border-white/5 bg-[#0B0F17] p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Thư xác nhận chính thức</p>
                                        <p class="mt-1 font-bold {{ $officialSentAt ? 'text-emerald-400' : 'text-zinc-500' }}">
                                            {{ $officialSentAt ? \Illuminate\Support\Carbon::parse($officialSentAt)->format('H:i · d/m/Y') : 'Chưa gửi từ Showroom' }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <x-admin.interaction-timeline
                                :events="$timelineEvents"
                                heading="3. Lịch sử tương tác Khách hàng ↔ Hệ thống"
                            />

                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">4. Trung tâm Thao tác &amp; Xem trước Email</p>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                    @if(filled($bookingEmail))
                                        <button
                                            type="button"
                                            @click="$wire.resendConfirmationEmail({{ $booking->id }})"
                                            class="sm:col-span-2 flex items-center justify-center rounded-none border border-emerald-500/60 bg-emerald-600/20 px-4 py-3 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-300 transition-colors hover:bg-emerald-600 hover:text-white cursor-pointer"
                                        >
                                            Gửi thư Xác nhận chính thức từ Showroom
                                        </button>
                                    @endif

                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'system_received']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-[#1C69D4]/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-[#70A7FF] transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/15"
                                    >
                                        Xem thư Hệ thống tiếp nhận
                                    </a>
                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'admin_confirmed']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-emerald-500/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-300 transition-colors hover:border-emerald-400 hover:bg-emerald-500/15"
                                    >
                                        Xem thư Admin duyệt VIP
                                    </a>
                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'guest_rescheduled']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-amber-500/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-amber-300 transition-colors hover:border-amber-400 hover:bg-amber-500/15"
                                    >
                                        Xem thư Khách đổi lịch
                                    </a>
                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'reminder']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex items-center justify-center rounded-none border border-indigo-500/40 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-300 transition-colors hover:border-indigo-400 hover:bg-indigo-500/15"
                                    >
                                        Xem thư Nhắc lịch 24h
                                    </a>
                                    <a
                                        href="{{ route('admin.mail-preview.appointment', ['appointment' => $booking, 'mode' => 'followup']) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="sm:col-span-2 flex items-center justify-center rounded-none border border-amber-600/45 bg-[#0B0F17] px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-[0.12em] text-amber-200 transition-colors hover:border-amber-500 hover:bg-amber-600/15"
                                    >
                                        Xem thư Hậu mãi trả góp
                                    </a>
                                </div>
                            </section>
                        </div>

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
