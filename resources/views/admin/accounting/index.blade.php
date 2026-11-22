<x-admin-layout>
    <x-admin.page-header
        eyebrow="Tài chính & Đối soát nội bộ · Chỉ Super Admin"
        title="Kế Toán &"
        accent="Sổ Sách"
        :description="'Kỳ đối soát: ' . $periodLabel . ' · Quản lý chứng từ (CT-YYMMDD-001), Gói ngày bán hàng và xuất sổ CSV.'"
    >
        <x-slot name="actions">
            <a
                href="{{ route('admin.accounting.export', request()->only(['period', 'module', 'status', 'date_from', 'date_to'])) }}"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-emerald-500"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Xuất sổ kế toán (.CSV)</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    @php
        $periodOptions = [
            'today' => 'Hôm nay',
            '7d' => '7 ngày gần nhất',
            'this_month' => 'Tháng này',
            'this_quarter' => 'Quý này',
            'custom' => 'Tùy chọn Từ ngày – Đến ngày',
        ];
        $moduleOptions = [
            'all' => 'Tất cả phân hệ',
            'accessories' => 'Đơn phụ kiện',
            'services' => 'Dịch vụ chăm sóc xe',
            'vehicles' => 'Lịch hẹn xe / Đặt cọc',
        ];
        $statusOptions = [
            'all' => 'Tất cả trạng thái',
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn thành (Đã thu)',
            'cancelled' => 'Đã hủy (Thất thoát)',
        ];
    @endphp

    {{-- Bộ lọc kỳ kế toán & phân hệ --}}
    <x-admin.card class="mb-6">
        <form
            method="GET"
            action="{{ route('admin.accounting.index') }}"
            x-data="{ period: @js($period) }"
            class="space-y-4"
        >
            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-12 md:items-end">
                <div class="md:col-span-3">
                    <x-admin.form-field name="period" label="Kỳ kế toán">
                        <select id="period" name="period" x-model="period" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                            @foreach($periodOptions as $val => $label)
                                <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-admin.form-field>
                </div>

                <div class="md:col-span-3">
                    <x-admin.form-field name="module" label="Phân hệ chứng từ">
                        <select id="module" name="module" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                            @foreach($moduleOptions as $val => $label)
                                <option value="{{ $val }}" @selected($module === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-admin.form-field>
                </div>

                <div class="md:col-span-3">
                    <x-admin.form-field name="status" label="Trạng thái hạch toán">
                        <select id="status" name="status" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                            @foreach($statusOptions as $val => $label)
                                <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-admin.form-field>
                </div>

                <div class="flex items-center justify-end gap-2 md:col-span-3">
                    <button type="submit" class="h-10 whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer">
                        Lọc sổ sách
                    </button>
                    <a href="{{ route('admin.accounting.index') }}" class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-xl border border-white/10 bg-[#131A29] px-4 text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white">
                        Đặt lại
                    </a>
                </div>
            </div>

            <div
                x-show="period === 'custom'"
                x-cloak
                class="grid grid-cols-1 gap-3.5 border-t border-white/[0.06] pt-3.5 sm:grid-cols-2"
            >
                <x-admin.form-field name="date_from" label="Từ ngày">
                    <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}" :disabled="period !== 'custom'" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                </x-admin.form-field>
                <x-admin.form-field name="date_to" label="Đến ngày">
                    <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}" :disabled="period !== 'custom'" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                </x-admin.form-field>
            </div>
        </form>
    </x-admin.card>

    {{-- 5 Chỉ số KPI Tài chính (Clean Executive Cards) --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5 shadow-lg">
            <p class="flex items-center gap-2 text-xs font-semibold text-zinc-400">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span>Doanh thu phụ kiện thực thu</span>
            </p>
            <p class="mt-2.5 text-xl font-bold tabular-nums text-white">{{ number_format($kpis['completed_accessory_revenue'], 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-[11px] text-zinc-500">Đơn phụ kiện đã hoàn thành</p>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5 shadow-lg">
            <p class="flex items-center gap-2 text-xs font-semibold text-zinc-400">
                <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                <span>Giá trị phụ kiện đang xử lý</span>
            </p>
            <p class="mt-2.5 text-xl font-bold tabular-nums text-white">{{ number_format($kpis['processing_accessory_value'], 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-[11px] text-zinc-500">Chờ duyệt &amp; Đã xác nhận</p>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5 shadow-lg">
            <p class="flex items-center gap-2 text-xs font-semibold text-zinc-400">
                <span class="h-2 w-2 rounded-full bg-[#1C69D4]"></span>
                <span>Giá trị đặt cọc xe tiềm năng</span>
            </p>
            <p class="mt-2.5 text-xl font-bold tabular-nums text-white">{{ number_format($kpis['vehicle_committed_deposit'], 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-[11px] text-zinc-500">Giá xe tham chiếu: {{ number_format($kpis['vehicle_reference_value'], 0, ',', '.') }} ₫</p>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5 shadow-lg">
            <p class="flex items-center gap-2 text-xs font-semibold text-zinc-400">
                <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                <span>Giá trị giao dịch bị Hủy</span>
            </p>
            <p class="mt-2.5 text-xl font-bold tabular-nums text-white">{{ number_format($kpis['cancelled_lost_value'], 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-[11px] text-zinc-500">Đơn hàng &amp; đặt cọc đã hủy</p>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5 shadow-lg">
            <p class="flex items-center gap-2 text-xs font-semibold text-zinc-400">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                <span>Tỷ lệ hoàn tất chứng từ</span>
            </p>
            <p class="mt-2.5 text-xl font-bold tabular-nums text-white">{{ number_format($kpis['completion_rate'], 1) }}%</p>
            <p class="mt-1 text-[11px] text-zinc-500">{{ $kpis['completed_vouchers_count'] }} / {{ $kpis['total_vouchers_count'] }} chứng từ hoàn tất</p>
        </div>
    </div>

    {{-- Biểu đồ Tài chính & Sản lượng theo ngày (SVG thuần + Alpine Tooltip) --}}
    @php
        $maxChartVal = max(1, collect($chartPoints)->map(fn ($p) => max($p['completed_revenue'], $p['committed_value']))->max() ?? 1);
        $hasChartActivity = ($kpis['total_vouchers_count'] ?? 0) > 0;
    @endphp
    <x-admin.card class="mb-6">
        <div
            x-data="{ activePoint: null }"
            class="space-y-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#70A7FF]">Biểu đồ Tài chính &amp; Sản lượng theo ngày</p>
                    <h2 class="mt-1 text-base font-bold text-white">Đối chiếu Doanh thu thực thu &amp; Giá trị cam kết</h2>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs">
                    <span class="inline-flex items-center gap-1.5 text-emerald-300">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span>Doanh thu hoàn tất</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-sky-300">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#1C69D4]"></span>
                        <span>Giá trị cam kết / đặt cọc</span>
                    </span>
                </div>
            </div>

            @if(! $hasChartActivity)
                <x-admin.empty-state
                    title="Chưa có dữ liệu doanh thu trong kỳ"
                    description="Chưa phát sinh lịch hẹn, dịch vụ hay đơn phụ kiện nào trong khoảng thời gian đã chọn."
                />
            @else
                <div class="relative rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-4">
                <div
                    x-show="activePoint !== null"
                    x-cloak
                    class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#1C69D4]/30 bg-[#0D121C] px-4 py-2.5 text-xs"
                >
                    <span class="font-bold text-white" x-text="`Ngày ${activePoint?.date_label || ''} (${activePoint?.total_vouchers || 0} chứng từ)`"></span>
                    <div class="flex flex-wrap items-center gap-4 tabular-nums">
                        <span class="text-emerald-300">Thực thu: <strong x-text="new Intl.NumberFormat('vi-VN').format(activePoint?.completed_revenue || 0) + ' ₫'"></strong></span>
                        <span class="text-sky-300">Cam kết: <strong x-text="new Intl.NumberFormat('vi-VN').format(activePoint?.committed_value || 0) + ' ₫'"></strong></span>
                    </div>
                </div>

                <svg viewBox="0 0 760 210" class="h-52 w-full overflow-visible" role="img" aria-label="Biểu đồ doanh thu và giá trị cam kết theo ngày">
                    @foreach([0, 0.5, 1] as $tick)
                        @php $y = (int) round(170 - ($tick * 135)); @endphp
                        <line x1="25" y1="{{ $y }}" x2="740" y2="{{ $y }}" stroke="rgba(255,255,255,0.06)" stroke-dasharray="4 4" />
                    @endforeach

                    @php
                        $countPoints = max(1, count($chartPoints));
                        $slotW = 700 / $countPoints;
                    @endphp
                    @foreach($chartPoints as $idx => $pt)
                        @php
                            $xCenter = 40 + ($idx * $slotW) + ($slotW / 2);
                            $revH = max(4, (int) round(($pt['completed_revenue'] / $maxChartVal) * 135));
                            $comH = max(4, (int) round(($pt['committed_value'] / $maxChartVal) * 135));
                        @endphp
                        <g
                            class="cursor-pointer"
                            @mouseenter="activePoint = @js($pt)"
                            @focus="activePoint = @js($pt)"
                        >
                            <rect
                                x="{{ round($xCenter - 13, 1) }}"
                                y="{{ 170 - $revH }}"
                                width="11"
                                height="{{ $revH }}"
                                rx="3"
                                fill="#10B981"
                            />
                            <rect
                                x="{{ round($xCenter + 2, 1) }}"
                                y="{{ 170 - $comH }}"
                                width="11"
                                height="{{ $comH }}"
                                rx="3"
                                fill="#1C69D4"
                            />
                            <text
                                x="{{ round($xCenter, 1) }}"
                                y="192"
                                text-anchor="middle"
                                fill="#94A3B8"
                                font-size="10"
                                font-weight="600"
                            >{{ $pt['short_label'] }}</text>
                        </g>
                    @endforeach
                </svg>
                </div>
            @endif
        </div>
    </x-admin.card>

    {{-- Gói Dữ Liệu Từng Ngày Bán Hàng (Daily Sales Packs) --}}
    <div class="mb-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#70A7FF]">Gói dữ liệu từng ngày bán hàng (Daily Sales Packs)</p>
                <h2 class="mt-1 text-lg font-bold text-white">Sổ tổng hợp theo ngày ({{ count($dailyPacks) }} ngày có chứng từ)</h2>
            </div>
        </div>

        @if($dailyPacks === [])
            <x-admin.card>
                <x-admin.empty-state
                    title="Chưa có gói sổ ngày nào trong kỳ lọc"
                    description="Khi có lịch hẹn xe, dịch vụ xưởng hoặc đơn phụ kiện phát sinh, hệ thống sẽ tự động đóng gói theo từng ngày bán hàng tại đây."
                />
            </x-admin.card>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach($dailyPacks as $pack)
                    @php
                        /** @var \App\Models\AccountingDailyClosing|null $closing */
                        $closing = $pack['closing'] ?? null;
                    @endphp
                    <div class="flex flex-col justify-between rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 shadow-xl">
                        <div>
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-white/[0.07] pb-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Gói ngày bán hàng</p>
                                    <h3 class="mt-1 text-xl font-bold tabular-nums text-white">Ngày {{ $pack['date_label'] }}</h3>
                                </div>

                                @if($closing)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Đã chốt sổ ngày</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                        <span>Đang mở</span>
                                    </span>
                                @endif
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full border border-white/10 bg-[#070A10] px-3 py-1 font-semibold text-zinc-200">
                                    {{ $pack['vehicles_count'] }} lịch hẹn xe
                                </span>
                                <span class="rounded-full border border-white/10 bg-[#070A10] px-3 py-1 font-semibold text-zinc-200">
                                    {{ $pack['services_count'] }} dịch vụ
                                </span>
                                <span class="rounded-full border border-white/10 bg-[#070A10] px-3 py-1 font-semibold text-zinc-200">
                                    {{ $pack['orders_count'] }} đơn phụ kiện
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-3.5 text-xs">
                                <div>
                                    <p class="text-zinc-400">Doanh thu hoàn tất</p>
                                    <p class="mt-1 text-sm font-bold tabular-nums text-emerald-400">{{ number_format($pack['completed_revenue'], 0, ',', '.') }} ₫</p>
                                </div>
                                <div>
                                    <p class="text-zinc-400">Đơn/Cọc cam kết</p>
                                    <p class="mt-1 text-sm font-bold tabular-nums text-[#70A7FF]">{{ number_format($pack['committed_value'], 0, ',', '.') }} ₫</p>
                                </div>
                            </div>

                            @if($closing)
                                <p class="mt-3 text-[11px] text-zinc-400">
                                    Chốt bởi <strong class="text-zinc-200">{{ $closing->closedBy?->name ?? 'Super Admin' }}</strong>
                                    lúc {{ $closing->closed_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                                    @if(filled($closing->notes))
                                        · Ghi chú: <span class="text-zinc-300">{{ $closing->notes }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-2.5 border-t border-white/[0.07] pt-4">
                            <a
                                href="{{ route('admin.accounting.day', ['date' => $pack['date']]) }}"
                                class="inline-flex h-9 items-center gap-2 rounded-xl bg-[#1C69D4] px-4 text-xs font-semibold text-white transition-colors hover:bg-[#1557B0]"
                            >
                                <span>Mở gói sổ ngày</span>
                            </a>

                            <a
                                href="{{ route('admin.accounting.export', ['date' => $pack['date']]) }}"
                                class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/10 bg-[#131A29] px-3.5 text-xs font-semibold text-zinc-200 transition-colors hover:border-emerald-500/40 hover:text-emerald-300"
                            >
                                <span>Xuất sổ ngày (.CSV)</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
