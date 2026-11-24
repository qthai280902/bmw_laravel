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
        eyebrow="Customer 360° · Hồ sơ Khách hàng"
        :title="$profile['name']"
        accent="360°"
        :description="'SĐT: ' . $profile['phone'] . ' · Email: ' . ($profile['email'] ?: 'Chưa có') . ' · Sales phụ trách: ' . $profile['primary_sales']"
    >
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                @if($profile['phone'] !== 'N/A')
                    <a
                        href="tel:{{ $profile['phone'] }}"
                        class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-emerald-500/35 bg-emerald-500/15 px-4 text-xs font-semibold text-emerald-300 transition-colors hover:bg-emerald-500 hover:text-slate-950"
                    >
                        Gọi khách
                    </a>
                @endif
                <a
                    href="{{ route('admin.customers.index') }}"
                    class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-[#131A29] px-4 text-xs font-semibold text-zinc-200 transition-colors hover:border-white/25 hover:text-white"
                >
                    ← Danh sách khách hàng
                </a>
            </div>
        </x-slot>
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
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-3.5">
            <p class="text-[11px] font-semibold text-zinc-400">Chi tiêu phụ kiện</p>
            <p class="mt-1 truncate text-lg font-extrabold tabular-nums text-emerald-300">{{ number_format($kpis['total_spend'], 0, ',', '.') }} đ</p>
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

                <!-- Create New Installment Plan Form -->
                <form
                    x-show="showCreatePlan"
                    x-collapse
                    method="POST"
                    action="{{ route('admin.customers.installments.store', $showRouteParams) }}"
                    class="rounded-2xl border border-[#1C69D4]/30 bg-[#070A10]/90 p-5 space-y-4"
                >
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

                        <x-admin.form-field name="loan_amount" label="Tổng khoản vay (VNĐ) *">
                            <input type="number" name="loan_amount" value="{{ old('loan_amount', 2000000000) }}" min="1000000" step="1000000" required class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="monthly_payment" label="Thanh toán / tháng (VNĐ) *">
                            <input type="number" name="monthly_payment" value="{{ old('monthly_payment', 42000000) }}" min="100000" step="100000" required class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="interest_rate" label="Lãi suất (%/năm) *">
                            <input type="number" name="interest_rate" value="{{ old('interest_rate', '7.9') }}" min="0" max="50" step="0.01" required class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="next_due_date" label="Ngày đến hạn kỳ tiếp theo">
                            <input type="date" name="next_due_date" value="{{ old('next_due_date', now()->addMonth()->format('Y-m-d')) }}" class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="term_months" label="Tổng số kỳ hạn (tháng) *">
                            <input type="number" name="term_months" value="{{ old('term_months', 60) }}" min="1" max="240" required class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>

                        <x-admin.form-field name="paid_installments" label="Số kỳ đã thanh toán *">
                            <input type="number" name="paid_installments" value="{{ old('paid_installments', 0) }}" min="0" max="240" required class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs tabular-nums text-white focus:border-[#1C69D4] focus:outline-none">
                        </x-admin.form-field>
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
                                $progress = $plan->progressPercent();
                            @endphp
                            <div
                                x-data="{ editing: false }"
                                class="rounded-2xl border border-white/[0.08] bg-[#070A10]/80 p-4 space-y-3"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-white">{{ $plan->bank_name }}</span>
                                            <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $plan->statusColorClass() }}">
                                                {{ $plan->statusLabel() }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-zinc-400">
                                            {{ $plan->product?->name ?? 'Hợp đồng tài chính BMW' }}
                                            @if($plan->appointment)
                                                · Lịch hẹn <span class="text-[#70A7FF]">{{ $plan->appointment->customer_order_code }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="editing = !editing"
                                        class="h-8 rounded-lg border border-white/10 bg-[#131A29] px-3 text-[11px] font-semibold text-zinc-300 hover:border-[#1C69D4] hover:text-white cursor-pointer"
                                        x-text="editing ? 'Đóng sửa' : 'Cập nhật kỳ / lãi suất'"
                                    ></button>
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-xs">
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Tổng dư nợ vay</p>
                                        <p class="mt-1 font-bold tabular-nums text-white">{{ number_format($plan->loan_amount, 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Trả hàng tháng</p>
                                        <p class="mt-1 font-bold tabular-nums text-sky-300">{{ number_format($plan->monthly_payment, 0, ',', '.') }} đ</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Lãi suất áp dụng</p>
                                        <p class="mt-1 font-bold tabular-nums text-amber-300">{{ rtrim(rtrim(number_format($plan->interest_rate, 2, '.', ''), '0'), '.') }}%/năm</p>
                                    </div>
                                    <div class="rounded-xl border border-white/[0.06] bg-[#0D121C] p-3">
                                        <p class="text-[10px] text-zinc-400">Kỳ đến hạn kế tiếp</p>
                                        <p class="mt-1 font-bold tabular-nums text-emerald-300">{{ $plan->next_due_date?->format('d/m/Y') ?? 'Đã tất toán' }}</p>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1.5 flex items-center justify-between text-[11px]">
                                        <span class="font-semibold text-zinc-300">
                                            Tiến độ thanh toán: <strong class="tabular-nums text-white">{{ $plan->paid_installments }}/{{ $plan->term_months }} kỳ</strong> (còn {{ $plan->remainingInstallments() }} kỳ)
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

                                <!-- Inline Edit Form -->
                                <form
                                    x-show="editing"
                                    x-collapse
                                    method="POST"
                                    action="{{ route('admin.customers.installments.update', $updateParams) }}"
                                    class="border-t border-white/[0.08] pt-4 space-y-3"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="route_email" value="{{ $routeEmail }}">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ngân hàng</label>
                                            <input type="text" name="bank_name" value="{{ $plan->bank_name }}" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Tổng vay (VNĐ)</label>
                                            <input type="number" name="loan_amount" value="{{ $plan->loan_amount }}" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Góp / tháng (VNĐ)</label>
                                            <input type="number" name="monthly_payment" value="{{ $plan->monthly_payment }}" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Lãi suất (%/năm)</label>
                                            <input type="number" step="0.01" name="interest_rate" value="{{ $plan->interest_rate }}" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Đã đóng / Tổng kỳ</label>
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" name="paid_installments" value="{{ $plan->paid_installments }}" min="0" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-2.5 text-xs tabular-nums text-white">
                                                <span class="text-zinc-500">/</span>
                                                <input type="number" name="term_months" value="{{ $plan->term_months }}" min="1" required class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-2.5 text-xs tabular-nums text-white">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ngày đến hạn kế tiếp</label>
                                            <input type="date" name="next_due_date" value="{{ $plan->next_due_date?->format('Y-m-d') }}" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs tabular-nums text-white">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Trạng thái</label>
                                            <x-ui.custom-select compact name="status" :value="$plan->status" :options="$installmentStatusOptions" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-semibold text-zinc-400">Ghi chú</label>
                                            <input type="text" name="notes" value="{{ $plan->notes }}" class="h-9 w-full rounded-lg border border-white/10 bg-[#0D121C] px-3 text-xs text-white">
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
