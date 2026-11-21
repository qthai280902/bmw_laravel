@props([
    'id' => 'bmw-datetime-picker',
    'value' => '',
    'wireModel' => null,
    'dateInputName' => null,
    'timeInputName' => null,
    'combinedInputName' => null,
    'theme' => 'dark',
    'slots' => ['08:30', '09:30', '10:30', '11:30', '13:30', '14:30', '15:30', '16:30', '17:30'],
])

@php
    $nowVn = now('Asia/Ho_Chi_Minh');
    $todayStr = $nowVn->format('Y-m-d');
    $tomorrowStr = $nowVn->copy()->addDay()->format('Y-m-d');
    $dayAfterStr = $nowVn->copy()->addDays(2)->format('Y-m-d');
    $currentMinutesVn = ((int) $nowVn->format('H')) * 60 + ((int) $nowVn->format('i'));

    $initialDate = '';
    $initialTime = '';
    $rawVal = trim((string) $value);
    if ($rawVal !== '') {
        try {
            $parsed = \Illuminate\Support\Carbon::parse($rawVal);
            $initialDate = $parsed->format('Y-m-d');
            $initialTime = $parsed->format('H:i');
        } catch (\Throwable) {
        }
    }

    $isLight = $theme === 'light';
    $radiusClass = $isLight ? 'rounded-xl' : 'rounded-none';
    $popoverRadiusClass = $isLight ? 'rounded-2xl' : 'rounded-none';
@endphp

