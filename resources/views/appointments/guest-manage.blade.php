<x-app-layout>
    @section('title', 'Quản Lý Lịch Hẹn | BMW Showroom Vietnam')

    @php
        $car = $appointment->product;
        $customerOrderCode = $appointment->publicReferenceCode();
        $assignedSales = $appointment->assignedSales;
        $isLocked = $appointment->isTerminalStatus();
        $isOutdatedLink = (bool) ($isOutdatedLink ?? false);
        $meta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);

        $metaGuestConfirmedAt = $meta['guest_confirmed_at'] ?? null;
        $guestConfirmedAtFormatted = $metaGuestConfirmedAt
            ? \Illuminate\Support\Carbon::parse($metaGuestConfirmedAt)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
            : null;
        $appointmentTimeFormatted = $appointment->appointment_date?->format('H:i d/m/Y') ?? 'Chưa ấn định';

        $metaInvalidatedConfirmedAt = $meta['invalidated_guest_confirmed_at'] ?? null;
        $invalidatedConfirmedAtFormatted = $metaInvalidatedConfirmedAt
            ? \Illuminate\Support\Carbon::parse($metaInvalidatedConfirmedAt)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
            : null;
        $invalidatedForDateLabel = $meta['invalidated_guest_confirmed_for_date'] ?? ($meta['previous_appointment_date'] ?? $appointmentTimeFormatted);
        $reschedulePendingReview = ! $isLocked && (bool) ($meta['reschedule_pending_review'] ?? false);
        $hasGuestConfirmed = filled($guestConfirmedAtFormatted);
        $isActionFrozen = $isLocked || $isOutdatedLink || $reschedulePendingReview;
        $isChangeCardLocked = $isActionFrozen || $hasGuestConfirmed;

        $cancellationAttribution = $appointment->cancellationAttributionLabel();
        $cancelReasonText = is_string($meta['cancel_reason'] ?? null) && trim($meta['cancel_reason']) !== ''
            ? trim($meta['cancel_reason'])
            : (is_string($meta['auto_cancelled_reason'] ?? null) ? trim($meta['auto_cancelled_reason']) : null);

        $currentSlotVn = $appointment->appointment_date?->copy();
        $currentDateStr = $currentSlotVn?->format('Y-m-d') ?? '';
        $currentTimeStr = $currentSlotVn?->format('H:i') ?? '';

        $defaultSlot = $currentSlotVn ? $currentSlotVn->copy() : now('Asia/Ho_Chi_Minh')->addDay()->setTime(9, 30);
        if (! \App\Http\Requests\StoreAppointmentRequest::isAfterShowroomNow($defaultSlot) || ! \App\Http\Requests\StoreAppointmentRequest::isWithinShowroomHours($defaultSlot)) {
            $defaultSlot = now('Asia/Ho_Chi_Minh')->addDay()->setTime(9, 30);
        }
        $initialDate = (string) old('new_date', $defaultSlot->format('Y-m-d'));
        $initialTime = (string) old('new_time', $defaultSlot->format('H:i'));
        $vnWeekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
        $initialFullDateLabel = ($vnWeekdays[\Illuminate\Support\Carbon::parse($initialDate, 'Asia/Ho_Chi_Minh')->dayOfWeek] ?? '').', '.\Illuminate\Support\Carbon::parse($initialDate, 'Asia/Ho_Chi_Minh')->format('d/m/Y');

        [$statusBg, $statusBorder, $statusText] = match ($appointment->status) {
            \App\Enums\AppointmentStatus::Confirmed => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-700'],
            \App\Enums\AppointmentStatus::Completed => ['bg-blue-50', 'border-blue-200', 'text-[#1C69D4]'],
            \App\Enums\AppointmentStatus::Cancelled => ['bg-rose-50', 'border-rose-200', 'text-rose-700'],
            default => ['bg-amber-50', 'border-amber-200', 'text-amber-700'],
        };
    @endphp

    <section class="guest-luxury min-h-screen bg-[#F1F5F9] py-10 px-4 text-slate-900 sm:py-14">
        <div class="mx-auto max-w-2xl space-y-6">
            {{-- Header Card --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-6">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#1C69D4]">
                            BMW Concierge Self-Service
                        </p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            Hồ sơ lịch hẹn {{ $customerOrderCode }}
                        </h1>
                        <p class="mt-1.5 text-sm text-slate-500">
                            Cổng tự phục vụ giúp Quý khách xác nhận lịch đến Showroom hoặc điều chỉnh thời gian thuận tiện nhất.
                        </p>
                    </div>
                    <span class="inline-flex items-center whitespace-nowrap rounded-lg border px-3.5 py-1.5 text-xs font-bold {{ $statusBg }} {{ $statusBorder }} {{ $statusText }}">
                        {{ $appointment->status->label() }}
                    </span>
                </div>

                @if($isOutdatedLink)
                    <div
                        data-outdated-link-banner
                        class="mt-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3.5 text-sm font-semibold leading-relaxed text-amber-900"
                    >
                        Liên kết quản lý lịch hẹn này là phiên bản cũ đã hết hiệu lực do lịch hẹn đã được cập nhật mới hơn. Quý khách vui lòng mở email xác nhận mới nhất từ BMW Showroom để thao tác.
                    </div>
                @elseif($reschedulePendingReview)
                    <div
                        data-reschedule-pending-banner
                        class="mt-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3.5 text-sm font-semibold leading-relaxed text-amber-900"
                    >
                        Yêu cầu đổi lịch sang {{ $appointment->appointment_date?->format('H:i — d/m/Y') }} đã được ghi nhận. Chuyên viên BMW Showroom sẽ kiểm tra và xác nhận lại lịch hẹn mới cho Quý khách.
                    </div>
                @elseif(session('success'))
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm font-semibold text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm font-semibold text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Structured Summary Table --}}
                @php
                    $previousAppointmentDateLabel = is_string($meta['previous_appointment_date'] ?? null) && trim($meta['previous_appointment_date']) !== ''
                        ? trim($meta['previous_appointment_date'])
                        : null;
                    $frozenCardClass = $isOutdatedLink
                        ? 'opacity-50 pointer-events-none select-none'
                        : ($reschedulePendingReview ? 'opacity-60 pointer-events-none select-none' : '');
                @endphp
                <div
                    data-guest-summary-card
                    class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-slate-50/70 transition-opacity {{ $isOutdatedLink ? 'opacity-50 select-none' : '' }}"
                >
                    @if($isOutdatedLink)
                        <div class="border-b border-amber-200 bg-amber-50/90 px-4 py-2 text-xs font-bold text-amber-800">
                            Thông tin tại thời điểm liên kết cũ &mdash; Đã có phiên bản cập nhật mới hơn
                        </div>
                    @endif
                    <dl class="divide-y divide-slate-200 text-sm">
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Mã tham chiếu</dt>
                            <dd class="font-bold text-[#1C69D4] tabular-nums">{{ $customerOrderCode }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Khách hàng</dt>
                            <dd class="font-semibold text-slate-900">{{ $appointment->customer_name }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Hạng mục đặt lịch</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $appointment->typeLabel() }}
                                @if($appointment->servicePackageLabel())
                                    &mdash; <span class="text-[#1C69D4]">{{ $appointment->servicePackageLabel() }}</span>
                                @elseif($car)
                                    &mdash; <span class="text-[#1C69D4]">{{ $car->name }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Thời gian hẹn</dt>
                            <dd class="font-bold text-slate-900 tabular-nums">
                                @if($isOutdatedLink && $previousAppointmentDateLabel)
                                    <span class="line-through text-slate-500">{{ $previousAppointmentDateLabel }}</span>
                                    <span class="ml-2 text-xs font-semibold text-amber-700">(Đã dời sang khung giờ mới)</span>
                                @else
                                    {{ $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'Chưa ấn định' }}
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Chuyên viên phụ trách</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $assignedSales?->exists ? $assignedSales->name : 'Đội ngũ Tư vấn BMW Showroom' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Địa điểm Showroom</dt>
                            <dd class="text-slate-700">BMW Flagship Showroom, Hà Nội / TP.HCM</dd>
                        </div>
                        @if($cancellationAttribution)
                            <div class="grid grid-cols-1 gap-1 bg-rose-50/70 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                                <dt class="font-semibold text-rose-700">Nguồn hủy lịch</dt>
                                <dd class="space-y-0.5">
                                    <p class="font-bold text-rose-800">{{ $cancellationAttribution }}</p>
                                    @if($cancelReasonText)
                                        <p class="text-xs text-rose-700/90">Lý do: {{ $cancelReasonText }}</p>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if(! $isLocked)
                    <div
                        data-guest-confirm-section
                        class="mt-6 space-y-3 {{ $frozenCardClass }}"
                    >
                        @if($guestConfirmedAtFormatted)
                            <div class="space-y-2.5">
                                <div class="inline-flex items-center gap-2.5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs font-bold text-[#1C69D4]">
                                    <svg class="h-4 w-4 shrink-0 text-[#1C69D4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Đã ghi nhận xác nhận đến Showroom cho lịch hẹn {{ $appointmentTimeFormatted }} (xác nhận lúc {{ $guestConfirmedAtFormatted }}).</span>
                                </div>
                                <div data-guest-confirmed-change-lock class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs leading-relaxed text-slate-600">
                                    Quý khách đã xác nhận tham dự cố định khung giờ này nên tính năng đổi giờ và hủy lịch trực tuyến đã được khóa. Nếu có thay đổi đột xuất, vui lòng liên hệ trực tiếp Hotline <a href="tel:1900123456" class="font-bold text-[#1C69D4] hover:underline">1900 123 456</a> hoặc Chuyên viên phụ trách để được hỗ trợ.
                                </div>
                            </div>
                        @else
                            @if($invalidatedConfirmedAtFormatted)
                                <div class="rounded-lg border border-slate-200 bg-slate-100 px-4 py-2.5 text-xs font-medium text-slate-600">
                                    <span class="line-through opacity-60">
                                        Đã ghi nhận xác nhận đến Showroom cho lịch hẹn {{ $invalidatedForDateLabel }} (xác nhận lúc {{ $invalidatedConfirmedAtFormatted }}).
                                    </span>
                                    <span class="ml-1.5 font-semibold text-amber-700">
                                        (Đã hết hiệu lực do thay đổi lịch hẹn)
                                    </span>
                                </div>
                            @endif

                            <form method="POST" action="{{ $updateUrl }}" class="w-full sm:w-auto">
                                @csrf
                                <input type="hidden" name="action" value="confirm">
                                <button
                                    type="submit"
                                    @disabled($isActionFrozen)
                                    class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-[#1C69D4] px-7 text-xs font-bold uppercase tracking-[0.12em] text-white shadow-sm transition-colors {{ $isActionFrozen ? 'opacity-60 cursor-not-allowed' : 'hover:bg-[#1554AD] cursor-pointer' }} sm:w-auto"
                                >
                                    Xác nhận chắc chắn đến Showroom
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Reschedule Card --}}
            @php
                $changeCardLockClass = $isChangeCardLocked ? 'opacity-50 pointer-events-none select-none' : '';
            @endphp
            <div
                data-guest-reschedule-card
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 {{ ! $isLocked ? $changeCardLockClass : '' }}"
            >
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#1C69D4]">Đề xuất thời gian khác</p>
                <h2 class="mt-1.5 text-lg font-bold text-slate-900">Yêu cầu đổi giờ hẹn</h2>

                @if($isLocked)
                    <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-relaxed text-slate-600">
                        Lịch hẹn này đang ở trạng thái <strong class="text-slate-900">{{ $appointment->status->label() }}</strong> nên không thể đổi giờ trực tuyến. Nếu cần hỗ trợ thêm, Quý khách vui lòng đặt một lịch hẹn mới tại Showroom BMW.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ $updateUrl }}"
                        x-data="{
                            currentDateStr: @js($currentDateStr),
                            currentTimeStr: @js($currentTimeStr),
                            proposedDateStr: @js($initialDate),
                            proposedDate: @js($initialFullDateLabel),
                            proposedTime: @js($initialTime),
                            isFrozen: @js($isChangeCardLocked),
                            hasChanged() {
                                if (!this.proposedDateStr || !this.proposedTime) {
                                    return false;
                                }
                                return `${this.proposedDateStr} ${this.proposedTime}` !== `${this.currentDateStr} ${this.currentTimeStr}`;
                            }
                        }"
                        @bmw-datetime-changed.window="if ($event.detail?.id === 'new_appointment_date') { proposedDateStr = $event.detail.date || ''; proposedDate = $event.detail.fullDateLabel || $event.detail.formattedDate || ''; proposedTime = $event.detail.time || ''; }"
                        class="mt-6 space-y-5"
                    >
                        @csrf
                        <input type="hidden" name="action" value="reschedule">

                        <div>
                            <label class="mb-2 block text-xs font-semibold text-slate-600">Ngày &amp; khung giờ đón tiếp mới (08:00 – 18:00)</label>
                            <x-ui.date-time-picker
                                id="new_appointment_date"
                                theme="light"
                                mode="reschedule"
                                :disabled="$isChangeCardLocked"
                                :anchor-date="$initialDate"
                                date-input-name="new_date"
                                time-input-name="new_time"
                                combined-input-name="new_appointment_date"
                                :value="$initialDate . ' ' . $initialTime"
                            />
                        </div>

                        @error('new_appointment_date')
                            <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror

                        <div>
                            <label for="reschedule_reason" class="block text-xs font-semibold text-slate-600">Ghi chú / Lý do đổi lịch</label>
                            <input
                                id="reschedule_reason"
                                type="text"
                                name="reason"
                                value="{{ old('reason') }}"
                                @disabled($isChangeCardLocked)
                                placeholder="Ví dụ: Dời sang buổi chiều cuối tuần..."
                                style="background-color: #FFFFFF; color: #0F172A; color-scheme: light;"
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] disabled:bg-slate-50 disabled:text-slate-400"
                            >
                        </div>

                        <div
                            x-show="hasChanged()"
                            x-cloak
                            data-reschedule-preview
                            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700"
                        >
                            <span class="font-semibold text-slate-500">Lịch hiện tại:</span>
                            <strong class="text-slate-900 tabular-nums">{{ $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'Chưa ấn định' }}</strong>
                            <span class="mx-1.5 font-bold text-[#1C69D4]">→</span>
                            <span class="font-semibold text-slate-500">Lịch đề xuất mới:</span>
                            <strong
                                class="text-[#1C69D4] tabular-nums"
                                x-text="(proposedTime && proposedDate) ? `${proposedTime} — ${proposedDate}` : 'Vui lòng chọn đủ ngày & giờ'"
                            ></strong>
                        </div>

                        <button
                            type="submit"
                            data-reschedule-submit
                            :disabled="!hasChanged() || isFrozen"
                            :class="(!hasChanged() || isFrozen) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#1C69D4] cursor-pointer'"
                            class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-300 bg-slate-900 px-6 text-xs font-bold uppercase tracking-[0.12em] text-white transition-colors"
                        >
                            Gửi yêu cầu đổi giờ hẹn
                        </button>
                    </form>
                @endif
            </div>

            {{-- Cancel Card --}}
            @if(! $isLocked)
                <div
                    data-guest-cancel-card
                    class="rounded-xl border border-rose-200 bg-white p-6 shadow-sm sm:p-8 {{ $changeCardLockClass }}"
                >
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-600">Thay đổi kế hoạch</p>
                    <h2 class="mt-1.5 text-lg font-bold text-slate-900">Hủy lịch hẹn</h2>

                    <form method="POST" action="{{ $updateUrl }}" class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end">
                        @csrf
                        <input type="hidden" name="action" value="cancel">
                        <div class="flex-1">
                            <label for="cancel_reason" class="block text-xs font-semibold text-slate-600">Lý do hủy (tùy chọn)</label>
                            <input
                                id="cancel_reason"
                                type="text"
                                name="reason"
                                @disabled($isChangeCardLocked)
                                placeholder="Vui lòng chia sẻ nếu có thay đổi lịch trình..."
                                style="background-color: #FFFFFF; color: #0F172A; color-scheme: light;"
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 disabled:bg-slate-50 disabled:text-slate-400"
                            >
                        </div>
                        <button
                            type="submit"
                            @disabled($isChangeCardLocked)
                            class="h-11 whitespace-nowrap rounded-lg border border-rose-200 bg-rose-50 px-6 text-xs font-bold uppercase tracking-[0.12em] text-rose-700 transition-colors {{ $isChangeCardLocked ? 'opacity-60 cursor-not-allowed' : 'hover:bg-rose-600 hover:text-white cursor-pointer' }}"
                        >
                            Hủy lịch hẹn
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
