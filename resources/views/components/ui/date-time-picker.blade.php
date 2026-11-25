@props([
    'id' => 'bmw-datetime-picker',
    'value' => '',
    'mode' => 'create',
    'anchorDate' => null,
    'wireModel' => null,
    'dateInputName' => null,
    'timeInputName' => null,
    'combinedInputName' => null,
    'theme' => 'dark',
    'disabled' => false,
    'placeholder' => 'Chọn ngày & khung giờ đón tiếp (08:00 – 18:00)...',
])

@php
    $nowVn = now('Asia/Ho_Chi_Minh');
    $todayVn = $nowVn->copy()->startOfDay();
    $todayStr = $nowVn->format('Y-m-d');
    $tomorrowStr = $todayVn->copy()->addDay()->format('Y-m-d');
    $currentMinutesVn = ((int) $nowVn->format('H')) * 60 + ((int) $nowVn->format('i'));

    $initialDate = '';
    $initialTime = '';
    $initialHour = '';
    $initialMinute = '';
    $rawVal = trim((string) $value);
    if ($rawVal !== '') {
        try {
            $parsed = \Illuminate\Support\Carbon::parse($rawVal, 'Asia/Ho_Chi_Minh');
            $initialDate = $parsed->format('Y-m-d');
            $initialTime = $parsed->format('H:i');
            $initialHour = $parsed->format('H');
            $initialMinute = $parsed->format('i');
        } catch (\Throwable) {
        }
    }

    $hoursList = ['08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18'];
    $minutesList = ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'];

    $normalizedMode = $mode === 'reschedule' ? 'reschedule' : 'create';
    $isLight = $theme === 'light';
    $radiusClass = $isLight ? 'rounded-lg' : 'rounded-none';
    $popoverRadiusClass = $isLight ? 'rounded-lg' : 'rounded-none';
@endphp

