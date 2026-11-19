@php
    $isLocked = in_array(
        $appointment->status,
        [\App\Enums\AppointmentStatus::Cancelled, \App\Enums\AppointmentStatus::Completed],
        true
    );

    $timeSlots = [];
    for ($hour = 8; $hour <= 20; $hour++) {
        $timeSlots[] = sprintf('%02d:00', $hour);
        if ($hour < 20) {
            $timeSlots[] = sprintf('%02d:30', $hour);
        }
    }

    $fallbackDate = ($appointment->appointment_date && $appointment->appointment_date->copy()->startOfDay()->gt(now()->startOfDay()))
        ? $appointment->appointment_date->format('Y-m-d')
        : now()->addDay()->format('Y-m-d');

    $appointmentTime = $appointment->appointment_date?->format('H:i');
    $fallbackTime = ($appointmentTime && in_array($appointmentTime, $timeSlots, true))
        ? $appointmentTime
        : '10:00';

    $oldCombined = old('new_appointment_date');
    $parsedOld = null;
    if (is_string($oldCombined) && trim($oldCombined) !== '') {
        try {
            $parsedOld = \Illuminate\Support\Carbon::parse($oldCombined);
        } catch (\Throwable) {
            $parsedOld = null;
        }
    }

    $initialDate = (string) old('new_date', $parsedOld?->format('Y-m-d') ?? $fallbackDate);
    $initialTime = (string) old('new_time', ($parsedOld && in_array($parsedOld->format('H:i'), $timeSlots, true)) ? $parsedOld->format('H:i') : $fallbackTime);
@endphp

