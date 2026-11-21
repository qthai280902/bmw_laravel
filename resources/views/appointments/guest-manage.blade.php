<x-app-layout>
    @section('title', 'Quản Lý Lịch Hẹn | BMW Showroom Vietnam')

    @php
        $car = $appointment->product;
        $customerOrderCode = $appointment->publicReferenceCode();
        $assignedSales = $appointment->assignedSales;
        $isLocked = $appointment->isTerminalStatus();
        $meta = is_array($appointment->meta_data) ? $appointment->meta_data : [];

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
        $reschedulePendingReview = (bool) ($meta['reschedule_pending_review'] ?? false);

        $showroomSlots = ['08:30', '09:30', '10:30', '11:30', '13:30', '14:30', '15:30', '16:30', '17:30'];
        $defaultSlot = $appointment->appointment_date?->copy()->timezone('Asia/Ho_Chi_Minh')
            ?? now('Asia/Ho_Chi_Minh')->addDay()->setTime(9, 30);
        if ($defaultSlot->lessThanOrEqualTo(now('Asia/Ho_Chi_Minh'))) {
            $defaultSlot = now('Asia/Ho_Chi_Minh')->addDay()->setTime(9, 30);
        }
        $initialDate = old('new_date', $defaultSlot->format('Y-m-d'));
        $initialTime = old('new_time', $defaultSlot->format('H:i'));
        if (! in_array($initialTime, $showroomSlots, true)) {
            $initialTime = '09:30';
        }

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
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
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
                    <span class="inline-flex items-center whitespace-nowrap rounded-full border px-3.5 py-1.5 text-xs font-bold {{ $statusBg }} {{ $statusBorder }} {{ $statusText }}">
                        {{ $appointment->status->label() }}
                    </span>
                </div>

                @if(session('success'))
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm font-semibold text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm font-semibold text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Structured Summary Table --}}
                <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/70">
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
                                {{ $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'Chưa ấn định' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Chuyên viên phụ trách</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $assignedSales?->name ?? 'Đội ngũ Tư vấn BMW Showroom' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3.5 sm:grid-cols-[38%_1fr] sm:items-center">
                            <dt class="font-medium text-slate-500">Địa điểm Showroom</dt>
                            <dd class="text-slate-700">BMW Flagship Showroom, Hà Nội / TP.HCM</dd>
                        </div>
                    </dl>
                </div>

                @if(! $isLocked)
                    <div class="mt-6 space-y-3">
                        @if($guestConfirmedAtFormatted)
                            <div class="inline-flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-800">
                                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Đã ghi nhận xác nhận đến Showroom cho lịch hẹn {{ $appointmentTimeFormatted }} (xác nhận lúc {{ $guestConfirmedAtFormatted }}).</span>
                            </div>
                        @else
                            @if($invalidatedConfirmedAtFormatted)
                                <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-xs font-medium text-slate-600">
                                    <span class="line-through opacity-60">
                                        Đã ghi nhận xác nhận đến Showroom cho lịch hẹn {{ $invalidatedForDateLabel }} (xác nhận lúc {{ $invalidatedConfirmedAtFormatted }}).
                                    </span>
                                    <span class="ml-1.5 font-semibold text-amber-700">
                                        (Đã hết hiệu lực do thay đổi lịch hẹn)
                                    </span>
                                </div>
                            @endif

                            @if($reschedulePendingReview)
                                <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3.5 text-xs font-bold leading-relaxed text-amber-900">
                                    Yêu cầu đổi lịch sang {{ $appointment->appointment_date?->format('H:i — d/m/Y') }} đã được ghi nhận. Chuyên viên BMW Showroom sẽ kiểm tra và xác nhận lại lịch hẹn mới cho Quý khách.
                                </div>
                            @endif

                            <form method="POST" action="{{ $updateUrl }}" class="w-full sm:w-auto">
                                @csrf
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#1C69D4] px-7 py-3.5 text-xs font-bold uppercase tracking-[0.12em] text-white shadow-sm transition-colors hover:bg-[#1554AD] cursor-pointer sm:w-auto">
                                    Xác nhận chắc chắn đến Showroom
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Reschedule Card --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#1C69D4]">Đề xuất thời gian khác</p>
                <h2 class="mt-1.5 text-lg font-bold text-slate-900">Yêu cầu đổi giờ hẹn</h2>

                @if($isLocked)
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-relaxed text-slate-600">
                        Lịch hẹn này đang ở trạng thái <strong class="text-slate-900">{{ $appointment->status->label() }}</strong> nên không thể đổi giờ trực tuyến. Nếu cần hỗ trợ thêm, Quý khách vui lòng đặt một lịch hẹn mới tại Showroom BMW.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ $updateUrl }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf
                        <input type="hidden" name="action" value="reschedule">

                        <x-ui.date-time-picker
                            id="new_appointment_date"
                            theme="light"
                            date-input-name="new_date"
                            time-input-name="new_time"
                            combined-input-name="new_appointment_date"
                            :value="$initialDate . ' ' . $initialTime"
                            :slots="$showroomSlots"
                        />

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
                                placeholder="Ví dụ: Dời sang buổi chiều cuối tuần..."
                                class="mt-2 h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                            >
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-slate-900 px-6 py-3 text-xs font-bold uppercase tracking-[0.12em] text-white transition-colors hover:bg-[#1C69D4] cursor-pointer">
                            Gửi yêu cầu đổi giờ hẹn
                        </button>
                    </form>
                @endif
            </div>

            {{-- Cancel Card --}}
            @if(! $isLocked)
                <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm sm:p-8">
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
                                placeholder="Vui lòng chia sẻ nếu có thay đổi lịch trình..."
                                class="mt-2 h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                            >
                        </div>
                        <button type="submit" class="h-11 whitespace-nowrap rounded-xl border border-rose-200 bg-rose-50 px-6 text-xs font-bold uppercase tracking-[0.12em] text-rose-700 transition-colors hover:bg-rose-600 hover:text-white cursor-pointer">
                            Hủy lịch hẹn
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
