@php
    $showRouteParams = ['phone' => $routePhone];
    if ($routeEmail !== '') {
        $showRouteParams['email'] = $routeEmail;
    }

    $quickTemplatesPayload = [];
    $quickTemplateOptions = ['' => '-- Chọn mẫu Email đang hoạt động --'];
    foreach ($activeEmailTemplates as $tpl) {
        $quickTemplateOptions[(string) $tpl->id] = $tpl->name . ' (' . $tpl->categoryLabel() . ')';
        $quickTemplatesPayload[(string) $tpl->id] = [
            'id' => $tpl->id,
            'code' => $tpl->code,
            'name' => $tpl->name,
            'subject' => $tpl->subject,
            'heading' => $tpl->heading,
            'body_content' => $tpl->body_content,
            'highlight_title' => $tpl->highlight_title,
            'highlight_content' => $tpl->highlight_content,
            'accent_color' => $tpl->accent_color,
        ];
    }

    $installmentSelectOptions = ['' => 'Tự động lấy hồ sơ trả góp gần nhất'];
    $installmentPlansVars = [];
    foreach ($installmentPlans as $plan) {
        $installmentSelectOptions[(string) $plan->id] = $plan->bank_name . ' · ' . $plan->interest_rate . '% (' . $plan->paid_installments . '/' . $plan->term_months . ' kỳ)';
        $installmentPlansVars[(string) $plan->id] = [
            'vehicle_name' => $plan->product?->name ?? ($previewVariables['vehicle_name'] ?? 'dòng xe BMW'),
            'bank_name' => $plan->bank_name,
            'interest_rate' => rtrim(rtrim(number_format((float) $plan->interest_rate, 2, '.', ''), '0'), '.') . '%/năm',
            'monthly_payment' => number_format((int) $plan->monthly_payment, 0, ',', '.') . ' VNĐ',
            'next_due_date' => $plan->next_due_date?->format('d/m/Y') ?? 'theo lịch kỳ tiếp theo',
        ];
    }

    $productSelectOptions = ['' => '-- Không gắn mẫu xe cụ thể --'] + $vehicleProducts->pluck('name', 'id')->all();
    $appointmentSelectOptions = ['' => '-- Không gắn lịch hẹn cụ thể --'];
    foreach ($vehicleAppointments as $appt) {
        $appointmentSelectOptions[(string) $appt->id] = $appt->publicReferenceCode() . ' · ' . ($appt->product?->name ?? 'Tư vấn') . ' (' . $appt->appointment_date?->format('d/m/Y') . ')';
    }
@endphp

