<x-admin-layout>
    <x-admin.page-header
        :back-url="Auth::user()?->canManageSystem() ? route('admin.users.index') : route('admin.staff.profile', $staffUser)"
        :back-label="Auth::user()?->canManageSystem() ? 'Danh sách nhân sự' : 'Hồ sơ cá nhân'"
        eyebrow="Hiệu suất Sales"
        title="Thống kê"
        :accent="$staffUser->name"
        description="Theo dõi khối lượng hồ sơ phụ trách, tỷ lệ chốt hoàn thành và doanh thu phụ kiện theo thời gian thực."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Tổng hồ sơ trong kỳ</p>
            <p class="mt-1 text-2xl font-black tabular-nums text-white">{{ number_format($kpis['total']) }}</p>
        </x-slot>
    </x-admin.page-header>

    {{-- Filter Bar --}}
    <x-admin.card class="mb-6 !p-5">
        <form method="GET" action="{{ route('admin.users.performance', $staffUser) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-[auto_1fr_1fr_1fr_1fr_auto] lg:items-end">
            <div>
                <p class="mb-1.5 text-[11px] font-semibold text-zinc-400">Khoảng thời gian</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(['7d' => '7 ngày', '30d' => '30 ngày', 'this_month' => 'Tháng này', 'all' => 'Tất cả'] as $pKey => $pLabel)
                        <a
                            href="{{ route('admin.users.performance', array_merge(['user' => $staffUser->id], array_filter(['period' => $pKey, 'status' => $statusFilter, 'kind' => $kindFilter !== 'all' ? $kindFilter : null]))) }}"
                            class="inline-flex h-10 items-center rounded-lg border px-3 text-xs font-semibold transition-colors {{ $period === $pKey ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:border-white/25 hover:text-white' }}"
                        >
                            {{ $pLabel }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="perf-date-from" class="mb-1.5 block text-[11px] font-semibold text-zinc-400">Từ ngày (tùy chọn)</label>
                <input
                    id="perf-date-from"
                    type="date"
                    name="date_from"
                    value="{{ $period === 'custom' ? $dateFrom : '' }}"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    onchange="this.form.querySelector('[name=period]').value = 'custom'"
                >
                <input type="hidden" name="period" value="{{ $period }}">
            </div>

            <div>
                <label for="perf-date-to" class="mb-1.5 block text-[11px] font-semibold text-zinc-400">Đến ngày</label>
                <input
                    id="perf-date-to"
                    type="date"
                    name="date_to"
                    value="{{ $period === 'custom' ? $dateTo : '' }}"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    onchange="this.form.querySelector('[name=period]').value = 'custom'"
                >
            </div>

            <div>
                <label for="perf-kind" class="mb-1.5 block text-[11px] font-semibold text-zinc-400">Phân hệ</label>
                <x-ui.custom-select
                    id="perf-kind"
                    name="kind"
                    :value="$kindFilter"
                    :options="[
                        'all' => 'Tất cả phân hệ',
                        'appointment' => 'Lịch hẹn Showroom',
                        'service' => 'Dịch vụ xe',
                        'accessory' => 'Đơn phụ kiện',
                    ]"
                />
            </div>

            <div>
                <label for="perf-status" class="mb-1.5 block text-[11px] font-semibold text-zinc-400">Trạng thái</label>
                <x-ui.custom-select
                    id="perf-status"
                    name="status"
                    :value="$statusFilter"
                    :options="[
                        '' => 'Tất cả trạng thái',
                        'pending' => 'Chờ duyệt',
                        'confirmed' => 'Đã xác nhận',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy',
                    ]"
                />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 rounded-xl bg-[#1C69D4] px-4 text-xs font-semibold text-white transition-colors hover:bg-[#2575e6] cursor-pointer">
                    Áp dụng
                </button>
                @if($period !== '30d' || $statusFilter !== '' || $kindFilter !== 'all')
                    <a href="{{ route('admin.users.performance', $staffUser) }}" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-semibold text-zinc-400 transition-colors hover:text-white">
                        Đặt lại
                    </a>
                @endif
            </div>
        </form>

        @if(Auth::user()?->canManageSystem() && $salesRoster->count() > 1)
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/[0.06] pt-4">
                <span class="text-[11px] font-semibold text-zinc-500">Chuyển nhanh Sales:</span>
                @foreach($salesRoster as $salesMember)
                    <a
                        href="{{ route('admin.users.performance', $salesMember) }}"
                        class="rounded-full border px-3 py-1 text-xs font-semibold transition-colors {{ (int) $salesMember->id === (int) $staffUser->id ? 'border-[#38BDF8]/50 bg-[#38BDF8]/15 text-[#38BDF8]' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:border-white/25 hover:text-white' }}"
                    >
                        {{ $salesMember->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </x-admin.card>

    {{-- KPI Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.card class="!p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Tổng hồ sơ được giao</p>
            <p class="mt-2 text-3xl font-black tabular-nums text-white">{{ number_format($kpis['total']) }}</p>
            <p class="mt-1.5 text-xs text-zinc-400 tabular-nums">
                {{ $kpis['appointments_count'] }} xe &middot; {{ $kpis['service_count'] }} dịch vụ &middot; {{ $kpis['accessory_count'] }} phụ kiện
            </p>
        </x-admin.card>

        <x-admin.card class="!p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Hoàn thành & Tỷ lệ chốt</p>
            <div class="mt-2 flex items-baseline gap-2.5">
                <span class="text-3xl font-black tabular-nums text-emerald-400">{{ number_format($kpis['completed']) }}</span>
                <span class="rounded-full border border-emerald-500/30 bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold tabular-nums text-emerald-300">
                    {{ number_format($kpis['completion_rate'], 1) }}%
                </span>
            </div>
            <p class="mt-1.5 text-xs text-zinc-400">Tính trên tổng hồ sơ phụ trách trong kỳ</p>
        </x-admin.card>

        <x-admin.card class="!p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Đang xử lý & Hủy</p>
            <div class="mt-2 flex items-baseline gap-3">
                <span class="text-3xl font-black tabular-nums text-[#38BDF8]">{{ number_format($kpis['pending'] + $kpis['confirmed']) }}</span>
                <span class="text-xs font-semibold tabular-nums text-rose-400">{{ number_format($kpis['cancelled']) }} đã hủy</span>
            </div>
            <p class="mt-1.5 text-xs text-zinc-400 tabular-nums">
                {{ $kpis['pending'] }} chờ duyệt &middot; {{ $kpis['confirmed'] }} đã xác nhận
            </p>
        </x-admin.card>

        <x-admin.card class="!p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Doanh thu phụ kiện hoàn thành</p>
            <p class="mt-2 text-2xl font-black tabular-nums text-[#A78BFA]">
                {{ number_format($kpis['accessory_revenue'], 0, ',', '.') }} <span class="text-xs font-bold text-zinc-400">VNĐ</span>
            </p>
            <p class="mt-1.5 text-xs text-zinc-400">Tổng giá trị đơn phụ kiện trạng thái Hoàn thành</p>
        </x-admin.card>
    </div>

    {{-- Pure SVG Charts --}}
    @php
        $maxBarValue = max(1, (int) collect($series)->map(fn ($b) => max($b['appointment'], $b['service'], $b['accessory']))->max());
        $bucketCount = max(1, count($series));
        $svgWidth = 640;
        $svgHeight = 220;
        $padLeft = 36;
        $padRight = 16;
        $padTop = 16;
        $padBottom = 34;
        $plotWidth = $svgWidth - $padLeft - $padRight;
        $plotHeight = $svgHeight - $padTop - $padBottom;
        $slotWidth = $plotWidth / $bucketCount;
    @endphp

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- Grouped Bar Chart --}}
        <x-admin.card class="xl:col-span-8 !p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-white">Xu hướng hồ sơ tiếp nhận theo thời gian</h2>
                    <p class="mt-0.5 text-xs text-zinc-400">So sánh số lượng Lịch hẹn xe, Dịch vụ và Đơn phụ kiện.</p>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs">
                    <span class="inline-flex items-center gap-1.5 text-zinc-300">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[#38BDF8]"></span> Lịch hẹn xe
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-zinc-300">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[#10B981]"></span> Dịch vụ
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-zinc-300">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[#A78BFA]"></span> Phụ kiện
                    </span>
                </div>
            </div>

            <div
                x-data="{ activeBucket: null }"
                class="relative rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-4"
            >
                <div
                    x-show="activeBucket !== null"
                    x-cloak
                    class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs"
                >
                    <span class="font-bold text-white" x-text="activeBucket ? activeBucket.full_label : ''"></span>
                    <div class="flex items-center gap-4 tabular-nums">
                        <span class="text-[#38BDF8]">Xe: <strong x-text="activeBucket ? activeBucket.appointment : 0"></strong></span>
                        <span class="text-[#10B981]">Dịch vụ: <strong x-text="activeBucket ? activeBucket.service : 0"></strong></span>
                        <span class="text-[#A78BFA]">Phụ kiện: <strong x-text="activeBucket ? activeBucket.accessory : 0"></strong></span>
                    </div>
                </div>

                <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" class="h-56 w-full overflow-visible" role="img" aria-label="Biểu đồ cột nhóm hiệu suất Sales">
                    {{-- Horizontal Grid Lines --}}
                    @for($g = 0; $g <= 4; $g++)
                        @php
                            $gy = $padTop + ($plotHeight * ($g / 4));
                            $gVal = (int) round($maxBarValue * (1 - ($g / 4)));
                        @endphp
                        <line x1="{{ $padLeft }}" y1="{{ $gy }}" x2="{{ $svgWidth - $padRight }}" y2="{{ $gy }}" stroke="rgba(255,255,255,0.06)" stroke-dasharray="3,3" />
                        <text x="{{ $padLeft - 8 }}" y="{{ $gy + 4 }}" text-anchor="end" fill="#71717A" font-size="10" font-weight="600">{{ $gVal }}</text>
                    @endfor

                    {{-- Grouped Bars --}}
                    @foreach($series as $idx => $bucket)
                        @php
                            $groupCenterX = $padLeft + ($idx + 0.5) * $slotWidth;
                            $barW = min(14, max(5, ($slotWidth - 10) / 3.4));
                            $gap = 2;
                            $totalGroupW = ($barW * 3) + ($gap * 2);
                            $startX = $groupCenterX - ($totalGroupW / 2);

                            $hAppt = $bucket['appointment'] > 0 ? max(3, ($bucket['appointment'] / $maxBarValue) * $plotHeight) : 0;
                            $hSvc = $bucket['service'] > 0 ? max(3, ($bucket['service'] / $maxBarValue) * $plotHeight) : 0;
                            $hAcc = $bucket['accessory'] > 0 ? max(3, ($bucket['accessory'] / $maxBarValue) * $plotHeight) : 0;
                        @endphp

                        <g
                            @mouseenter="activeBucket = {{ json_encode($bucket, JSON_UNESCAPED_UNICODE) }}"
                            @mouseleave="activeBucket = null"
                            class="cursor-pointer"
                        >
                            <rect
                                x="{{ $padLeft + ($idx * $slotWidth) }}"
                                y="{{ $padTop }}"
                                width="{{ $slotWidth }}"
                                height="{{ $plotHeight }}"
                                fill="transparent"
                            />

                            @if($hAppt > 0)
                                <rect x="{{ round($startX, 1) }}" y="{{ round($padTop + $plotHeight - $hAppt, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hAppt, 1) }}" rx="3" fill="#38BDF8" />
                            @endif
                            @if($hSvc > 0)
                                <rect x="{{ round($startX + $barW + $gap, 1) }}" y="{{ round($padTop + $plotHeight - $hSvc, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hSvc, 1) }}" rx="3" fill="#10B981" />
                            @endif
                            @if($hAcc > 0)
                                <rect x="{{ round($startX + ($barW + $gap) * 2, 1) }}" y="{{ round($padTop + $plotHeight - $hAcc, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($hAcc, 1) }}" rx="3" fill="#A78BFA" />
                            @endif

                            <text x="{{ round($groupCenterX, 1) }}" y="{{ $svgHeight - 10 }}" text-anchor="middle" fill="#A1A1AA" font-size="10" font-weight="600">
                                {{ $bucket['label'] }}
                            </text>
                        </g>
                    @endforeach
                </svg>
            </div>
        </x-admin.card>

        {{-- Donut Chart --}}
        <x-admin.card class="xl:col-span-4 !p-6">
            <h2 class="text-sm font-bold text-white">Tỷ trọng trạng thái xử lý</h2>
            <p class="mt-0.5 text-xs text-zinc-400">Di chuột lên từng vùng để xem chi tiết.</p>

            <div
                x-data="{ activeSlice: null }"
                class="mt-5 flex flex-col items-center"
            >
                <div class="relative flex h-44 w-44 items-center justify-center">
                    <svg viewBox="0 0 120 120" class="h-44 w-44 -rotate-90" role="img" aria-label="Biểu đồ Donut tỷ trọng trạng thái">
                        <circle cx="60" cy="60" r="42" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="14" />
                        @foreach($donutSlices as $slice)
                            @if($slice['count'] > 0)
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="{{ $slice['color'] }}"
                                    stroke-width="14"
                                    stroke-dasharray="{{ $slice['dash'] }} {{ $slice['gap'] }}"
                                    stroke-dashoffset="{{ $slice['offset'] }}"
                                    @mouseenter="activeSlice = {{ json_encode($slice, JSON_UNESCAPED_UNICODE) }}"
                                    @mouseleave="activeSlice = null"
                                    class="cursor-pointer transition-opacity duration-150 hover:opacity-85"
                                />
                            @endif
                        @endforeach
                    </svg>

                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                        <template x-if="activeSlice">
                            <div>
                                <p class="text-[11px] font-semibold text-zinc-400" x-text="activeSlice.label"></p>
                                <p class="text-xl font-black tabular-nums text-white" x-text="activeSlice.percent + '%'"></p>
                                <p class="text-[11px] tabular-nums text-zinc-400" x-text="activeSlice.count + ' hồ sơ'"></p>
                            </div>
                        </template>
                        <template x-if="!activeSlice">
                            <div>
                                <p class="text-[11px] font-semibold text-zinc-400">Hoàn thành</p>
                                <p class="text-2xl font-black tabular-nums text-emerald-400">{{ number_format($kpis['completion_rate'], 1) }}%</p>
                                <p class="text-[11px] tabular-nums text-zinc-500">{{ number_format($kpis['total']) }} tổng</p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-5 w-full space-y-2 border-t border-white/[0.06] pt-4">
                    @foreach($donutSlices as $slice)
                        <div
                            @mouseenter="activeSlice = {{ json_encode($slice, JSON_UNESCAPED_UNICODE) }}"
                            @mouseleave="activeSlice = null"
                            class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs transition-colors hover:bg-white/[0.04]"
                        >
                            <span class="inline-flex items-center gap-2 font-medium text-zinc-300">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $slice['color'] }}"></span>
                                {{ $slice['label'] }}
                            </span>
                            <span class="font-bold tabular-nums text-white">
                                {{ $slice['count'] }} <span class="font-medium text-zinc-500">({{ number_format($slice['percent'], 1) }}%)</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-admin.card>
    </div>

    {{-- Detailed Assigned Leads Table --}}
    <x-admin.card class="!p-0 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.08] px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-white">Hồ sơ đang phụ trách</h2>
                <p class="mt-0.5 text-xs text-zinc-400">Hiển thị tối đa 50 hồ sơ gần nhất khớp bộ lọc ({{ $totalFilteredCount }} hồ sơ).</p>
            </div>
        </div>

        @if($items->isEmpty())
            <div class="p-6">
                <x-admin.empty-state
                    title="Chưa có hồ sơ phù hợp"
                    description="Thay đổi bộ lọc thời gian hoặc phân hệ để xem các hồ sơ được phân công cho nhân sự này."
                />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/[0.08] bg-[#070A10]/70">
                            <th class="px-6 py-3.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Mã kép (Khách / Ngày)</th>
                            <th class="px-6 py-3.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Phân hệ</th>
                            <th class="px-6 py-3.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Khách hàng</th>
                            <th class="px-6 py-3.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Hạng mục</th>
                            <th class="px-6 py-3.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">Trạng thái</th>
                            <th class="px-6 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-zinc-400">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06] text-xs">
                        @foreach($items as $row)
                            @php
                                $pillClass = match ($row['status']) {
                                    'completed' => 'border-emerald-500/30 bg-emerald-500/15 text-emerald-300',
                                    'confirmed' => 'border-[#38BDF8]/30 bg-[#38BDF8]/15 text-[#38BDF8]',
                                    'cancelled' => 'border-rose-500/30 bg-rose-500/15 text-rose-300',
                                    default => 'border-amber-500/30 bg-amber-500/15 text-amber-300',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-[#131A29]/60">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="font-bold tabular-nums text-[#70A7FF]">{{ $row['customer_order_code'] }}</p>
                                    <p class="mt-0.5 text-[11px] tabular-nums text-zinc-400">{{ $row['daily_sequence_code'] }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="rounded-full border border-white/10 bg-[#070A10] px-2.5 py-1 text-[11px] font-semibold text-zinc-300">
                                        {{ $row['kind_label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-white">{{ $row['customer_name'] }}</p>
                                    <p class="mt-0.5 tabular-nums text-zinc-400">{{ $row['customer_phone'] }}</p>
                                </td>
                                <td class="px-6 py-4 text-zinc-200">
                                    {{ $row['subject'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $pillClass }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <a
                                        href="{{ $row['route_url'] }}"
                                        class="inline-flex items-center rounded-lg border border-white/10 bg-[#070A10] px-3 py-1.5 text-[11px] font-semibold text-zinc-200 transition-colors hover:border-[#1C69D4] hover:text-white"
                                    >
                                        Mở hồ sơ
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>
</x-admin-layout>