<div
    data-bmw-datetime-picker="{{ $id }}"
    data-picker-mode="{{ $normalizedMode }}"
    x-data="{
        todayStr: @js($todayStr),
        tomorrowStr: @js($tomorrowStr),
        currentMinutesVn: @js($currentMinutesVn),
        openMinutes: 480,
        closeMinutes: 1080,
        hoursList: @js($hoursList),
        minutesList: @js($minutesList),
        selectedDate: @js($initialDate),
        selectedTime: @js($initialTime),
        hourInput: @js($initialHour),
        minuteInput: @js($initialMinute),
        timeWarning: '',
        combinedValue: @js($initialDate !== '' && $initialTime !== '' ? $initialDate . ' ' . $initialTime : ''),
        isDisabled: @js((bool) $disabled),
        placeholderText: @js($placeholder),
        openPopover: false,
        openUpward: false,
        viewYear: @js((int) ($initialDate !== '' ? substr($initialDate, 0, 4) : $nowVn->format('Y'))),
        viewMonth: @js((int) ($initialDate !== '' ? substr($initialDate, 5, 2) : $nowVn->format('n'))),
        monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
        dayHeaders: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],

        init() {
            if (this.selectedDate !== '' && this.selectedTime !== '' && !this.isValidTimeForDate(this.selectedTime, this.selectedDate)) {
                this.selectedTime = '';
                this.hourInput = '';
                this.minuteInput = '';
            }
            this.syncCombined(false);
        },

        togglePopover(event) {
            if (this.isDisabled) {
                return;
            }
            if (!this.openPopover && event?.currentTarget) {
                const rect = event.currentTarget.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                this.openUpward = spaceBelow < 400 && rect.top > 400;
            }
            this.openPopover = !this.openPopover;
        },

        ensureDefaultDate() {
            if (this.selectedDate === '') {
                this.selectedDate = this.currentMinutesVn >= this.closeMinutes ? this.tomorrowStr : this.todayStr;
                const parts = this.selectedDate.split('-');
                if (parts.length === 3) {
                    this.viewYear = parseInt(parts[0], 10);
                    this.viewMonth = parseInt(parts[1], 10);
                }
            }
        },

        selectDate(dateStr) {
            if (this.isDisabled || this.isDateDisabled(dateStr)) {
                return;
            }
            this.selectedDate = dateStr;
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                this.viewYear = parseInt(parts[0], 10);
                this.viewMonth = parseInt(parts[1], 10);
            }

            if (this.selectedTime !== '' && !this.isValidTimeForDate(this.selectedTime, this.selectedDate)) {
                this.selectedTime = '';
                this.hourInput = '';
                this.minuteInput = '';
                this.timeWarning = 'Khung giờ vừa chọn đã qua trong ngày hôm nay. Vui lòng chọn giờ tiếp theo.';
            } else {
                this.timeWarning = '';
            }

            this.syncCombined(true);
        },

        pickHour(hourStr) {
            if (this.isDisabled || this.isHourDisabled(hourStr)) {
                return;
            }
            this.ensureDefaultDate();
            this.hourInput = hourStr;

            let targetMinute = this.minuteInput !== '' ? this.minuteInput.padStart(2, '0') : '00';
            if (hourStr === '18') {
                targetMinute = '00';
            } else if (this.isMinuteDisabledForHour(hourStr, targetMinute)) {
                const firstAvailable = this.minutesList.find((m) => !this.isMinuteDisabledForHour(hourStr, m));
                targetMinute = firstAvailable || '00';
            }
            this.minuteInput = targetMinute;
            this.commitDigitalTime(true);
        },

        pickMinute(minStr) {
            if (this.isDisabled || this.isMinuteDisabled(minStr)) {
                return;
            }
            this.ensureDefaultDate();
            if (this.hourInput === '' || this.isHourDisabled(this.hourInput.padStart(2, '0'))) {
                const firstValidHour = this.hoursList.find((h) => !this.isHourDisabled(h) && !this.isMinuteDisabledForHour(h, minStr));
                if (!firstValidHour) {
                    return;
                }
                this.hourInput = firstValidHour;
            }
            this.minuteInput = minStr;
            this.commitDigitalTime(true);
        },

        onHourInput(raw) {
            this.hourInput = String(raw).replace(/\D/g, '').slice(0, 2);
            if (this.hourInput.length === 2 && this.minuteInput.length === 0) {
                this.minuteInput = '00';
            }
            this.commitDigitalTime(false);
        },

        onMinuteInput(raw) {
            this.minuteInput = String(raw).replace(/\D/g, '').slice(0, 2);
            this.commitDigitalTime(false);
        },

        normalizeDigitalBlur() {
            if (this.hourInput !== '') {
                this.hourInput = this.hourInput.padStart(2, '0');
            }
            if (this.minuteInput !== '') {
                this.minuteInput = this.minuteInput.padStart(2, '0');
            } else if (this.hourInput !== '') {
                this.minuteInput = '00';
            }
            this.commitDigitalTime(true);
        },

        commitDigitalTime(strict = false) {
            this.timeWarning = '';

            if (this.hourInput === '' && this.minuteInput === '') {
                this.selectedTime = '';
                this.syncCombined(true);
                return false;
            }

            if (!strict && (this.hourInput.length < 2 || this.minuteInput.length < 2)) {
                return false;
            }

            const h = parseInt(this.hourInput, 10);
            const m = parseInt(this.minuteInput, 10);

            if (Number.isNaN(h) || Number.isNaN(m) || h < 0 || h > 23 || m < 0 || m > 59) {
                this.timeWarning = 'Giờ không hợp lệ. Vui lòng nhập theo định dạng 08:00 – 18:00.';
                return false;
            }

            const totalMin = h * 60 + m;
            if (totalMin < this.openMinutes || totalMin > this.closeMinutes) {
                this.timeWarning = 'Showroom BMW đón tiếp từ 08:00 đến 18:00.';
                return false;
            }

            this.ensureDefaultDate();

            if (this.selectedDate === this.todayStr && totalMin <= this.currentMinutesVn) {
                this.timeWarning = 'Giờ hẹn hôm nay phải lớn hơn thời điểm hiện tại.';
                return false;
            }

            const hPad = String(h).padStart(2, '0');
            const mPad = String(m).padStart(2, '0');
            this.hourInput = hPad;
            this.minuteInput = mPad;
            this.selectedTime = `${hPad}:${mPad}`;
            this.syncCombined(true);
            return true;
        },

        applyAndClose() {
            if (this.hourInput !== '' || this.minuteInput !== '') {
                const ok = this.commitDigitalTime(true);
                if (!ok) {
                    return;
                }
            }
            this.openPopover = false;
        },

        timeToMinutes(timeStr) {
            const [h, m] = String(timeStr).split(':').map((n) => parseInt(n, 10));
            if (Number.isNaN(h) || Number.isNaN(m)) {
                return -1;
            }
            return h * 60 + m;
        },

        isValidTimeForDate(timeStr, dateStr) {
            const totalMin = this.timeToMinutes(timeStr);
            if (totalMin < this.openMinutes || totalMin > this.closeMinutes) {
                return false;
            }
            if (dateStr < this.todayStr) {
                return false;
            }
            if (dateStr === this.todayStr && totalMin <= this.currentMinutesVn) {
                return false;
            }
            return true;
        },

        isDateDisabled(dateStr) {
            return String(dateStr) < String(this.todayStr);
        },

        isHourDisabled(hourStr) {
            if (this.selectedDate !== '' && this.selectedDate < this.todayStr) {
                return true;
            }
            const h = parseInt(hourStr, 10);
            if (this.selectedDate === this.todayStr) {
                const maxMinuteInHour = h === 18 ? 0 : 59;
                return (h * 60 + maxMinuteInHour) <= this.currentMinutesVn;
            }
            return false;
        },

        isMinuteDisabledForHour(hourStr, minStr) {
            const h = parseInt(hourStr, 10);
            const m = parseInt(minStr, 10);
            if (Number.isNaN(h) || Number.isNaN(m)) {
                return false;
            }
            const total = h * 60 + m;
            if (total < this.openMinutes || total > this.closeMinutes) {
                return true;
            }
            if (this.selectedDate === this.todayStr && total <= this.currentMinutesVn) {
                return true;
            }
            return false;
        },

        isMinuteDisabled(minStr) {
            if (this.hourInput === '' || this.hourInput.length < 2) {
                return false;
            }
            return this.isMinuteDisabledForHour(this.hourInput, minStr);
        },

        syncCombined(notifyWire = true) {
            this.combinedValue = (this.selectedDate !== '' && this.selectedTime !== '')
                ? `${this.selectedDate} ${this.selectedTime}`
                : '';

            this.$dispatch('bmw-datetime-changed', {
                id: @js($id),
                date: this.selectedDate,
                time: this.selectedTime,
                combined: this.combinedValue,
                formattedDate: this.formattedShortDate(),
                fullDateLabel: this.formattedSelectedDate(),
            });

            @if($wireModel)
            if (notifyWire && typeof $wire !== 'undefined') {
                $wire.set(@js($wireModel), this.combinedValue);
            }
            @endif
        },

        formattedShortDate() {
            if (!this.selectedDate) {
                return '';
            }
            const parts = this.selectedDate.split('-');
            if (parts.length !== 3) {
                return this.selectedDate;
            }
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        prevMonth() {
            if (this.viewMonth === 1) {
                this.viewMonth = 12;
                this.viewYear--;
            } else {
                this.viewMonth--;
            }
        },

        nextMonth() {
            if (this.viewMonth === 12) {
                this.viewMonth = 1;
                this.viewYear++;
            } else {
                this.viewMonth++;
            }
        },

        calendarDays() {
            const firstDay = new Date(this.viewYear, this.viewMonth - 1, 1);
            const daysInMonth = new Date(this.viewYear, this.viewMonth, 0).getDate();
            let startOffset = firstDay.getDay() - 1;
            if (startOffset < 0) {
                startOffset = 6;
            }

            const cells = [];
            for (let i = 0; i < startOffset; i++) {
                cells.push({ empty: true, key: `empty-${i}` });
            }

            const mStr = String(this.viewMonth).padStart(2, '0');
            for (let d = 1; d <= daysInMonth; d++) {
                const dStr = String(d).padStart(2, '0');
                const fullDate = `${this.viewYear}-${mStr}-${dStr}`;
                cells.push({
                    empty: false,
                    key: fullDate,
                    day: d,
                    dateStr: fullDate,
                    disabled: this.isDateDisabled(fullDate),
                    isToday: fullDate === this.todayStr,
                    isSelected: fullDate === this.selectedDate,
                });
            }

            return cells;
        },

        formattedSelectedDate() {
            if (!this.selectedDate) {
                return '';
            }
            const parts = this.selectedDate.split('-');
            if (parts.length !== 3) {
                return this.selectedDate;
            }
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10);
            const d = parseInt(parts[2], 10);
            const dateObj = new Date(y, m - 1, d);
            const weekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
            const weekday = weekdays[dateObj.getDay()] || '';
            const dd = String(d).padStart(2, '0');
            const mm = String(m).padStart(2, '0');

            return `${weekday}, ${dd}/${mm}/${y}`;
        },

        formattedTriggerLabel() {
            if (!this.selectedDate && !this.selectedTime) {
                return this.placeholderText;
            }
            const dateLabel = this.formattedSelectedDate();
            if (this.selectedDate && this.selectedTime) {
                return `${dateLabel} · ${this.selectedTime}`;
            }
            if (this.selectedDate) {
                return `${dateLabel} · Chọn giờ...`;
            }
            return this.placeholderText;
        }
    }"
    @keydown.escape.window="openPopover = false"
    :class="openPopover ? 'relative z-40' : 'relative'"