<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.customers.index')"
        back-label="Danh sách khách hàng"
        eyebrow="Customer 360° · Hồ sơ Khách hàng"
        :title="$profile['name']"
        accent="360°"
        :description="'SĐT: ' . $profile['phone'] . ' · Email: ' . ($profile['email'] ?: 'Chưa có') . ' · Sales phụ trách: ' . $profile['primary_sales']"
    >
        @if($profile['phone'] !== 'N/A')
            <x-slot name="actions">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="tel:{{ $profile['phone'] }}"
                        class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-emerald-500/35 bg-emerald-500/15 px-4 text-xs font-semibold text-emerald-300 transition-colors hover:bg-emerald-500 hover:text-slate-950"
                    >
                        Gọi khách
                    </a>
                </div>
            </x-slot>
        @endif
    </x-admin.page-header>

    <!-- 360° KPI Summary Strip -->
    <div class="mb-6 grid grid-cols-2 gap-3.5 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-3.5">
            <p class="text-[11px] font-semibold text-zinc-400">Lịch hẹn Showroom</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-white">{{ $kpis['appointments'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-3.5">
            <p class="text-[11px] font-semibold text-zinc-400">Dịch vụ xưởng</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-sky-300">{{ $kpis['services'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-3.5">
            <p class="text-[11px] font-semibold text-zinc-400">Đơn phụ kiện</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-[#70A7FF]">{{ $kpis['orders'] }}</p>
        </div>
        <div class="min-w-0 rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-3.5">
            <p class="text-[11px] font-semibold text-zinc-400">Chi tiêu phụ kiện</p>
            <p class="mt-1 truncate whitespace-nowrap text-base font-extrabold tabular-nums text-emerald-300 sm:text-lg" title="{{ number_format($kpis['total_spend'], 0, ',', '.') }} đ">{{ number_format($kpis['total_spend'], 0, ',', '.') }}&nbsp;đ</p>
        </div>
        <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3.5">
            <p class="text-[11px] font-semibold text-emerald-300">Hồ sơ trả góp mở</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-white">{{ $kpis['open_installments'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-3.5">
            <p class="text-[11px] font-semibold text-zinc-400">Phiên Chat AI</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-violet-300">{{ $kpis['ai_sessions'] }}</p>
        </div>
    </div>

    <!-- Top Installment Debt & Schedule Summary Strip -->
    <div class="mb-6 rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5" data-customer-installment-overview>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.06] pb-3.5">
            <div class="flex flex-wrap items-center gap-2.5">
                <p class="text-xs font-bold uppercase tracking-wider text-[#38BDF8]">Tổng quan Tín dụng &amp; Trả góp BMW Financial</p>
                <span class="rounded-full border px-3 py-0.5 text-[11px] font-bold {{ $installmentOverview['status_badge_class'] }}">
                    {{ $installmentOverview['status_label'] }}
                </span>
            </div>
            <a href="{{ route('customer.lookup.index') }}" target="_blank" rel="noopener" class="text-xs font-semibold text-[#70A7FF] hover:underline">
                Mở cổng tra cứu khách hàng (/tra-cuu) &rarr;
            </a>
        </div>

        <div class="mt-3.5 grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4 text-xs">
            <div class="min-w-0 rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-3.5">
                <p class="text-[11px] text-zinc-400">Dư nợ gốc còn lại</p>
                <p class="mt-1 truncate whitespace-nowrap text-sm font-extrabold tabular-nums text-white sm:text-base" title="{{ number_format($installmentOverview['remaining_principal'], 0, ',', '.') }} đ">{{ number_format($installmentOverview['remaining_principal'], 0, ',', '.') }}&nbsp;đ</p>
            </div>
            <div class="min-w-0 rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-3.5">
                <p class="text-[11px] text-zinc-400">Tổng tiền dự kiến còn lại</p>
                <p class="mt-1 truncate whitespace-nowrap text-sm font-extrabold tabular-nums text-sky-300 sm:text-base" title="{{ number_format($installmentOverview['estimated_remaining_total'], 0, ',', '.') }} đ">{{ number_format($installmentOverview['estimated_remaining_total'], 0, ',', '.') }}&nbsp;đ</p>
            </div>
            <div class="min-w-0 rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-3.5">
                <p class="text-[11px] text-zinc-400">Số kỳ còn lại</p>
                <p class="mt-1 whitespace-nowrap text-sm font-extrabold tabular-nums text-[#70A7FF] sm:text-base">
                    @if($installmentOverview['term_months'] > 0)
                        Còn {{ $installmentOverview['remaining_installments'] }} / {{ $installmentOverview['term_months'] }} kỳ
                    @else
                        0 / 0 kỳ
                    @endif
                </p>
            </div>
            <div class="min-w-0 rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-3.5">
                <p class="text-[11px] text-zinc-400">Ngày đến hạn kỳ tới</p>
                <p class="mt-1 whitespace-nowrap text-sm font-extrabold tabular-nums text-emerald-300 sm:text-base">{{ $installmentOverview['next_due_date_label'] }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.35fr_0.65fr] xl:items-start">
        <!-- Left Column: Installment Plans + 360 History Tabs -->
        <div class="space-y-6">
            <!-- Section 1: Quản lý Hồ sơ Trả góp (BMW Financial) -->
            <x-admin.card class="!p-6 space-y-5" x-data="{ showCreatePlan: {{ $errors->hasAny(['bank_name', 'loan_amount', 'monthly_payment', 'interest_rate', 'term_months', 'paid_installments']) ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.08] pb-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#38BDF8]">BMW Financial Services</p>
                        <h2 class="mt-1 text-base font-bold text-white">Hồ sơ Trả góp &amp; Tiến độ Thanh toán</h2>
                    </div>
                    <button
                        type="button"
                        @click="showCreatePlan = !showCreatePlan"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-[#1C69D4] px-4 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer"
                    >
                        <span x-text="showCreatePlan ? 'Đóng biểu mẫu' : '+ Thêm hồ sơ trả góp'"></span>
                    </button>
                </div>

                <!-- Create New Installment Plan Form (Phase 40 Live Calculator + Dot Formatting) -->
                @php
                    $createCalcConfig = [
                        'mode' => 'create',
                        'vehiclePrice' => old('vehicle_price'),
                        'downPayment' => old('down_payment'),
                        'loanAmount' => (int) old('loan_amount', 2000000000),
                        'monthlyPayment' => (int) old('monthly_payment', 42000000),
                        'hasOldMonthlyPayment' => old('monthly_payment') !== null,
                        'interestRate' => (string) old('interest_rate', '7.9'),
                        'interestMethod' => (string) old('interest_method', \App\Models\CustomerInstallmentPlan::METHOD_REDUCING_BALANCE),
                        'termMonths' => (int) old('term_months', 60),
                        'paidInstallments' => (int) old('paid_installments', 0),
                    ];
                @endphp
                <form
                    x-show="showCreatePlan"
                    x-collapse
                    x-data="bmwInstallmentCalculator($el)"
                    data-installment-create-form
                    method="POST"
                    action="{{ route('admin.customers.installments.store', $showRouteParams) }}"
                    class="rounded-2xl border border-[#1C69D4]/30 bg-[#070A10]/90 p-5 space-y-4"
                >
                    <script type="application/json" data-installment-calc-config>@json($createCalcConfig)</script>
                    @csrf
                    <input type="hidden" name="route_email" value="{{ $routeEmail }}">
                    <p class="text-xs font-bold text-[#70A7FF]">Khởi tạo hợp đồng trả góp mới cho {{ $profile['name'] }}</p>

                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                        <x-admin.form-field name="bank_name" label="Ngân hàng / Tổ chức tài chính *">
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" required placeholder="Ví dụ: Techcombank BMW Financial" class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="status" label="Trạng thái hợp đồng *">
                            <x-ui.custom-select
                                name="status"
                                :value="old('status', \App\Models\CustomerInstallmentPlan::STATUS_ACTIVE)"
                                :options="$installmentStatusOptions"
                            />
                        </x-admin.form-field>

                        <x-admin.form-field name="product_id" label="Mẫu xe BMW liên kết">
                            <x-ui.custom-select
                                name="product_id"
                                :value="(string) old('product_id', '')"
                                :options="$productSelectOptions"
                            />
                        </x-admin.form-field>

                        <x-admin.form-field name="appointment_id" label="Lịch hẹn Showroom liên kết">
                            <x-ui.custom-select
                                name="appointment_id"
                                :value="(string) old('appointment_id', '')"
                                :options="$appointmentSelectOptions"
                            />
                        </x-admin.form-field>

                        <x-admin.form-field name="vehicle_price" label="Giá xe niêm yết (VNĐ)">
                            <input type="hidden" name="vehicle_price" :value="vehiclePrice !== null ? vehiclePrice : ''">
                            <input
                                type="text"
                                inputmode="numeric"
                                data-calc-vehicle-price
                                :value="vehiclePriceDisplay"
                                @input="onVehiclePriceInput($event.target.value)"
                                placeholder="Ví dụ: 3.500.000.000"
                                class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-semibold tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-zinc-300">Trả trước đối ứng (VNĐ &amp; Tỷ lệ %)</label>
                            <input type="hidden" name="down_payment" :value="downPayment !== null ? downPayment : ''">
                            <div class="grid grid-cols-[1fr_92px] gap-2">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    data-calc-down-payment
                                    :value="downPaymentDisplay"
                                    @input="onDownPaymentInput($event.target.value)"
                                    placeholder="Ví dụ: 1.050.000.000"
                                    class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-semibold tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                                >
                                <div class="relative">
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        data-calc-down-payment-percent
                                        :value="downPaymentPercent"
                                        @input="onDownPaymentPercentInput($event.target.value)"
                                        placeholder="30"
                                        aria-label="Tỷ lệ trả trước (%)"
                                        class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] pl-3 pr-7 text-right text-xs font-bold tabular-nums text-[#70A7FF] placeholder:text-zinc-600 focus:border-[#1C69D4] focus:outline-none"
                                    >
                                    <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-zinc-400">%</span>
                                </div>
                            </div>
                        </div>

                        <x-admin.form-field name="loan_amount" label="Tổng khoản vay (VNĐ) *">
                            <input type="hidden" name="loan_amount" :value="loanAmount">
                            <input
                                type="text"
                                inputmode="numeric"
                                data-calc-loan-amount
                                :value="loanAmountDisplay"
                                @input="onLoanAmountInput($event.target.value)"
                                required
                                placeholder="Ví dụ: 2.450.000.000"
                                class="h-10 w-full rounded-xl border border-[#1C69D4]/35 bg-[#0D121C] px-3.5 text-xs font-bold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <label class="text-xs font-semibold text-zinc-300">Thanh toán / tháng (VNĐ) *</label>
                                <button
                                    type="button"
                                    x-show="hasManualMonthlyOverride"
                                    @click="syncRecommendedMonthlyPayment()"
                                    class="text-[10px] font-bold text-[#70A7FF] hover:underline cursor-pointer"
                                >
                                    Đồng bộ theo kỳ đầu
                                </button>
                            </div>
                            <input type="hidden" name="monthly_payment" :value="monthlyPayment">
                            <input
                                type="text"
                                inputmode="numeric"
                                data-calc-monthly-payment
                                :value="monthlyPaymentDisplay"
                                @input="onMonthlyPaymentInput($event.target.value)"
                                required
                                class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-bold tabular-nums text-emerald-300 focus:border-[#1C69D4] focus:outline-none"
                            >
                        </div>

                        <x-admin.form-field name="interest_rate" label="Lãi suất (%/năm) *">
                            <input
                                type="text"
                                inputmode="decimal"
                                name="interest_rate"
                                data-calc-interest-rate
                                x-model="interestRate"
                                @input="onFormulaParamChange()"
                                required
                                class="no-number-spinner h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>

                        <x-admin.form-field name="interest_method" label="Phương thức tính lãi">
                            <div @change="if ($event.detail?.value) onFormulaParamChange($event.detail.value)">
                                <x-ui.custom-select
                                    name="interest_method"
                                    :value="(string) old('interest_method', \App\Models\CustomerInstallmentPlan::METHOD_REDUCING_BALANCE)"
                                    :options="$interestMethodOptions"
                                />
                            </div>
                        </x-admin.form-field>

                        <x-admin.form-field name="start_date" label="Ngày bắt đầu hợp đồng">
                            <input type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="next_due_date" label="Ngày đến hạn kỳ tiếp theo">
                            <input type="date" name="next_due_date" value="{{ old('next_due_date', now()->addMonth()->format('Y-m-d')) }}" class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="term_months" label="Tổng số kỳ hạn (tháng) *">
                            <input
                                type="number"
                                name="term_months"
                                data-calc-term-months
                                x-model.number="termMonths"
                                @input="onFormulaParamChange()"
                                min="1"
                                max="240"
                                required
                                class="no-number-spinner h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>

                        <x-admin.form-field name="paid_installments" label="Số kỳ đã thanh toán *">
                            <input
                                type="number"
                                name="paid_installments"
                                data-calc-paid-installments
                                x-model.number="paidInstallments"
                                min="0"
                                max="240"
                                required
                                class="no-number-spinner h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>
                    </div>

                    {{-- Realtime Amortization Preview Panel --}}
                    <div
                        data-installment-live-preview
                        class="rounded-xl border border-[#1C69D4]/30 bg-[#0D121C]/95 p-4 space-y-2.5"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-[#38BDF8]">
                                Dự toán Realtime BMW Financial
                            </p>
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300" x-text="interestMethod === 'flat' ? 'Lãi cố định trên gốc ban đầu' : 'Dư nợ giảm dần chuẩn ngân hàng'"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6 text-xs">
                            <div class="rounded-lg border border-white/[0.06] bg-[#070A10] p-2.5">
                                <p class="text-[10px] text-zinc-400">Gốc mỗi tháng</p>
                                <p class="mt-1 font-bold tabular-nums text-white" x-text="formatCurrency(summary.monthlyPrincipal)"></p>
                            </div>
                            <div class="rounded-lg border border-white/[0.06] bg-[#070A10] p-2.5">
                                <p class="text-[10px] text-zinc-400">Lãi kỳ đầu</p>
                                <p class="mt-1 font-bold tabular-nums text-amber-300" x-text="formatCurrency(summary.firstMonthInterest)"></p>
                            </div>
                            <div class="rounded-lg border border-white/[0.06] bg-[#070A10] p-2.5">
                                <p class="text-[10px] text-zinc-400">Lãi TB / tháng</p>
                                <p class="mt-1 font-bold tabular-nums text-zinc-200" x-text="formatCurrency(summary.avgMonthlyInterest)"></p>
                            </div>
                            <div class="rounded-lg border border-white/[0.06] bg-[#070A10] p-2.5">
                                <p class="text-[10px] text-zinc-400">Tổng lãi toàn kỳ</p>
                                <p class="mt-1 font-bold tabular-nums text-amber-300" x-text="formatCurrency(summary.totalInterest)"></p>
                            </div>
                            <div class="rounded-lg border border-white/[0.06] bg-[#070A10] p-2.5">
                                <p class="text-[10px] text-zinc-400">Tổng gốc + lãi</p>
                                <p class="mt-1 font-bold tabular-nums text-sky-300" x-text="formatCurrency(summary.totalPayable)"></p>
                            </div>
                            <div class="rounded-lg border border-emerald-500/25 bg-emerald-500/10 p-2.5">
                                <p class="text-[10px] text-emerald-300">Gốc còn lại</p>
                                <p class="mt-1 font-bold tabular-nums text-emerald-200" x-text="formatCurrency(summary.remainingPrincipal)"></p>
                            </div>
                        </div>
                    </div>

                    <x-admin.form-field name="notes" label="Ghi chú hợp đồng / điều kiện lãi suất">
                        <textarea name="notes" rows="2" placeholder="Ví dụ: Cố định 12 tháng đầu, sau đó thả nổi biên độ 3.2%..." class="w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 py-2.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none">{{ old('notes') }}</textarea>
                    </x-admin.form-field>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCreatePlan = false" class="h-9 rounded-xl border border-white/10 bg-[#131A29] px-4 text-xs font-semibold text-zinc-300 cursor-pointer">Hủy</button>
                        <button type="submit" class="h-9 rounded-xl bg-emerald-600 px-5 text-xs font-bold text-white hover:bg-emerald-500 cursor-pointer">Lưu hồ sơ trả góp</button>
                    </div>
                </form>

                <!-- Existing Installment Plans -->
                @if($installmentPlans->isEmpty())
                    <div class="rounded-xl border border-dashed border-white/10 bg-[#070A10]/60 px-5 py-6 text-center text-xs text-zinc-400">
                        Khách hàng này chưa có hồ sơ trả góp nào. Bấm <strong class="text-white">"+ Thêm hồ sơ trả góp"</strong> để bắt đầu theo dõi kỳ hạn &amp; lãi suất.
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($installmentPlans as $plan)
                            @php
                                $updateParams = array_merge($showRouteParams, ['plan' => $plan->id]);
                                $planCalc = $installmentSchedules[$plan->id] ?? null;
                                $progress = $planCalc['progress_percent'] ?? $plan->progressPercent();
                            @endphp
                            <div
                                x-data="{ editing: false, showSchedule: false }"
                                class="rounded-2xl border border-white/[0.08] bg-[#070A10]/80 p-4 space-y-3"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-white">{{ $plan->bank_name }}</span>
                                            <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $plan->statusColorClass() }}">
                                                {{ $plan->statusLabel() }}
                                            </span>
                                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
                                                {{ $plan->interestMethodLabel() }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-zinc-400">
                                            {{ $plan->product?->name ?? 'Hợp đồng tài chính BMW' }}
                                            @if($plan->appointment)
                                                · Lịch hẹn <span class="text-[#70A7FF]">{{ $plan->appointment->customer_order_code }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($planCalc)
                                            <button
                                                type="button"
                                                @click="showSchedule = !showSchedule"
                                                class="h-8 rounded-lg border border-[#38BDF8]/35 bg-[#38BDF8]/10 px-3 text-[11px] font-semibold text-[#38BDF8] hover:bg-[#38BDF8] hover:text-black cursor-pointer"
                                                x-text="showSchedule ? 'Ẩn lịch từng tháng' : 'Lịch từng tháng (Kỳ 1 → {{ $planCalc['term_months'] }})'"
                                            ></button>
                                        @endif
                                        <button
                                            type="button"
                                            @click="editing = !editing"
                                            class="h-8 rounded-lg border border-white/10 bg-[#131A29] px-3 text-[11px] font-semibold text-zinc-300 hover:border-[#1C69D4] hover:text-white cursor-pointer"
                                            x-text="editing ? 'Đóng sửa' : 'Cập nhật kỳ / lãi suất'"
                                        ></button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 text-xs">
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Tổng khoản vay</p>
                                        <p class="mt-1 font-bold tabular-nums text-white">{{ number_format($plan->loan_amount, 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-xl border border-[#1C69D4]/30 bg-[#1C69D4]/10 p-3">
                                        <p class="text-[10px] text-[#70A7FF]">Dư nợ gốc còn lại</p>
                                        <p class="mt-1 font-bold tabular-nums text-white">{{ number_format($planCalc['remaining_principal'] ?? 0, 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Dự kiến còn đóng</p>
                                        <p class="mt-1 font-bold tabular-nums text-sky-300">{{ number_format($planCalc['estimated_remaining_total'] ?? 0, 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Trả tham chiếu / tháng</p>
                                        <p class="mt-1 font-bold tabular-nums text-zinc-200">{{ number_format($plan->monthly_payment, 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Lãi suất áp dụng</p>
                                        <p class="mt-1 font-bold tabular-nums text-amber-300">{{ rtrim(rtrim(number_format($plan->interest_rate, 2, '.', ''), '0'), '.') }}%/năm</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Kỳ đến hạn kế tiếp</p>
                                        <p class="mt-1 font-bold tabular-nums text-emerald-300">{{ $planCalc['next_due_date_label'] ?? ($plan->next_due_date?->format('d/m/Y') ?? 'Đã tất toán') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1.5 flex items-center justify-between text-[11px]">
                                        <span class="font-semibold text-zinc-300">
                                            Tiến độ thanh toán: <strong class="tabular-nums text-white">{{ $planCalc['paid_installments'] ?? $plan->paid_installments }}/{{ $plan->term_months }} kỳ</strong> (còn {{ $planCalc['remaining_installments'] ?? $plan->remainingInstallments() }} kỳ)
                                        </span>
                                        <span class="font-bold tabular-nums text-[#70A7FF]">{{ $progress }}%</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-white/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-[#1C69D4] to-emerald-400" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>

                                @if(filled($plan->notes))
                                    <p class="text-xs text-zinc-400"><strong class="text-zinc-300">Ghi chú:</strong> {{ $plan->notes }}</p>
                                @endif

                                {{-- Monthly Amortization Schedule Table --}}
                                @if($planCalc && ! empty($planCalc['schedule']))
                                    <div x-show="showSchedule" x-collapse class="pt-2">
                                        <div class="max-h-96 overflow-auto rounded-xl border border-white/10 bg-[#0D121C]">
                                            <table class="w-full min-w-[680px] border-collapse text-left text-[11px]">
                                                <thead class="sticky top-0 z-10 border-b border-white/10 bg-[#070A10] text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                                                    <tr>
                                                        <th class="px-3 py-2.5">Kỳ</th>
                                                        <th class="px-3 py-2.5">Hạn đóng</th>
                                                        <th class="px-3 py-2.5 text-right">Dư nợ đầu kỳ</th>
                                                        <th class="px-3 py-2.5 text-right">Gốc</th>
                                                        <th class="px-3 py-2.5 text-right">Lãi</th>
                                                        <th class="px-3 py-2.5 text-right">Tổng đóng</th>
                                                        <th class="px-3 py-2.5 text-right">Dư nợ cuối</th>
                                                        <th class="px-3 py-2.5 text-right">Trạng thái</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-white/[0.06]">
                                                    @foreach($planCalc['schedule'] as $sRow)
                                                        @php
                                                            $sStatus = $sRow['status'];
                                                            $sStrike = $sStatus === 'paid' ? 'opacity-50 line-through' : '';
                                                            $sRowBg = match ($sStatus) {
                                                                'current' => 'border-l-2 border-l-[#1C69D4] bg-[#1C69D4]/10',
                                                                'overdue' => 'border-l-2 border-l-rose-500 bg-rose-500/10',
                                                                default => '',
                                                            };
                                                        @endphp
                                                        <tr class="{{ $sRowBg }}">
                                                            <td class="px-3 py-2 font-bold tabular-nums text-white {{ $sStrike }}">Kỳ {{ $sRow['period'] }}</td>
                                                            <td class="px-3 py-2 tabular-nums text-zinc-300 {{ $sStrike }}">{{ $sRow['due_date_label'] }}</td>
                                                            <td class="px-3 py-2 text-right tabular-nums text-zinc-400 {{ $sStrike }}">{{ number_format($sRow['opening_balance'], 0, ',', '.') }}</td>
                                                            <td class="px-3 py-2 text-right tabular-nums text-zinc-200 {{ $sStrike }}">{{ number_format($sRow['principal_amount'], 0, ',', '.') }}</td>
                                                            <td class="px-3 py-2 text-right tabular-nums text-amber-300 {{ $sStrike }}">{{ number_format($sRow['interest_amount'], 0, ',', '.') }}</td>
                                                            <td class="px-3 py-2 text-right font-bold tabular-nums text-white {{ $sStrike }}">{{ number_format($sRow['total_payment'], 0, ',', '.') }}</td>
                                                            <td class="px-3 py-2 text-right tabular-nums text-zinc-400 {{ $sStrike }}">{{ number_format($sRow['closing_balance'], 0, ',', '.') }}</td>
                                                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                                                @if($sStatus === 'paid')
                                                                    <span class="rounded-md border border-emerald-500/35 bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold text-emerald-300">Đã thanh toán</span>
                                                                @elseif($sStatus === 'overdue')
                                                                    <span class="rounded-md border border-rose-500/40 bg-rose-500/15 px-2 py-0.5 text-[10px] font-bold text-rose-300">Quá hạn</span>
                                                                @elseif($sStatus === 'current')
                                                                    <span class="rounded-md border border-[#1C69D4]/45 bg-[#1C69D4]/20 px-2 py-0.5 text-[10px] font-bold text-[#70A7FF]">Kỳ sắp tới</span>
                                                                @else
                                                                    <span class="text-[10px] text-zinc-500">Chưa đến kỳ</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                <!-- Inline Edit Form (Phase 40 Live Calculator — Preserves Saved Monthly Payment on Mount) -->
                                @php
                                    $editCalcConfig = [
                                        'mode' => 'edit',
                                        'vehiclePrice' => $plan->vehicle_price,
                                        'downPayment' => $plan->down_payment,
                                        'loanAmount' => (int) $plan->loan_amount,
                                        'monthlyPayment' => (int) $plan->monthly_payment,
                                        'interestRate' => rtrim(rtrim(number_format((float) $plan->interest_rate, 2, '.', ''), '0'), '.'),
                                        'interestMethod' => (string) ($plan->interest_method ?: 'reducing_balance'),
                                        'termMonths' => (int) $plan->term_months,
                                        'paidInstallments' => (int) $plan->paid_installments,
                                    ];
                                @endphp
                                <form
                                    x-show="editing"
                                    x-collapse
                                    x-data="bmwInstallmentCalculator($el)"
                                    data-installment-edit-form="{{ $plan->id }}"
                                    method="POST"
                                    action="{{ route('admin.customers.installments.update', $updateParams) }}"
                                    class="border-t border-white/[0.08] pt-4 space-y-3"
                                >
                                    <script type="application/json" data-installment-calc-config>@json($editCalcConfig)</script>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="route_email" value="{{ $routeEmail }}">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ngân hàng</label>
                                            <input type="text" name="bank_name" value="{{ $plan->bank_name }}" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Giá xe (VNĐ)</label>
                                            <input type="hidden" name="vehicle_price" :value="vehiclePrice !== null ? vehiclePrice : ''">
                                            <input type="text" inputmode="numeric" :value="vehiclePriceDisplay" @input="onVehiclePriceInput($event.target.value)" placeholder="3.500.000.000" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Trả trước (VNĐ &amp; %)</label>
                                            <input type="hidden" name="down_payment" :value="downPayment !== null ? downPayment : ''">
                                            <div class="grid grid-cols-[1fr_72px] gap-1.5">
                                                <input type="text" inputmode="numeric" :value="downPaymentDisplay" @input="onDownPaymentInput($event.target.value)" placeholder="1.050.000.000" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-2.5 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                                <div class="relative">
                                                    <input type="text" inputmode="decimal" :value="downPaymentPercent" @input="onDownPaymentPercentInput($event.target.value)" placeholder="%" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] pl-2 pr-5 text-right text-xs font-bold tabular-nums text-[#70A7FF] focus:border-[#1C69D4] focus:outline-none">
                                                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-zinc-400">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Tổng vay (VNĐ)</label>
                                            <input type="hidden" name="loan_amount" :value="loanAmount">
                                            <input type="text" inputmode="numeric" :value="loanAmountDisplay" @input="onLoanAmountInput($event.target.value)" required class="h-9 w-full rounded-lg border border-[#1C69D4]/35 bg-[#0D121C] px-3 text-xs font-bold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                        <div>
                                            <div class="mb-1 flex items-center justify-between gap-1">
                                                <label class="text-[10px] font-semibold text-zinc-400">Góp / tháng (VNĐ)</label>
                                                <button type="button" x-show="hasManualMonthlyOverride" @click="syncRecommendedMonthlyPayment()" class="text-[9px] font-bold text-[#70A7FF] hover:underline cursor-pointer">Tính lại</button>
                                            </div>
                                            <input type="hidden" name="monthly_payment" :value="monthlyPayment">
                                            <input type="text" inputmode="numeric" data-edit-monthly-payment="{{ $plan->id }}" :value="monthlyPaymentDisplay" @input="onMonthlyPaymentInput($event.target.value)" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs font-bold tabular-nums text-emerald-300 focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Lãi suất (%/năm)</label>
                                            <input type="text" inputmode="decimal" name="interest_rate" x-model="interestRate" @input="onFormulaParamChange()" required class="no-number-spinner h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Phương thức tính lãi</label>
                                            <div @change="if ($event.detail?.value) onFormulaParamChange($event.detail.value)">
                                                <x-ui.custom-select compact name="interest_method" :value="$plan->interest_method ?: 'reducing_balance'" :options="$interestMethodOptions" />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Đã đóng / Tổng kỳ</label>
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" name="paid_installments" x-model.number="paidInstallments" min="0" required class="no-number-spinner h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-2.5 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                                <span class="text-zinc-500">/</span>
                                                <input type="number" name="term_months" x-model.number="termMonths" @input="onFormulaParamChange()" min="1" max="240" required class="no-number-spinner h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-2.5 text-xs font-semibold tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ngày đến hạn kế tiếp</label>
                                            <input type="date" name="next_due_date" value="{{ $plan->next_due_date?->format('Y-m-d') }}" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ngày bắt đầu hợp đồng</label>
                                            <input type="date" name="start_date" value="{{ $plan->start_date?->format('Y-m-d') }}" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Trạng thái</label>
                                            <x-ui.custom-select compact name="status" :value="$plan->status" :options="$installmentStatusOptions" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ghi chú</label>
                                            <input type="text" name="notes" value="{{ $plan->notes }}" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs text-white focus:border-[#1C69D4] focus:outline-none">
                                        </div>
                                    </div>

                                    {{-- Compact Live Summary on Edit --}}
                                    <div class="grid grid-cols-2 gap-2 rounded-xl border border-white/[0.08] bg-[#0D121C]/80 p-3 text-[11px] sm:grid-cols-4">
                                        <div>
                                            <span class="text-zinc-400">Gốc/tháng:</span>
                                            <strong class="ml-1 tabular-nums text-white" x-text="formatCurrency(summary.monthlyPrincipal)"></strong>
                                        </div>
                                        <div>
                                            <span class="text-zinc-400">Lãi kỳ đầu:</span>
                                            <strong class="ml-1 tabular-nums text-amber-300" x-text="formatCurrency(summary.firstMonthInterest)"></strong>
                                        </div>
                                        <div>
                                            <span class="text-zinc-400">Tổng lãi:</span>
                                            <strong class="ml-1 tabular-nums text-amber-300" x-text="formatCurrency(summary.totalInterest)"></strong>
                                        </div>
                                        <div>
                                            <span class="text-zinc-400">Gốc còn lại:</span>
                                            <strong class="ml-1 tabular-nums text-emerald-300" x-text="formatCurrency(summary.remainingPrincipal)"></strong>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="h-9 rounded-lg bg-[#1C69D4] px-4 text-xs font-bold text-white hover:bg-[#1657B0] cursor-pointer">
                                            Cập nhật tiến độ
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            <!-- Section 2: 360° Full Interaction History Tabs -->
            <x-admin.card class="!p-6" x-data="{ tab: 'appointments' }">
                <div class="mb-5 flex flex-wrap items-center gap-2 border-b border-white/[0.08] pb-4">
                    <button type="button" @click="tab = 'appointments'" :class="tab === 'appointments' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:text-white'" class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer">
                        Lịch hẹn xe ({{ $vehicleAppointments->count() }})
                    </button>
                    <button type="button" @click="tab = 'services'" :class="tab === 'services' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:text-white'" class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer">
                        Dịch vụ xưởng ({{ $serviceBookings->count() }})
                    </button>
                    <button type="button" @click="tab = 'orders'" :class="tab === 'orders' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:text-white'" class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer">
                        Đơn phụ kiện ({{ $accessoryOrders->count() }})
                    </button>
                    <button type="button" @click="tab = 'ai'" :class="tab === 'ai' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:text-white'" class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer">
                        Hội thoại AI ({{ $aiSessions->count() }})
                    </button>
                    <button type="button" @click="tab = 'emails'" :class="tab === 'emails' ? 'border-[#1C69D4] bg-[#1C69D4]/20 text-white' : 'border-white/10 bg-[#070A10] text-zinc-400 hover:text-white'" class="rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors cursor-pointer">
                        Lịch sử thư đã gửi ({{ $emailLogs->count() }})
                    </button>
                </div>

                <!-- Tab 1: Vehicle Appointments -->
                <div x-show="tab === 'appointments'" class="space-y-3">
                    @forelse($vehicleAppointments as $appt)
                        <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/70 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-[#70A7FF]">
                                        {{ $appt->publicReferenceCode() }}
                                        @if($appt->daily_sequence_code)
                                            <span class="text-zinc-500">·</span> <span class="text-zinc-300">{{ $appt->daily_sequence_code }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-white">{{ $appt->product?->name ?? 'Tư vấn tại Showroom' }} · {{ $appt->type->label() }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-400">
                                        {{ $appt->appointment_date?->format('H:i — d/m/Y') }} · Sales: {{ $appt->assignedSales?->name ?? 'Chưa phân công' }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $appt->status->color() }}">
                                        {{ $appt->status->label() }}
                                    </span>
                                    @if($appt->completionOutcome() === \App\Models\Appointment::OUTCOME_PURCHASED)
                                        <span class="rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300">
                                            Đã mua xe{{ $appt->careVoucherCode() ? ' · ' . $appt->careVoucherCode() : '' }}
                                        </span>
                                    @elseif($appt->completionOutcome() === \App\Models\Appointment::OUTCOME_VISITED_NO_PURCHASE)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
                                            Chưa mua xe
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-zinc-500">Chưa có lịch hẹn tư vấn / lái thử xe.</p>
                    @endforelse
                </div>

                <!-- Tab 2: Service Bookings -->
                <div x-show="tab === 'services'" class="space-y-3" style="display: none;">
                    @forelse($serviceBookings as $srv)
                        <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/70 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-[#70A7FF]">{{ $srv->publicReferenceCode() }}</p>
                                    <p class="mt-1 text-sm font-bold text-white">{{ $srv->servicePackageLabel() ?: $srv->type->label() }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-400">{{ $srv->appointment_date?->format('H:i — d/m/Y') }}</p>
                                </div>
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $srv->status->color() }}">
                                    {{ $srv->status->label() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-zinc-500">Chưa có lịch dịch vụ chăm sóc xe.</p>
                    @endforelse
                </div>

                <!-- Tab 3: Accessory Orders -->
                <div x-show="tab === 'orders'" class="space-y-3" style="display: none;">
                    @forelse($accessoryOrders as $order)
                        <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/70 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-[#70A7FF]">{{ $order->publicReferenceCode() }}</p>
                                    <p class="mt-1 text-sm font-bold text-white">{{ $order->product?->name ?? 'Phụ kiện BMW' }} × {{ $order->quantity }}</p>
                                    <p class="mt-0.5 text-xs tabular-nums text-emerald-300 font-semibold">{{ number_format($order->total_amount, 0, ',', '.') }} VNĐ · {{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $order->statusColorClass() }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                    @if(Auth::user()?->canAccessCrm())
                                        <a href="{{ route('admin.accessory-orders.show', $order) }}" class="text-[11px] font-semibold text-[#70A7FF] hover:underline">
                                            Mở đơn hàng →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-zinc-500">Chưa có đơn phụ kiện nào.</p>
                    @endforelse
                </div>

                <!-- Tab 4: AI Chat Sessions -->
                <div x-show="tab === 'ai'" class="space-y-3" style="display: none;">
                    @forelse($aiSessions as $session)
                        <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/70 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-white">Phiên Chat AI #{{ $session->id }}</p>
                                    <p class="mt-0.5 text-[11px] text-zinc-400">
                                        {{ $session->messages->count() }} tin nhắn · Cập nhật {{ $session->last_seen_at?->format('H:i · d/m/Y') ?? $session->updated_at?->format('H:i · d/m/Y') }}
                                    </p>
                                </div>
                                @if(Auth::user()?->canAccessCrm())
                                    <a href="{{ route('admin.ai-conversations.show', $session) }}" class="rounded-lg border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-3 py-1.5 text-[11px] font-semibold text-[#70A7FF] hover:bg-[#1C69D4] hover:text-white">
                                        Xem hội thoại →
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-zinc-500">Chưa có phiên hội thoại AI nào liên kết với khách hàng này.</p>
                    @endforelse
                </div>

                <!-- Tab 5: Sent Email History (customer_email_logs) -->
                <div x-show="tab === 'emails'" class="space-y-3" style="display: none;" data-customer-email-logs-tab>
                    @forelse($emailLogs as $log)
                        <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/70 p-4 space-y-2">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $log->typeBadgeClass() }}">
                                            {{ $log->typeLabel() }}
                                        </span>
                                        @if(filled($log->template_code))
                                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] font-medium text-zinc-300">
                                                Mẫu: {{ $log->template_code }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1.5 text-sm font-bold text-white">{{ $log->subject }}</p>
                                </div>
                                <div class="shrink-0 text-right text-[11px] text-zinc-400">
                                    <p class="font-semibold tabular-nums text-zinc-300">{{ $log->sent_at?->format('H:i · d/m/Y') }}</p>
                                    <p class="mt-0.5 text-[10px] text-zinc-500">
                                        Người gửi: {{ $log->sender?->name ?? 'Hệ thống tự động' }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-[11px] text-zinc-400">
                                Tới: <span class="font-semibold text-[#70A7FF]">{{ $log->customer_email }}</span>
                            </p>
                            @if(filled($log->body_excerpt))
                                <p class="rounded-lg border border-white/[0.05] bg-[#0D121C] px-3 py-2 text-xs leading-relaxed text-zinc-300">
                                    {{ $log->body_excerpt }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-zinc-500">Chưa có lịch sử Email nào được ghi nhận cho khách hàng này.</p>
                    @endforelse
                </div>
            </x-admin.card>
        </div>

        <!-- Right Column: Quick 1-to-1 Email Composer (Active Email Templates) -->
        @php
            $quickEmailConfig = [
                'templates' => $quickTemplatesPayload,
                'baseVars' => $previewVariables,
                'planVars' => $installmentPlansVars,
                'selectedTemplateId' => (string) old('template_id', ''),
                'selectedPlanId' => (string) old('installment_plan_id', ''),
                'subjectOverride' => (string) old('subject_override', ''),
                'bodyOverride' => (string) old('body_override', ''),
            ];
        @endphp
        <aside class="sticky top-24">
            <script id="bmw-quick-email-config" type="application/json">{!! json_encode($quickEmailConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
            <x-admin.card class="!p-5">
                <div x-data="bmwCustomerQuickEmail('bmw-quick-email-config')" class="space-y-4">
                <div class="border-b border-white/[0.08] pb-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#38BDF8]">Chăm sóc 1-1</p>
                    <h2 class="mt-1 text-base font-bold text-white">Gửi Email từ Kho mẫu</h2>
                    <p class="mt-1 text-xs text-zinc-400">
                        Chọn mẫu Email đang hoạt động (Active) để tự động điền tên khách, mẫu xe và dữ liệu trả góp.
                    </p>
                </div>

                @if($activeEmailTemplates->isEmpty())
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-200">
                        Chưa có mẫu Email nào đang ở trạng thái <strong>Active</strong>.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.customers.send-email', $showRouteParams) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="route_email" value="{{ $routeEmail }}">

                        <x-admin.form-field name="recipient_email" label="Email người nhận *">
                            <input
                                id="recipient_email"
                                type="email"
                                name="recipient_email"
                                value="{{ old('recipient_email', $profile['email']) }}"
                                required
                                placeholder="khachhang@example.com"
                                class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>

                        <x-admin.form-field name="template_id" label="Chọn mẫu Email (Active) *">
                            <x-ui.custom-select
                                id="quick-email-template"
                                name="template_id"
                                x-model="selectedTemplateId"
                                @change="loadSelectedTemplate()"
                                :value="(string) old('template_id', '')"
                                :options="$quickTemplateOptions"
                            />
                        </x-admin.form-field>

                        @if($installmentPlans->isNotEmpty())
                            <x-admin.form-field name="installment_plan_id" label="Dữ liệu hồ sơ trả góp điền biến">
                                <x-ui.custom-select
                                    id="quick-email-plan"
                                    name="installment_plan_id"
                                    x-model="selectedPlanId"
                                    @change="loadSelectedTemplate()"
                                    :value="(string) old('installment_plan_id', '')"
                                    :options="$installmentSelectOptions"
                                />
                            </x-admin.form-field>
                        @endif

                        <x-admin.form-field name="subject_override" label="Tiêu đề thư (có thể chỉnh nhanh)">
                            <input
                                id="subject_override"
                                type="text"
                                name="subject_override"
                                x-model="subjectOverride"
                                placeholder="Sẽ tự động điền khi chọn mẫu Email..."
                                class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none"
                            >
                        </x-admin.form-field>

                        <x-admin.form-field name="body_override" label="Nội dung thư (đã tự động điền biến)">
                            <textarea
                                id="body_override"
                                name="body_override"
                                rows="7"
                                x-model="bodyOverride"
                                placeholder="Chọn mẫu Email ở trên để xem trước và tùy chỉnh..."
                                class="w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 py-2.5 text-xs leading-relaxed text-white focus:border-[#1C69D4] focus:outline-none"
                            ></textarea>
                        </x-admin.form-field>

                        <button
                            type="submit"
                            class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#1C69D4] px-5 text-xs font-bold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#1657B0] cursor-pointer"
                        >
                            Gửi Email chăm sóc ngay
                        </button>
                    </form>
                @endif
                </div>
            </x-admin.card>
        </aside>
    </div>
</x-admin-layout>