<x-app-layout>
    <section class="min-h-screen bg-[#050505] py-16 px-4 sm:px-6 lg:px-8 border-t border-white/10">
        <div class="mx-auto max-w-3xl space-y-8">
            <div class="rounded-none border border-white/10 bg-[#0B0F17] p-8 shadow-2xl">
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#70A7FF]">BMW Showroom Guest Concierge</p>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-white">
                        Quản lý lịch hẹn #{{ $appointment->id }}
                    </h1>
                    <span class="inline-flex whitespace-nowrap rounded-none border px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] {{ $appointment->status->color() }}">
                        {{ $appointment->status->label() }}
                    </span>
                </div>
                <p class="mt-3 text-sm text-zinc-400 leading-relaxed">
                    Quý khách có thể trực tiếp xác nhận tham dự, đề xuất đổi giờ hẹn mới hoặc hủy lịch hẹn mà không cần tạo tài khoản.
                </p>

                @if(session('success'))
                    <div class="mt-6 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-xs font-bold text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mt-6 rounded-none border border-rose-500/30 bg-rose-500/10 px-5 py-4 text-xs font-bold text-rose-300">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="mt-8 grid grid-cols-1 gap-px bg-white/10 border border-white/10 sm:grid-cols-2">
                    <div class="bg-[#050505] p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Khách hàng</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $appointment->user?->name ?? $appointment->guest_name ?? 'Quý khách' }}</p>
                        @if(filled($appointment->guest_phone))
                            <p class="mt-1 text-xs text-zinc-400">{{ $appointment->guest_phone }}</p>
                        @endif
                    </div>
                    <div class="bg-[#050505] p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Loại yêu cầu & Dòng xe</p>
                        <p class="mt-2 text-sm font-bold text-[#70A7FF]">{{ $appointment->type->label() }}</p>
                        <p class="mt-1 text-xs font-semibold text-white">{{ $appointment->product?->name ?? ($appointment->meta_data['customer_car_model'] ?? 'Dịch vụ chung Showroom') }}</p>
                    </div>
                    <div class="bg-[#050505] p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Thời gian hẹn hiện tại</p>
                        <p class="mt-2 text-base font-black text-white tabular-nums">{{ $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'Đang cập nhật' }}</p>
                    </div>
                    <div class="bg-[#050505] p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Địa điểm Showroom</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $appointment->showroom ?? ($appointment->meta_data['showroom'] ?? 'Showroom BMW Chính hãng') }}</p>
                    </div>
                </div>

                <!-- Confirm Attendance -->
                @if(! $isLocked)
                    <div class="mt-8 border-t border-white/10 pt-8">
                        @if($appointment->status === \App\Enums\AppointmentStatus::Confirmed)
                            <div class="inline-flex items-center gap-2.5 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3 text-xs font-bold text-emerald-300">
                                <span class="h-2 w-2 bg-emerald-400"></span>
                                <span>Quý khách đã xác nhận tham dự lịch hẹn này. Quý khách vẫn có thể đổi giờ hẹn hoặc hủy bên dưới nếu thay đổi kế hoạch.</span>
                            </div>
                        @else
                            <form method="POST" action="{{ $updateUrl }}">
                                @csrf
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="w-full sm:w-auto rounded-none bg-[#1C69D4] px-8 py-4 text-[11px] font-black uppercase tracking-[0.24em] text-white transition-colors hover:bg-white hover:text-black cursor-pointer">
                                    Xác nhận chắc chắn đến Showroom
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Reschedule -->
            <div class="rounded-none border border-white/10 bg-[#0B0F17] p-8">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#70A7FF]">Đề xuất thời gian khác</p>
                <h2 class="mt-2 text-lg font-black uppercase tracking-wider text-white">Yêu cầu đổi giờ hẹn</h2>

                @if($isLocked)
                    <div class="mt-6 rounded-none border border-zinc-800 bg-[#050505] px-5 py-4 text-xs leading-relaxed text-zinc-400">
                        Lịch hẹn này đang ở trạng thái <strong class="text-white">{{ $appointment->status->label() }}</strong> nên không thể đổi giờ trực tuyến. Nếu cần hỗ trợ thêm, Quý khách vui lòng đặt một lịch hẹn mới tại Showroom BMW.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ $updateUrl }}"
                        class="mt-6 space-y-5"
                        x-data="{
                            newDate: '{{ $initialDate }}',
                            newTime: '{{ $initialTime }}',
                            get combinedDateTime() {
                                return (this.newDate && this.newTime) ? (this.newDate + 'T' + this.newTime) : '';
                            }
                        }"
                    >
                        @csrf
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" id="new_appointment_date" name="new_appointment_date" value="{{ $initialDate.'T'.$initialTime }}" :value="combinedDateTime">

                        @if($errors->has('new_appointment_date') || $errors->has('new_date') || $errors->has('new_time'))
                            <div class="rounded-none border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs font-semibold text-rose-300">
                                @error('new_appointment_date')
                                    <p>{{ $message }}</p>
                                @enderror
                                @error('new_date')
                                    <p>{{ $message }}</p>
                                @enderror
                                @error('new_time')
                                    <p>{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="new_date" class="block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ngày hẹn mới</label>
                                <div class="relative mt-2 flex items-stretch">
                                    <input
                                        id="new_date"
                                        x-ref="dateInput"
                                        type="date"
                                        name="new_date"
                                        x-model="newDate"
                                        value="{{ $initialDate }}"
                                        min="{{ now()->format('Y-m-d') }}"
                                        required
                                        style="color-scheme: dark;"
                                        onclick="try { this.showPicker && this.showPicker(); } catch (e) {}"
                                        class="w-full h-11 rounded-none border border-white/15 bg-[#050505] px-4 text-xs font-semibold text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] cursor-pointer"
                                    >
                                    <button
                                        type="button"
                                        aria-label="Mở lịch chọn ngày"
                                        @click="try { $refs.dateInput.showPicker ? $refs.dateInput.showPicker() : $refs.dateInput.focus(); } catch (e) { $refs.dateInput.focus(); }"
                                        class="shrink-0 inline-flex items-center justify-center border border-l-0 border-white/15 bg-zinc-950 px-3.5 text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="new_time" class="block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Khung giờ showroom (08:00 — 20:00)</label>
                                <div class="mt-2">
                                    <x-ui.custom-select
                                        id="new_time"
                                        name="new_time"
                                        x-model="newTime"
                                        :value="$initialTime"
                                        :options="array_combine($timeSlots, $timeSlots)"
                                        class="[&_button]:h-11"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="reschedule_reason" class="block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ghi chú / Lý do đổi lịch</label>
                            <input
                                id="reschedule_reason"
                                type="text"
                                name="reason"
                                value="{{ old('reason') }}"
                                placeholder="Ví dụ: Dời sang buổi chiều cuối tuần..."
                                class="mt-2 w-full h-11 rounded-none border border-white/15 bg-[#050505] px-4 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                            >
                        </div>

                        <button type="submit" class="rounded-none border border-white/20 bg-black px-7 py-3.5 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer">
                            Gửi yêu cầu đổi giờ hẹn
                        </button>
                    </form>
                @endif
            </div>

            <!-- Cancel -->
            @if(! $isLocked)
                <div class="rounded-none border border-rose-500/20 bg-[#0B0F17] p-8">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-rose-400">Thay đổi kế hoạch</p>
                    <h2 class="mt-2 text-lg font-black uppercase tracking-wider text-white">Hủy lịch hẹn</h2>

                    <form method="POST" action="{{ $updateUrl }}" class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end">
                        @csrf
                        <input type="hidden" name="action" value="cancel">
                        <div class="flex-1">
                            <label for="cancel_reason" class="block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Lý do hủy (tùy chọn)</label>
                            <input
                                id="cancel_reason"
                                type="text"
                                name="reason"
                                placeholder="Vui lòng chia sẻ nếu có thay đổi lịch trình..."
                                class="mt-2 w-full h-11 rounded-none border border-white/15 bg-[#050505] px-4 text-xs text-white placeholder:text-zinc-600 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                            >
                        </div>
                        <button type="submit" class="h-11 whitespace-nowrap rounded-none border border-rose-500/40 bg-rose-500/15 px-7 text-[10px] font-black uppercase tracking-[0.22em] text-rose-300 transition-colors hover:bg-rose-600 hover:text-white cursor-pointer">
                            Hủy lịch hẹn
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