<div
    data-bmw-datetime-picker="{{ $id }}"
    x-data="{
        todayStr: @js($todayStr),
        tomorrowStr: @js($tomorrowStr),
        dayAfterStr: @js($dayAfterStr),
        currentMinutesVn: @js($currentMinutesVn),
        slots: @js(array_values($slots)),
        selectedDate: @js($initialDate),
        selectedTime: @js($initialTime),
        combinedValue: @js($initialDate !== '' && $initialTime !== '' ? $initialDate . ' ' . $initialTime : ''),
        openCalendar: false,
        openUpward: false,
        viewYear: @js((int) ($initialDate !== '' ? substr($initialDate, 0, 4) : $nowVn->format('Y'))),
        viewMonth: @js((int) ($initialDate !== '' ? substr($initialDate, 5, 2) : $nowVn->format('n'))),
        monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
        dayHeaders: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],

        init() {
            if (this.selectedDate !== '' && this.selectedTime !== '' && this.isSlotDisabled(this.selectedTime)) {
                this.selectedTime = '';
            }
            this.syncCombined(false);
        },

        toggleCalendar(event) {
            if (!this.openCalendar && event?.currentTarget) {
                const rect = event.currentTarget.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                this.openUpward = spaceBelow < 340 && rect.top > 340;
            }
            this.openCalendar = !this.openCalendar;
        },

        selectDate(dateStr) {
            if (this.isDateDisabled(dateStr)) {
                return;
            }
            this.selectedDate = dateStr;
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                this.viewYear = parseInt(parts[0], 10);
                this.viewMonth = parseInt(parts[1], 10);
            }
            this.openCalendar = false;

            if (this.selectedTime !== '' && this.isSlotDisabled(this.selectedTime)) {
                this.selectedTime = '';
            }

            this.syncCombined(true);
        },

        selectTime(slot) {
            if (this.isSlotDisabled(slot)) {
                return;
            }
            if (this.selectedDate === '') {
                this.selectedDate = this.allTodaySlotsPassed() ? this.tomorrowStr : this.todayStr;
            }
            this.selectedTime = slot;
            this.syncCombined(true);
        },

        allTodaySlotsPassed() {
            return this.slots.every((s) => this.slotToMinutes(s) <= this.currentMinutesVn);
        },

        slotToMinutes(slot) {
            const [h, m] = String(slot).split(':').map((n) => parseInt(n, 10) || 0);
            return h * 60 + m;
        },

        isDateDisabled(dateStr) {
            return String(dateStr) < String(this.todayStr);
        },

        isSlotDisabled(slot) {
            if (this.selectedDate === '') {
                return false;
            }
            if (this.selectedDate < this.todayStr) {
                return true;
            }
            if (this.selectedDate === this.todayStr) {
                return this.slotToMinutes(slot) <= this.currentMinutesVn;
            }
            return false;
        },

        syncCombined(notifyWire = true) {
            this.combinedValue = (this.selectedDate !== '' && this.selectedTime !== '')
                ? `${this.selectedDate} ${this.selectedTime}`
                : '';

            @if($wireModel)
            if (notifyWire && typeof $wire !== 'undefined') {
                $wire.set(@js($wireModel), this.combinedValue);
            }
            @endif
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
                return 'Chọn ngày hẹn tại Showroom...';
            }
            const parts = this.selectedDate.split('-');
            if (parts.length !== 3) {
                return this.selectedDate;
            }
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10);
            const d = parseInt(parts[2], 10);
            const dateObj = new Date(y, m - 1, d);
            const weekdays = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
            const weekday = weekdays[dateObj.getDay()] || '';
            const dd = String(d).padStart(2, '0');
            const mm = String(m).padStart(2, '0');

            return `${weekday}, ${dd}/${mm}/${y}`;
        }
    }"
    class="space-y-4"
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

    {{-- Bước 1: Chọn Ngày Hẹn --}}
    <div class="space-y-2.5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="{{ $isLight ? 'text-xs font-semibold text-slate-600' : 'text-[10px] font-black uppercase tracking-[0.22em] text-zinc-400' }}">
                Bước 1 · Chọn ngày hẹn
            </span>

            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Chọn nhanh ngày hẹn">
                <button
                    type="button"
                    data-quick-date="today"
                    @click="selectDate(todayStr)"
                    :class="selectedDate === todayStr
                        ? '{{ $isLight ? 'border-[#1C69D4] bg-[#1C69D4] text-white' : 'border-[#1C69D4] bg-[#1C69D4] text-white' }}'
                        : '{{ $isLight ? 'border-slate-200 bg-white text-slate-700 hover:border-[#1C69D4]' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-[#1C69D4] hover:text-white' }}'"
                    class="{{ $radiusClass }} border px-3 py-1.5 text-[11px] font-bold transition-colors cursor-pointer"
                >
                    Hôm nay
                </button>
                <button
                    type="button"
                    data-quick-date="tomorrow"
                    @click="selectDate(tomorrowStr)"
                    :class="selectedDate === tomorrowStr
                        ? '{{ $isLight ? 'border-[#1C69D4] bg-[#1C69D4] text-white' : 'border-[#1C69D4] bg-[#1C69D4] text-white' }}'
                        : '{{ $isLight ? 'border-slate-200 bg-white text-slate-700 hover:border-[#1C69D4]' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-[#1C69D4] hover:text-white' }}'"
                    class="{{ $radiusClass }} border px-3 py-1.5 text-[11px] font-bold transition-colors cursor-pointer"
                >
                    Ngày mai
                </button>
                <button
                    type="button"
                    data-quick-date="day_after"
                    @click="selectDate(dayAfterStr)"
                    :class="selectedDate === dayAfterStr
                        ? '{{ $isLight ? 'border-[#1C69D4] bg-[#1C69D4] text-white' : 'border-[#1C69D4] bg-[#1C69D4] text-white' }}'
                        : '{{ $isLight ? 'border-slate-200 bg-white text-slate-700 hover:border-[#1C69D4]' : 'border-zinc-800 bg-zinc-950 text-zinc-300 hover:border-[#1C69D4] hover:text-white' }}'"
                    class="{{ $radiusClass }} border px-3 py-1.5 text-[11px] font-bold transition-colors cursor-pointer"
                >
                    Ngày kia
                </button>
            </div>
        </div>

        <div class="relative" @click.outside="openCalendar = false">
            <button
                type="button"
                data-picker-trigger="calendar"
                @click="toggleCalendar($event)"
                class="flex w-full items-center justify-between {{ $radiusClass }} border px-4 py-3.5 text-left text-sm font-semibold transition-colors cursor-pointer {{ $isLight ? 'border-slate-300 bg-slate-50 text-slate-900 hover:border-[#1C69D4] focus:border-[#1C69D4]' : 'border-zinc-800 bg-zinc-950 text-white hover:border-[#1C69D4] focus:border-[#1C69D4]' }}"
            >
                <span class="flex items-center gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-[#1C69D4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-text="formattedSelectedDate()" :class="selectedDate ? '' : '{{ $isLight ? 'text-slate-400' : 'text-zinc-500' }}'"></span>
                </span>
                <span class="text-xs font-bold text-[#1C69D4]" x-text="openCalendar ? 'Đóng lịch' : 'Mở lịch'"></span>
            </button>

            <div
                x-show="openCalendar"
                x-cloak
                x-transition.opacity.duration.120ms
                :class="openUpward ? 'bottom-full mb-2' : 'top-full mt-2'"
                class="absolute left-0 right-0 z-50 {{ $popoverRadiusClass }} border p-4 shadow-2xl {{ $isLight ? 'border-slate-200 bg-white text-slate-900' : 'border-zinc-800 bg-[#0B0F17] text-white' }}"
            >
                <div class="mb-3 flex items-center justify-between">
                    <button
                        type="button"
                        @click="prevMonth()"
                        aria-label="Tháng trước"
                        class="{{ $radiusClass }} border px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer {{ $isLight ? 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300 hover:border-white hover:text-white' }}"
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
                        class="{{ $radiusClass }} border px-2.5 py-1 text-xs font-bold transition-colors cursor-pointer {{ $isLight ? 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300 hover:border-white hover:text-white' }}"
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
                                                : '{{ $isLight ? 'border-slate-100 bg-slate-50 text-slate-800 hover:border-[#1C69D4] hover:bg-blue-50 cursor-pointer' : 'border-zinc-900 bg-zinc-950 text-zinc-200 hover:border-[#1C69D4] hover:text-white cursor-pointer' }}'),
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
        </div>
    </div>

    {{-- Bước 2: Chọn Khung Giờ Showroom --}}
    <div class="space-y-2.5">
        <div class="flex items-center justify-between gap-2">
            <span class="{{ $isLight ? 'text-xs font-semibold text-slate-600' : 'text-[10px] font-black uppercase tracking-[0.22em] text-zinc-400' }}">
                Bước 2 · Chọn khung giờ đón tiếp (08:30 – 17:30)
            </span>
            <span
                x-show="selectedTime !== ''"
                x-cloak
                class="text-xs font-bold tabular-nums text-[#1C69D4]"
                x-text="`Đã chọn: ${selectedTime}`"
            ></span>
        </div>

        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5" role="group" aria-label="Khung giờ đón tiếp tại Showroom">
            @foreach($slots as $slot)
                <button
                    type="button"
                    data-time-slot="{{ $slot }}"
                    :disabled="isSlotDisabled('{{ $slot }}')"
                    @click="selectTime('{{ $slot }}')"
                    :class="selectedTime === '{{ $slot }}'
                        ? 'border-[#1C69D4] bg-[#1C69D4] text-white shadow-sm'
                        : (isSlotDisabled('{{ $slot }}')
                            ? '{{ $isLight ? 'border-slate-200 bg-slate-100 text-slate-400 line-through opacity-50 cursor-not-allowed' : 'border-zinc-900 bg-zinc-950/50 text-zinc-700 line-through opacity-45 cursor-not-allowed' }}'
                            : '{{ $isLight ? 'border-slate-300 bg-white text-slate-800 hover:border-[#1C69D4] hover:text-[#1C69D4] cursor-pointer' : 'border-zinc-800 bg-zinc-950 text-zinc-200 hover:border-[#1C69D4] hover:text-white cursor-pointer' }}')"
                    class="{{ $radiusClass }} border px-3 py-2.5 text-center text-xs font-bold tabular-nums transition-colors"
                >
                    {{ $slot }}
                </button>
            @endforeach
        </div>
    </div>
</div>