>
    @if($combinedInputName || $wireModel)
        <input
            type="hidden"
            id="{{ $id }}"
            @if($combinedInputName) name="{{ $combinedInputName }}" @endif
            @if($wireModel) wire:model.live="{{ $wireModel }}" @endif
            :value="combinedValue"
        >
    @endif

    @if($dateInputName)
        <input type="hidden" id="{{ $dateInputName }}" name="{{ $dateInputName }}" :value="selectedDate">
    @endif

    @if($timeInputName)
        <input type="hidden" id="{{ $timeInputName }}" name="{{ $timeInputName }}" :value="selectedTime">
    @endif

    <div class="relative" :class="openPopover ? 'z-[95]' : 'z-10'" @click.outside="openPopover = false">
        <button
            type="button"
            data-picker-trigger="datetime"
            :disabled="isDisabled"
            @click="togglePopover($event)"
            :aria-expanded="openPopover ? 'true' : 'false'"
            class="flex h-11 w-full items-center justify-between {{ $radiusClass }} border px-4 text-left text-sm font-semibold transition-colors {{ $isLight ? 'border-slate-300 bg-white text-slate-900 hover:border-[#1C69D4] focus:border-[#1C69D4]' : 'border-white/15 bg-[#0D1117] text-white hover:border-[#1C69D4] focus:border-[#1C69D4]' }} {{ $disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}"
        >
            <span class="flex min-w-0 items-center gap-2.5">
                <svg class="h-4 w-4 shrink-0 text-[#1C69D4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span
                    class="truncate tabular-nums"
                    x-text="formattedTriggerLabel()"
                    :class="(selectedDate && selectedTime) ? '' : '{{ $isLight ? 'text-slate-400 font-medium' : 'text-zinc-500 font-medium' }}'"
                ></span>
            </span>

            <span class="ml-3 flex shrink-0 items-center gap-1.5 text-xs font-bold text-[#1C69D4]">
                <span class="hidden sm:inline" x-text="openPopover ? 'Đóng' : 'Chọn lịch'"></span>
                <svg
                    class="h-4 w-4 transition-transform duration-150"
                    :class="openPopover ? 'rotate-180' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </button>

        {{-- Split Popover: Cột trái Lịch tháng | Cột phải Đồng hồ số HH:MM + 2 cột cuộn Giờ | Phút --}}
        <div
            x-show="openPopover"
            x-cloak
            data-picker-popover="split"
            :class="openUpward ? 'bottom-full mb-2' : 'top-full mt-2'"
            style="{{ $isLight ? 'background-color: #ffffff;' : 'background-color: #090D14;' }}"
            class="absolute left-0 right-0 z-[95] isolate {{ $popoverRadiusClass }} border p-4 shadow-[0_24px_60px_rgba(0,0,0,0.9)] {{ $isLight ? 'border-slate-200 bg-white text-slate-900' : 'border-white/20 bg-[#090D14] text-white' }}"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1.35fr_1fr]">
                {{-- Cột trái: Lịch tháng Tiếng Việt --}}
                <div class="{{ $isLight ? 'md:border-r md:border-slate-200 md:pr-4' : 'md:border-r md:border-white/10 md:pr-4' }}">
                    <div class="mb-3 flex items-center justify-between">
                        <button
                            type="button"
                            @click="prevMonth()"
                            aria-label="Tháng trước"
                            class="{{ $radiusClass }} border px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer {{ $isLight ? 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' : 'border-white/15 bg-zinc-900 text-zinc-300 hover:border-white hover:text-white' }}"
                        >
                            ‹
                        </button>
                        <div class="text-xs font-bold uppercase tracking-wider">
                            <span x-text="monthNames[viewMonth - 1]"></span>
                            <span x-text="viewYear"></span>
                        </div>
                        <button
                            type="button"
                            @click="nextMonth()"
                            aria-label="Tháng sau"
                            class="{{ $radiusClass }} border px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer {{ $isLight ? 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' : 'border-white/15 bg-zinc-900 text-zinc-300 hover:border-white hover:text-white' }}"
                        >
                            ›
                        </button>
                    </div>

                    <div class="mb-1.5 grid grid-cols-7 gap-1 text-center text-[11px] font-bold {{ $isLight ? 'text-slate-500' : 'text-zinc-500' }}">
                        <template x-for="dayLabel in dayHeaders" :key="dayLabel">
                            <div class="py-1" x-text="dayLabel"></div>
                        </template>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="cell in calendarDays()" :key="cell.key">
                            <div>
                                <template x-if="!cell.empty">
                                    <button
                                        type="button"
                                        :disabled="cell.disabled"
                                        @click="selectDate(cell.dateStr)"
                                        :class="[
                                            cell.isSelected
                                                ? 'border-[#1C69D4] bg-[#1C69D4] text-white font-bold'
                                                : (cell.disabled
                                                    ? '{{ $isLight ? 'border-transparent text-slate-300 cursor-not-allowed' : 'border-transparent text-zinc-700 cursor-not-allowed' }}'
                                                    : '{{ $isLight ? 'border-slate-100 bg-slate-50 text-slate-800 hover:border-[#1C69D4] hover:bg-blue-50 cursor-pointer' : 'border-white/10 bg-zinc-950 text-zinc-200 hover:border-[#1C69D4] hover:text-white cursor-pointer' }}'),
                                            cell.isToday && !cell.isSelected ? 'ring-1 ring-[#1C69D4]' : ''
                                        ]"
                                        class="flex h-9 w-full items-center justify-center {{ $radiusClass }} border text-xs tabular-nums transition-colors"
                                        x-text="cell.day"
                                    ></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Cột phải: Ô đồng hồ số gõ tay HH:MM + 2 cột cuộn độc lập Giờ | Phút --}}
                <div class="flex flex-col">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider {{ $isLight ? 'text-slate-500' : 'text-zinc-400' }}">
                            Giờ đón tiếp (08:00 – 18:00)
                        </span>
                    </div>

                    {{-- Ô đồng hồ số HH : MM --}}
                    <div class="mb-3 flex items-center justify-center gap-2 {{ $radiusClass }} border p-2 {{ $isLight ? 'border-slate-200 bg-slate-50' : 'border-white/15 bg-zinc-950' }}">
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="2"
                            aria-label="Nhập giờ (08 đến 18)"
                            data-time-digital-hour
                            placeholder="09"
                            :value="hourInput"
                            @input="onHourInput($event.target.value)"
                            @blur="normalizeDigitalBlur()"
                            @keydown.enter.prevent="applyAndClose()"
                            class="h-10 w-14 {{ $radiusClass }} border text-center text-base font-extrabold tabular-nums focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4] {{ $isLight ? 'border-slate-300 bg-white text-slate-900 placeholder:text-slate-400' : 'border-white/15 bg-[#0D1117] text-white placeholder:text-zinc-600' }}"
                        >
                        <span class="text-base font-black {{ $isLight ? 'text-slate-500' : 'text-zinc-400' }}">:</span>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="2"
                            aria-label="Nhập phút (00 đến 59)"
                            data-time-digital-minute
                            placeholder="30"
                            :value="minuteInput"
                            @input="onMinuteInput($event.target.value)"
                            @blur="normalizeDigitalBlur()"
                            @keydown.enter.prevent="applyAndClose()"
                            class="h-10 w-14 {{ $radiusClass }} border text-center text-base font-extrabold tabular-nums focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4] {{ $isLight ? 'border-slate-300 bg-white text-slate-900 placeholder:text-slate-400' : 'border-white/15 bg-[#0D1117] text-white placeholder:text-zinc-600' }}"
                        >
                    </div>

                    <p
                        x-show="timeWarning !== ''"
                        x-cloak
                        data-time-warning
                        class="mb-2.5 text-[11px] font-semibold leading-snug {{ $isLight ? 'text-rose-600' : 'text-rose-400' }}"
                        x-text="timeWarning"
                    ></p>

                    {{-- 2 cột cuộn độc lập: Giờ | Phút --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="mb-1 text-center text-[10px] font-bold uppercase tracking-wider {{ $isLight ? 'text-slate-500' : 'text-zinc-500' }}">
                                Giờ
                            </div>
                            <div
                                data-wheel-hours
                                class="sidebar-scroll max-h-44 space-y-1 overflow-y-auto overscroll-contain {{ $radiusClass }} border p-1 {{ $isLight ? 'border-slate-200 bg-slate-50/70' : 'border-white/10 bg-zinc-950/80' }}"
                            >
                                <template x-for="h in hoursList" :key="'h-' + h">
                                    <button
                                        type="button"
                                        :data-hour-option="h"
                                        :disabled="isHourDisabled(h)"
                                        @click="pickHour(h)"
                                        :class="hourInput === h
                                            ? 'bg-[#1C69D4] text-white font-bold'
                                            : (isHourDisabled(h)
                                                ? '{{ $isLight ? 'text-slate-300 line-through cursor-not-allowed' : 'text-zinc-700 line-through cursor-not-allowed' }}'
                                                : '{{ $isLight ? 'text-slate-700 hover:bg-blue-50 hover:text-[#1C69D4] cursor-pointer' : 'text-zinc-300 hover:bg-white/10 hover:text-white cursor-pointer' }}')"
                                        class="flex h-8 w-full items-center justify-center {{ $radiusClass }} text-xs font-semibold tabular-nums transition-colors"
                                        x-text="h"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <div class="mb-1 text-center text-[10px] font-bold uppercase tracking-wider {{ $isLight ? 'text-slate-500' : 'text-zinc-500' }}">
                                Phút
                            </div>
                            <div
                                data-wheel-minutes
                                class="sidebar-scroll max-h-44 space-y-1 overflow-y-auto overscroll-contain {{ $radiusClass }} border p-1 {{ $isLight ? 'border-slate-200 bg-slate-50/70' : 'border-white/10 bg-zinc-950/80' }}"
                            >
                                <template x-for="m in minutesList" :key="'m-' + m">
                                    <button
                                        type="button"
                                        :data-minute-option="m"
                                        :disabled="isMinuteDisabled(m)"
                                        @click="pickMinute(m)"
                                        :class="minuteInput === m
                                            ? 'bg-[#1C69D4] text-white font-bold'
                                            : (isMinuteDisabled(m)
                                                ? '{{ $isLight ? 'text-slate-300 line-through cursor-not-allowed' : 'text-zinc-700 line-through cursor-not-allowed' }}'
                                                : '{{ $isLight ? 'text-slate-700 hover:bg-blue-50 hover:text-[#1C69D4] cursor-pointer' : 'text-zinc-300 hover:bg-white/10 hover:text-white cursor-pointer' }}')"
                                        class="flex h-8 w-full items-center justify-center {{ $radiusClass }} text-xs font-semibold tabular-nums transition-colors"
                                        x-text="m"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Thanh chân Popover: tóm tắt ngày giờ đầy đủ + nút Áp dụng --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t pt-3 {{ $isLight ? 'border-slate-200' : 'border-white/10' }}">
                <span
                    class="text-xs font-semibold tabular-nums {{ $isLight ? 'text-slate-600' : 'text-zinc-300' }}"
                    x-text="(selectedDate && selectedTime) ? `${formattedSelectedDate()} · ${selectedTime}` : 'Vui lòng chọn đủ ngày & giờ (08:00 – 18:00)'"
                ></span>
                <button
                    type="button"
                    data-picker-apply
                    @click="applyAndClose()"
                    class="inline-flex h-8 items-center justify-center {{ $radiusClass }} bg-[#1C69D4] px-4 text-xs font-bold text-white transition-colors hover:bg-[#1557B0] cursor-pointer"
                >
                    Áp dụng
                </button>
            </div>
        </div>
    </div>
</div>
