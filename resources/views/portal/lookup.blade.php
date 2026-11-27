<x-app-layout>
    @section('title', 'Tra Cứu Hồ Sơ & Lịch Trả Góp | BMW Showroom Vietnam')

    <section class="guest-luxury min-h-screen bg-[#F1F5F9] py-10 px-4 text-slate-900 sm:py-14">
        <div class="mx-auto max-w-5xl space-y-6">
            {{-- Lookup Form Card --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#1C69D4]">
                        BMW Client Relations &amp; Financial Services
                    </p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        Cổng tra cứu hồ sơ &amp; lịch trả góp BMW
                    </h1>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        Nhập <strong>Số điện thoại</strong> hoặc <strong>Địa chỉ Email</strong> đã giao dịch tại BMW Showroom để kiểm tra dư nợ ngân hàng, lịch thanh toán từng tháng và lịch sử dịch vụ chính hãng.
                    </p>
                </div>

                <form method="POST" action="{{ route('customer.lookup.search') }}" class="mt-6">
                    @csrf
                    <label for="customer-lookup-query" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Số điện thoại hoặc Email khách hàng
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="customer-lookup-query"
                            type="text"
                            name="query"
                            value="{{ old('query', $searchQuery ?? '') }}"
                            required
                            autocomplete="off"
                            placeholder="Ví dụ: 0901234567, +84901234567 hoặc khachhang@email.com"
                            class="h-12 flex-1 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#1C69D4] focus:outline-none focus:ring-2 focus:ring-[#1C69D4]/20"
                        >
                        <button
                            type="submit"
                            class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#1C69D4] px-7 text-xs font-bold uppercase tracking-wider text-white shadow-sm transition-colors hover:bg-[#1557B0] cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2m1.7-4.3a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                            </svg>
                            <span>Tra cứu hồ sơ</span>
                        </button>
                    </div>
                    @error('query')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            {{-- Polite Empty Result --}}
            @if($searched && ! $found)
                <div
                    data-lookup-empty-state
                    class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-slate-800 shadow-sm"
                >
                    <p class="text-sm font-bold text-amber-900">
                        Chưa tìm thấy hồ sơ giao dịch khớp với thông tin "{{ $searchQuery }}"
                    </p>
                    <p class="mt-1.5 text-sm leading-relaxed text-amber-800">
                        Quý khách vui lòng kiểm tra lại Số điện thoại hoặc địa chỉ Email đã dùng khi đặt lịch hẹn, mua xe, trả góp hoặc đặt phụ kiện tại BMW Showroom. Nếu cần hỗ trợ ngay, vui lòng liên hệ Hotline <strong>1900 123 456</strong>.
                    </p>
                </div>
            @endif

            @if($found && $profile)
                {{-- 1. Customer & Dedicated Advisor Summary Card --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#1C69D4]">Hồ sơ khách hàng chính hãng</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">{{ $profile['name'] }}</h2>
                        </div>
                        <span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3.5 py-1.5 text-xs font-bold text-[#1C69D4]">
                            Khách hàng BMW Showroom
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                        <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5">
                            <p class="text-xs font-medium text-slate-500">Số điện thoại liên hệ</p>
                            <p class="mt-1 font-bold tabular-nums text-slate-900">{{ $profile['phone'] }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5">
                            <p class="text-xs font-medium text-slate-500">Địa chỉ Email</p>
                            <p class="mt-1 truncate font-semibold text-slate-900" title="{{ $profile['email'] }}">{{ $profile['email'] }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3.5">
                            <p class="text-xs font-medium text-slate-500">Chuyên viên phụ trách</p>
                            <p class="mt-1 font-bold text-[#1C69D4]">{{ $profile['advisor_name'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- 2 & 3. Installment Plans & Detailed Period-by-Period Schedule (Top Priority) --}}
                @if($installmentEntries->isNotEmpty())
                    <div class="space-y-6">
                        @foreach($installmentEntries as $entry)
                            @php
                                /** @var \App\Models\CustomerInstallmentPlan $plan */
                                $plan = $entry['plan'];
                                $calc = $entry['calc'];
                                [$planBadgeBg, $planBadgeBorder, $planBadgeText] = match ($plan->status) {
                                    \App\Models\CustomerInstallmentPlan::STATUS_COMPLETED => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-700'],
                                    \App\Models\CustomerInstallmentPlan::STATUS_OVERDUE => ['bg-rose-50', 'border-rose-200', 'text-rose-700'],
                                    default => ['bg-blue-50', 'border-blue-200', 'text-[#1C69D4]'],
                                };
                            @endphp
                            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-6" data-installment-plan-card>
                                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-[#1C69D4]">
                                            Hồ sơ trả góp &amp; Dư nợ ngân hàng
                                        </p>
                                        <h3 class="mt-1.5 text-xl font-bold text-slate-900">
                                            {{ $plan->bank_name }} — {{ $plan->product?->name ?? 'Hợp đồng tài chính BMW' }}
                                        </h3>
                                        <p class="mt-1 text-xs font-medium text-slate-500">
                                            Phương thức tính lãi: <strong class="text-slate-800">{{ $calc['interest_method_label'] }}</strong>
                                            @if($plan->appointment?->customer_order_code)
                                                · Mã tham chiếu lịch hẹn: <strong class="text-[#1C69D4]">{{ $plan->appointment->customer_order_code }}</strong>
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-lg border px-3.5 py-1.5 text-xs font-bold {{ $planBadgeBg }} {{ $planBadgeBorder }} {{ $planBadgeText }}">
                                        {{ $plan->statusLabel() }}
                                    </span>
                                </div>

                                {{-- Financial Summary Grid --}}
                                <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                                    @if($calc['vehicle_price'])
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                                            <p class="text-xs font-medium text-slate-500">Giá xe niêm yết</p>
                                            <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">{{ number_format($calc['vehicle_price'], 0, ',', '.') }} đ</p>
                                        </div>
                                    @endif
                                    @if($calc['down_payment'] !== null)
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                                            <p class="text-xs font-medium text-slate-500">Trả trước đối ứng</p>
                                            <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">{{ number_format($calc['down_payment'], 0, ',', '.') }} đ</p>
                                        </div>
                                    @endif
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                                        <p class="text-xs font-medium text-slate-500">Tổng khoản vay gốc</p>
                                        <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">{{ number_format($calc['loan_amount'], 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                                        <p class="text-xs font-medium text-slate-500">Lãi suất áp dụng</p>
                                        <p class="mt-1 text-sm font-bold tabular-nums text-[#1C69D4]">
                                            {{ rtrim(rtrim(number_format((float) $calc['interest_rate'], 2, '.', ''), '0'), '.') }}%/năm
                                            <span class="block text-[11px] font-medium text-slate-500">Ưu đãi {{ $calc['fixed_rate_months'] }} tháng đầu</span>
                                            @if($calc['floating_interest_rate'] !== null && $calc['floating_rate_adjustment_months'] > 0)
                                                <span class="block text-[11px] font-semibold text-slate-700">Sau ưu đãi: {{ rtrim(rtrim(number_format((float) $calc['floating_interest_rate'], 2, '.', ''), '0'), '.') }}%/năm ({{ $calc['floating_rate_adjustment_label'] }})</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="rounded-lg border border-blue-200 bg-blue-50/70 p-3.5">
                                        <p class="text-xs font-semibold text-[#1C69D4]">Dư nợ gốc còn lại</p>
                                        <p class="mt-1 text-base font-extrabold tabular-nums text-slate-900">{{ number_format($calc['remaining_principal'], 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-lg border border-indigo-200 bg-indigo-50/60 p-3.5">
                                        <p class="text-xs font-semibold text-indigo-700">Ước tính gốc + lãi còn đóng</p>
                                        <p class="mt-1 text-base font-extrabold tabular-nums text-slate-900">{{ number_format($calc['estimated_remaining_total'], 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                                        <p class="text-xs font-medium text-slate-500">Tiến độ kỳ hạn</p>
                                        <p class="mt-1 text-sm font-bold tabular-nums text-slate-900">
                                            {{ $calc['paid_installments'] }} / {{ $calc['term_months'] }} kỳ
                                            <span class="text-xs font-medium text-slate-500">(còn {{ $calc['remaining_installments'] }} kỳ)</span>
                                        </p>
                                    </div>
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-3.5">
                                        <p class="text-xs font-semibold text-emerald-700">Ngày đến hạn kỳ kế tiếp</p>
                                        <p class="mt-1 text-sm font-bold tabular-nums text-emerald-900">{{ $calc['next_due_date_label'] }}</p>
                                    </div>
                                </div>

                                {{-- Progress Bar --}}
                                <div>
                                    <div class="mb-1.5 flex items-center justify-between text-xs font-semibold text-slate-600">
                                        <span>Tiến độ hoàn thành hợp đồng</span>
                                        <span class="font-bold tabular-nums text-[#1C69D4]">{{ $calc['progress_percent'] }}%</span>
                                    </div>
                                    <div class="h-2.5 w-full overflow-hidden rounded-lg bg-slate-200">
                                        <div class="h-full rounded-lg bg-[#1C69D4]" style="width: {{ $calc['progress_percent'] }}%"></div>
                                    </div>
                                </div>

                                {{-- Period-by-Period Schedule Table (Kỳ 1 -> Kỳ N) --}}
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h4 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                                            Bảng lịch thanh toán chi tiết từng tháng (Kỳ 1 &rarr; Kỳ {{ $calc['term_months'] }})
                                        </h4>
                                        <span class="text-xs text-slate-500">Các kỳ đã đóng được gạch ngang để Quý khách dễ đối chiếu</span>
                                    </div>
                                    @if(! empty($calc['early_settlement_fee_note']))
                                        <p class="rounded-lg border border-blue-200 bg-blue-50/70 px-3.5 py-2 text-xs text-slate-700">
                                            <strong class="text-[#1C69D4]">Phí tất toán trước hạn theo hợp đồng:</strong> {{ $calc['early_settlement_fee_note'] }}
                                        </p>
                                    @endif

                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="w-full min-w-[760px] border-collapse text-left text-xs">
                                            <thead>
                                                <tr class="border-b border-slate-200 bg-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                                    <th class="px-3.5 py-3">Kỳ</th>
                                                    <th class="px-3.5 py-3">Tháng / Hạn đóng</th>
                                                    <th class="px-3.5 py-3 text-right">Dư nợ đầu kỳ</th>
                                                    <th class="px-3.5 py-3 text-right">Tiền gốc</th>
                                                    <th class="px-3.5 py-3 text-right">Tiền lãi</th>
                                                    <th class="px-3.5 py-3 text-right">Tổng phải đóng</th>
                                                    <th class="px-3.5 py-3 text-right">Dư nợ cuối kỳ</th>
                                                    <th class="px-3.5 py-3 text-right">Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 bg-white">
                                                @foreach($calc['schedule'] as $row)
                                                    @php
                                                        $rowStatus = $row['status'];
                                                        $rowClass = match ($rowStatus) {
                                                            'paid' => 'bg-slate-50/70',
                                                            'current' => 'border-l-4 border-l-[#1C69D4] bg-blue-50/60 ring-1 ring-inset ring-[#1C69D4]/40',
                                                            'overdue' => 'border-l-4 border-l-rose-600 bg-rose-50/80 ring-1 ring-inset ring-rose-300',
                                                            default => 'hover:bg-slate-50',
                                                        };
                                                        $cellStrikeClass = $rowStatus === 'paid' ? 'opacity-50 line-through' : '';
                                                    @endphp
                                                    <tr data-schedule-period="{{ $row['period'] }}" data-schedule-status="{{ $rowStatus }}" class="{{ $rowClass }}">
                                                        <td class="px-3.5 py-3 font-bold tabular-nums text-slate-900 {{ $cellStrikeClass }}">
                                                            Kỳ {{ $row['period'] }}
                                                        </td>
                                                        <td class="px-3.5 py-3 tabular-nums text-slate-700 {{ $cellStrikeClass }}">
                                                            <span class="font-semibold text-slate-900">{{ $row['due_date_label'] }}</span>
                                                            <span class="block text-[11px] text-slate-500">{{ $row['month_label'] }}</span>
                                                        </td>
                                                        <td class="px-3.5 py-3 text-right tabular-nums text-slate-700 {{ $cellStrikeClass }}">
                                                            {{ number_format($row['opening_balance'], 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="px-3.5 py-3 text-right font-semibold tabular-nums text-slate-800 {{ $cellStrikeClass }}">
                                                            {{ number_format($row['principal_amount'], 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="px-3.5 py-3 text-right tabular-nums text-slate-700 {{ $cellStrikeClass }}">
                                                            {{ number_format($row['interest_amount'], 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="px-3.5 py-3 text-right font-bold tabular-nums text-slate-900 {{ $cellStrikeClass }}">
                                                            {{ number_format($row['total_payment'], 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="px-3.5 py-3 text-right tabular-nums text-slate-700 {{ $cellStrikeClass }}">
                                                            {{ number_format($row['closing_balance'], 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="whitespace-nowrap px-3.5 py-3 text-right">
                                                            @if($rowStatus === 'paid')
                                                                <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                                                    Đã thanh toán
                                                                </span>
                                                            @elseif($rowStatus === 'overdue')
                                                                <span class="inline-flex items-center rounded-md border border-rose-300 bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">
                                                                    Quá hạn
                                                                </span>
                                                            @elseif($rowStatus === 'current')
                                                                <span class="inline-flex items-center rounded-md border border-[#1C69D4]/40 bg-[#1C69D4]/10 px-2 py-0.5 text-[11px] font-bold text-[#1C69D4]">
                                                                    Kỳ sắp tới
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                                    Chưa đến hạn
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- 4. Showroom Services & Transactions Used --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-[#1C69D4]">Lịch sử đồng hành cùng BMW</p>
                        <h3 class="mt-1 text-xl font-bold text-slate-900">Dịch vụ &amp; Giao dịch tại Showroom</h3>
                    </div>

                    {{-- 4a. Vehicle Appointments --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                            Lịch hẹn tư vấn &amp; lái thử xe ({{ $vehicleAppointments->count() }})
                        </h4>
                        @forelse($vehicleAppointments as $appt)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold tabular-nums text-[#1C69D4]">{{ $appt->publicReferenceCode() }}</p>
                                    <p class="text-sm font-bold text-slate-900">
                                        {{ $appt->product?->name ?? 'Tư vấn dòng xe BMW' }} — {{ $appt->typeLabel() }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Thời gian hẹn: <strong class="tabular-nums text-slate-700">{{ $appt->appointment_date?->format('H:i — d/m/Y') ?? 'Chưa ấn định' }}</strong>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700">
                                        {{ $appt->status->label() }}
                                    </span>
                                    @if($appt->completionOutcome() === \App\Models\Appointment::OUTCOME_PURCHASED)
                                        <span class="rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                            Đã mua xe{{ $appt->careVoucherCode() ? ' · Đặc quyền ' . $appt->careVoucherCode() : '' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-xs text-slate-500">Chưa có lịch hẹn xe tại Showroom.</p>
                        @endforelse
                    </div>

                    {{-- 4b. Service Bookings --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                            Lịch bảo dưỡng &amp; chăm sóc xe chính hãng ({{ $serviceBookings->count() }})
                        </h4>
                        @forelse($serviceBookings as $srv)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold tabular-nums text-[#1C69D4]">{{ $srv->publicReferenceCode() }}</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $srv->servicePackageLabel() ?: $srv->typeLabel() }}</p>
                                    <p class="text-xs text-slate-500">
                                        Thời gian hẹn: <strong class="tabular-nums text-slate-700">{{ $srv->appointment_date?->format('H:i — d/m/Y') ?? 'Chưa ấn định' }}</strong>
                                    </p>
                                </div>
                                <span class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700">
                                    {{ $srv->status->label() }}
                                </span>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-xs text-slate-500">Chưa có lịch bảo dưỡng / chăm sóc xe.</p>
                        @endforelse
                    </div>

                    {{-- 4c. Accessory Orders --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                            Đơn hàng phụ kiện BMW Lifestyle ({{ $accessoryOrders->count() }})
                        </h4>
                        @forelse($accessoryOrders as $order)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold tabular-nums text-[#1C69D4]">{{ $order->publicReferenceCode() }}</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $order->product?->name ?? 'Phụ kiện BMW chính hãng' }} &times; {{ $order->quantity }}</p>
                                    <p class="text-xs font-semibold tabular-nums text-slate-700">
                                        {{ number_format((int) $order->total_amount, 0, ',', '.') }} VNĐ · {{ $order->created_at?->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                <span class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-700">
                                    {{ $order->statusLabel() }}
                                </span>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-xs text-slate-500">Chưa có đơn hàng phụ kiện.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
