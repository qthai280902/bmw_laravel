<x-admin-layout>
    <x-admin.page-header
        eyebrow="Customer intelligence"
        title="Cơ sở dữ liệu"
        accent="Khách hàng"
        description="Hệ thống CRM tổng hợp dữ liệu khách hàng, lọc theo trạng thái/dòng xe và điều phối chiến dịch ưu đãi cá nhân hóa."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng khách hàng</p>
            <p class="mt-1 text-2xl font-black tabular-nums text-white">{{ $customers->total() }}</p>
        </x-slot>

        @if(Auth::user()?->canManageSystem())
            <x-slot name="actions">
                <form method="POST" action="{{ route('admin.customers.distribute-sales') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-xl border border-[#38BDF8]/40 bg-[#38BDF8]/15 px-4 text-xs font-semibold text-[#38BDF8] transition-colors hover:bg-[#38BDF8] hover:text-black cursor-pointer"
                    >
                        <span>Chia đều hồ sơ chưa phân công</span>
                        <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-bold tabular-nums">{{ $unassignedTotal ?? 0 }}</span>
                    </button>
                </form>
            </x-slot>
        @endif
    </x-admin.page-header>

    <div class="space-y-8">
        <!-- Khu vực 1: Danh sách & Bộ lọc Khách hàng -->
        <section class="space-y-5">
            @php
                $salesSelectOptions = [
                    '' => 'Tất cả Sales',
                    'mine' => 'Khách của tôi',
                    'unassigned' => 'Chưa phân công',
                ];
                foreach ($salesRoster ?? [] as $salesMember) {
                    $salesSelectOptions[(string) $salesMember->id] = $salesMember->name;
                }
            @endphp
            <x-admin.card class="!p-5">
                <form action="{{ route('admin.customers.index') }}" method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
                    <div>
                        <label for="customer-search-input" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Tìm kiếm khách hàng</label>
                        <div class="relative">
                            <input
                                id="customer-search-input"
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Tên, Email, SĐT hoặc mã BMW/CT/KH..."
                                x-data
                                data-last-search="{{ request('search', '') }}"
                                @input.debounce.450ms="if ($el.value !== ($el.dataset.lastSearch || '')) { $el.dataset.lastSearch = $el.value; $el.form.submit(); }"
                                class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] pl-10 pr-4 text-xs font-semibold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all"
                            >
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <label for="customer-filter-status" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Trạng thái lịch hẹn</label>
                        <x-ui.custom-select
                            id="customer-filter-status"
                            name="status"
                            :value="$statusFilter"
                            :options="array_merge(['' => 'Tất cả trạng thái'], collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())"
                            :auto-submit="true"
                        />
                    </div>

                    <div>
                        <label for="customer-filter-product" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Mẫu xe quan tâm</label>
                        <x-ui.custom-select
                            id="customer-filter-product"
                            name="product_id"
                            :value="(string) ($productIdFilter ?? '')"
                            :options="['' => 'Tất cả dòng xe'] + $products->pluck('name', 'id')->all()"
                            :auto-submit="true"
                        />
                    </div>

                    <div>
                        <label for="customer-filter-sales" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Sales phụ trách</label>
                        <x-ui.custom-select
                            id="customer-filter-sales"
                            name="sales_id"
                            :value="(string) ($salesIdFilter ?? '')"
                            :options="$salesSelectOptions"
                            :auto-submit="true"
                        />
                    </div>

                    <div>
                        <label for="customer-filter-priority" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Độ ưu tiên Lead</label>
                        <x-ui.custom-select
                            id="customer-filter-priority"
                            name="lead_priority"
                            :value="(string) ($leadPriorityFilter ?? '')"
                            :options="[
                                '' => 'Mọi mức độ',
                                'hot' => 'HOT LEAD',
                                'warm' => 'TIỀM NĂNG',
                                'normal' => 'THAM KHẢO',
                            ]"
                            :auto-submit="true"
                        />
                    </div>

                    <div>
                        <label for="customer-filter-recent" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Tương tác gần đây</label>
                        <x-ui.custom-select
                            id="customer-filter-recent"
                            name="recent"
                            :value="(string) ($recentFilter ?? '')"
                            :options="[
                                '' => 'Mọi thời gian',
                                '7d' => '7 ngày qua',
                                '30d' => '30 ngày qua',
                                'this_month' => 'Tháng này',
                            ]"
                            :auto-submit="true"
                        />
                    </div>

                    <div class="flex items-center gap-2 lg:justify-end">
                        <button type="submit" class="h-10 whitespace-nowrap rounded-xl bg-[#1C69D4] px-4 text-[10px] font-semibold uppercase tracking-widest text-white shadow-sm shadow-[#1C69D4]/25 transition-all hover:bg-[#2575e6] cursor-pointer">
                            Lọc
                        </button>
                        @if(($activeFilterCount ?? 0) > 0)
                            <a href="{{ route('admin.customers.index') }}" class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-xl border border-white/10 bg-white/[0.03] px-3.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-300 transition-all hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                                Xóa ({{ $activeFilterCount }})
                            </a>
                        @endif
                    </div>
                </form>
            </x-admin.card>

            <!-- CRM Table -->
            <x-admin.card class="!p-0 overflow-hidden">
                <div class="overflow-x-auto lg:overflow-x-visible">
                    <table class="w-full lg:table-fixed border-collapse text-left">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-[#070A10]/70">
                                <th class="w-[31%] px-4 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Thông tin khách hàng</th>
                                <th class="w-[27%] px-4 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Liên lạc</th>
                                <th class="w-[11%] px-3 py-4 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Tương tác</th>
                                <th class="w-[14%] px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Gần nhất</th>
                                <th class="w-[17%] px-4 py-4 text-right text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Hồ sơ 360°</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.06]">
                            @forelse($customers as $customer)
                                @php
                                    $normalizedPhone = \App\Models\CustomerInstallmentPlan::normalizePhoneDigits($customer->phone);
                                    $detailPhoneParam = $normalizedPhone !== '' ? $normalizedPhone : 'khach-hang';
                                    $detailParams = ['phone' => $detailPhoneParam];
                                    if (filled($customer->email)) {
                                        $detailParams['email'] = $customer->email;
                                    }
                                    $detailUrl = route('admin.customers.show', $detailParams);
                                    $emailLower = mb_strtolower(trim((string) ($customer->email ?? '')));
                                    $hasInstallment = ($emailLower !== '' && isset($installmentEmails[$emailLower]))
                                        || ($normalizedPhone !== '' && isset($installmentPhones[$normalizedPhone]));
                                @endphp
                                <tr class="group transition-colors hover:bg-[#131A29]/60">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#070A10] transition-colors group-hover:border-[#1C69D4]">
                                                <span class="text-xs font-black text-zinc-400 transition-colors group-hover:text-[#70A7FF]">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ $detailUrl }}" class="mb-1 block truncate text-sm font-black uppercase leading-none tracking-wider text-white transition-colors hover:text-[#70A7FF] group-hover:text-[#70A7FF]" title="{{ $customer->name }}">
                                                    {{ $customer->name }}
                                                </a>
                                                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-zinc-400">
                                                    @if(($customer->priority_rank ?? 4) === 1)
                                                        <span class="rounded-full border border-rose-500/30 bg-rose-500/15 px-2 py-0.5 text-rose-300">HOT LEAD</span>
                                                    @elseif(($customer->priority_rank ?? 4) === 2)
                                                        <span class="rounded-full border border-amber-500/30 bg-amber-500/15 px-2 py-0.5 text-amber-300">TIỀM NĂNG</span>
                                                    @endif
                                                    @if($hasInstallment)
                                                        <span class="rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2 py-0.5 text-emerald-300">Trả góp</span>
                                                    @endif
                                                    <span class="truncate max-w-full rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-sky-300">
                                                        {{ filled($customer->assigned_sales_name) ? 'Sales: ' . $customer->assigned_sales_name : 'Chưa gán Sales' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="space-y-1 min-w-0">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <svg class="h-3.5 w-3.5 shrink-0 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                <span class="truncate text-xs font-medium text-zinc-300" title="{{ $customer->email }}">{{ $customer->email }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <svg class="h-3.5 w-3.5 shrink-0 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                <span class="whitespace-nowrap text-xs font-medium tabular-nums text-zinc-400">{{ $customer->phone }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-white/10 bg-[#070A10] px-2.5 py-1">
                                            <span class="text-[10px] font-bold tabular-nums text-white">{{ $customer->interactions_count }}</span>
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400">Lượt</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right whitespace-nowrap">
                                        <div class="text-xs font-bold uppercase tracking-wider tabular-nums text-zinc-300">
                                            {{ \Carbon\Carbon::parse($customer->last_interaction)->format('d/m/Y') }}
                                        </div>
                                        <div class="mt-0.5 text-[10px] font-medium text-zinc-500">
                                            {{ \Carbon\Carbon::parse($customer->last_interaction)->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right whitespace-nowrap">
                                        <a
                                            href="{{ $detailUrl }}"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-3 text-[11px] font-semibold text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white"
                                        >
                                            <span>Xem chi tiết</span>
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <x-admin.empty-state
                                            title="Chưa có dữ liệu khách hàng"
                                            description="Các khách hàng đặt lịch hẹn hoặc tư vấn tại Showroom sẽ tự động xuất hiện tại đây."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            @if($customers->hasPages())
                <div>
                    {{ $customers->links() }}
                </div>
            @endif
        </section>

        <!-- Khu vực 2: Trung tâm Gửi Chiến Dịch Ưu Đãi Khách Cũ -->
        @if(Auth::user()?->canAccessContent())
            @php
                $initialAudience = old('audience', 'all');
                $initialProductId = old('product_id') !== null && old('product_id') !== '' ? (int) old('product_id') : null;
                $initialOnlyCompleted = $initialAudience === 'completed' || (bool) old('only_completed', false);
                $initialOnlyInstallment = $initialAudience === 'installment';
                $initialMatchedRecipients = $initialAudience === 'by_product' && $initialProductId === null
                    ? collect()
                    : collect($promotionAudienceTuples)
                        ->filter(function (array $row) use ($initialOnlyCompleted, $initialOnlyInstallment, $initialProductId): bool {
                            if ($initialOnlyInstallment && empty($row['has_installment'])) {
                                return false;
                            }

                            if ($initialOnlyCompleted && ! $row['completed']) {
                                return false;
                            }

                            if ($initialProductId !== null && $row['product_id'] !== $initialProductId) {
                                return false;
                            }

                            return true;
                        })
                        ->unique('key')
                        ->values();
                $initialMatchedCount = $initialMatchedRecipients->count();
                $campaignTemplateOptions = ['' => '-- Chọn mẫu từ Kho mẫu Email --'];
                $campaignTemplatesPayload = [];
                foreach ($activeEmailTemplates ?? [] as $emailTemplate) {
                    $campaignTemplateOptions[$emailTemplate->code] = $emailTemplate->name . ' (' . $emailTemplate->categoryLabel() . ')';
                    $campaignTemplatesPayload[] = [
                        'code' => $emailTemplate->code,
                        'name' => $emailTemplate->name,
                        'category' => $emailTemplate->category,
                        'subject' => $emailTemplate->subject,
                        'body_content' => $emailTemplate->body_content,
                    ];
                }
                $promotionCampaignConfig = [
                    'audience' => $initialAudience,
                    'productId' => (string) old('product_id', ''),
                    'onlyCompleted' => $initialOnlyCompleted,
                    'records' => $promotionAudienceTuples,
                    'templates' => $campaignTemplatesPayload,
                    'offerTitle' => (string) old('title', ''),
                    'offerContent' => (string) old('content', ''),
                    'suggestionsUrl' => route('admin.customers.purchased-suggestions'),
                ];
            @endphp
            <script id="bmw-promotion-campaign-config" type="application/json">{!! json_encode($promotionCampaignConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
            <section>
                <x-admin.card
                    class="!p-6 border-white/10"
                    x-data="bmwPromotionCampaign('bmw-promotion-campaign-config')"
                >
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/[0.08] pb-5">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-[#70A7FF]">BMW Loyalty & Retention</p>
                            <h2 class="mt-1 text-base font-black uppercase tracking-wider text-white">Trung tâm Gửi Chiến Dịch Ưu Đãi Khách Cũ</h2>
                        </div>
                        <span class="inline-flex whitespace-nowrap rounded-full border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-3.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#70A7FF]">
                            Cá nhân hóa theo dòng xe
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.customers.promotions.store') }}" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
                        @csrf

                        <!-- Cột trái (7/12): Soạn nội dung ưu đãi -->
                        <div class="space-y-5 lg:col-span-7">
                            @if(($activeEmailTemplates ?? collect())->isNotEmpty())
                                <div class="rounded-xl border border-[#1C69D4]/25 bg-[#1C69D4]/10 p-4">
                                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                        <label for="promo-template-loader" class="text-[11px] font-bold text-[#70A7FF]">
                                            Nạp nhanh từ Kho mẫu Email
                                        </label>
                                        <a href="{{ route('admin.email-templates.index') }}" class="text-[11px] font-semibold text-zinc-300 underline-offset-4 hover:text-white hover:underline">
                                            Quản lý Kho mẫu Email →
                                        </a>
                                    </div>
                                    <x-ui.custom-select
                                        id="promo-template-loader"
                                        name="_template_loader"
                                        x-model="selectedTemplateCode"
                                        @change="applyEmailTemplate(selectedTemplateCode)"
                                        value=""
                                        :options="$campaignTemplateOptions"
                                    />
                                    <p class="mt-2 text-[11px] text-zinc-400">
                                        Các biến như <code class="text-sky-300">&#123;&#123;customer_name&#125;&#125;</code>, <code class="text-sky-300">&#123;&#123;vehicle_name&#125;&#125;</code>, <code class="text-sky-300">&#123;&#123;interest_rate&#125;&#125;</code>, <code class="text-sky-300">&#123;&#123;next_due_date&#125;&#125;</code> sẽ tự động điền theo từng khách hàng khi phát thư.
                                    </p>
                                </div>
                            @endif

                            <x-admin.form-field name="title" label="Tiêu đề ưu đãi">
                                <input
                                    id="promo-title"
                                    type="text"
                                    name="title"
                                    x-model="offerTitle"
                                    value="{{ old('title') }}"
                                    required
                                    placeholder="Ví dụ: Tặng gói rửa xe & kiểm tra tổng quát 20 hạng mục miễn phí"
                                    class="h-11 w-full rounded-xl border border-white/10 bg-[#070A10] px-4 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                >
                            </x-admin.form-field>

                            <x-admin.form-field name="content" label="Nội dung chương trình ưu đãi">
                                <textarea
                                    id="promo-content"
                                    name="content"
                                    rows="8"
                                    x-model="offerContent"
                                    required
                                    placeholder="Nhập nội dung chi tiết ưu đãi, thời gian áp dụng và quyền lợi dành riêng cho khách hàng BMW..."
                                    class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-xs leading-relaxed text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                >{{ old('content') }}</textarea>
                            </x-admin.form-field>
                        </div>

                        <!-- Cột phải (5/12): Bộ lọc đối tượng chủ động & Live Preview Box -->
                        <div class="flex flex-col justify-between space-y-5 rounded-2xl border border-white/[0.08] bg-[#070A10] p-5 lg:col-span-5">
                            <div class="space-y-4">
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Bộ lọc đối tượng mục tiêu</p>

                                <!-- Autocomplete Tìm & Đề xuất Khách đã mua hàng -->
                                <div class="space-y-2.5 rounded-xl border border-[#38BDF8]/25 bg-[#0D121C] p-3.5" @click.outside="suggestionsOpen = false">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="promo-purchased-search" class="text-[11px] font-bold text-[#38BDF8]">
                                            Chọn đích danh khách đã mua hàng
                                        </label>
                                        <button
                                            type="button"
                                            x-show="selectedRecipients.length > 0"
                                            @click="clearSelectedRecipients()"
                                            class="text-[10px] font-bold text-rose-300 underline-offset-4 hover:text-rose-200 hover:underline cursor-pointer"
                                        >
                                            Xóa chọn thủ công
                                        </button>
                                    </div>

                                    <div class="relative">
                                        <input
                                            id="promo-purchased-search"
                                            type="text"
                                            x-model="searchQuery"
                                            @input.debounce.200ms="fetchPurchasedSuggestions()"
                                            @focus="fetchPurchasedSuggestions()"
                                            @keydown.escape="suggestionsOpen = false"
                                            autocomplete="off"
                                            placeholder="Tìm khách đã mua theo Email, SĐT hoặc Tên..."
                                            class="h-10 w-full rounded-lg border border-white/10 bg-[#070A10] pl-9 pr-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                                        >
                                        <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2m1.7-4.3a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                                        </svg>

                                        <div
                                            x-show="suggestionsOpen"
                                            style="display: none;"
                                            data-purchased-suggestions-dropdown
                                            class="absolute left-0 right-0 top-full z-30 mt-1.5 max-h-64 overflow-y-auto rounded-xl border border-white/15 bg-[#0D121C] p-1.5 shadow-2xl shadow-black/80"
                                        >
                                            <template x-if="suggestionsLoading">
                                                <p class="px-3 py-2.5 text-xs text-zinc-400">Đang tìm khách đã mua hàng...</p>
                                            </template>

                                            <template x-if="!suggestionsLoading && suggestions.length === 0">
                                                <p class="px-3 py-2.5 text-xs text-zinc-500">Không có khách hàng đã mua hàng khớp từ khóa.</p>
                                            </template>

                                            <template x-for="item in suggestions" :key="item.key">
                                                <button
                                                    type="button"
                                                    @click="selectRecipientChip(item)"
                                                    class="flex w-full items-start justify-between gap-2 rounded-lg px-3 py-2 text-left transition-colors hover:bg-[#131A29] cursor-pointer"
                                                >
                                                    <div class="min-w-0 flex-1">
                                                        <p class="truncate text-xs font-bold text-white" x-text="item.name"></p>
                                                        <p class="truncate text-[11px] text-zinc-400">
                                                            <span x-text="item.email"></span>
                                                            <span class="text-zinc-600">·</span>
                                                            <span class="tabular-nums" x-text="item.phone"></span>
                                                        </p>
                                                    </div>
                                                    <span
                                                        class="shrink-0 rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold text-emerald-300"
                                                        x-text="item.purchase_badge"
                                                    ></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Selected Chips + Hidden Form Inputs -->
                                    <div x-show="selectedRecipients.length > 0" class="flex flex-wrap gap-1.5 pt-1" style="display: none;">
                                        <template x-for="chip in selectedRecipients" :key="chip.email">
                                            <span class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-[#1C69D4]/45 bg-[#1C69D4]/15 px-2.5 py-1 text-[11px] font-semibold text-[#70A7FF]">
                                                <input type="hidden" name="selected_recipient_emails[]" :value="chip.email">
                                                <span class="truncate" x-text="chip.name + ' (' + chip.email + ')'"></span>
                                                <button
                                                    type="button"
                                                    @click="removeRecipientChip(chip.email)"
                                                    class="ml-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded text-zinc-300 hover:bg-rose-500/30 hover:text-white cursor-pointer"
                                                    aria-label="Gỡ khách hàng"
                                                >&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <x-admin.form-field name="audience" label="Nhóm đối tượng">
                                    <x-ui.custom-select
                                        id="promo-audience"
                                        name="audience"
                                        x-model="audience"
                                        @change="onAudienceChange()"
                                        :value="$initialAudience"
                                        :options="[
                                            'all' => 'Tất cả khách hàng có email',
                                            'completed' => 'Chỉ khách đã hoàn thành lịch hẹn (Completed)',
                                            'installment' => 'Khách hàng đang có hồ sơ trả góp',
                                            'by_product' => 'Theo dòng xe cụ thể',
                                        ]"
                                    />
                                </x-admin.form-field>

                                <x-admin.form-field name="product_id" label="Chọn dòng xe BMW (tự động kích hoạt lọc)">
                                    <x-ui.custom-select
                                        id="promo-product-id"
                                        name="product_id"
                                        x-model="productId"
                                        @change="onProductChange()"
                                        :value="(string) old('product_id', '')"
                                        :options="['' => 'Tất cả dòng xe BMW'] + $products->pluck('name', 'id')->all()"
                                    />
                                </x-admin.form-field>

                                <label class="flex items-start gap-2.5 rounded-xl border border-white/[0.08] bg-[#0D121C] p-3.5 text-xs text-zinc-300 cursor-pointer">
                                    <input
                                        id="promo-only-completed"
                                        type="checkbox"
                                        name="only_completed"
                                        value="1"
                                        x-model="onlyCompleted"
                                        @change="onOnlyCompletedChange()"
                                        @checked($initialOnlyCompleted)
                                        class="mt-0.5 rounded border-white/20 bg-[#070A10] text-[#1C69D4] focus:ring-[#1C69D4]"
                                    >
                                    <span class="leading-snug">
                                        <span class="block font-semibold text-zinc-200">Chỉ gửi khách đã hoàn thành lịch hẹn</span>
                                        <span class="mt-0.5 block text-[11px] text-zinc-500">Ưu tiên hiển thị tên xe khách đã hoàn thành giao dịch trong email.</span>
                                    </span>
                                </label>

                                <!-- Live Preview Box -->
                                <div
                                    data-testid="promotion-matched-counter"
                                    class="space-y-3 rounded-xl border border-[#1C69D4]/30 bg-[#0D121C] p-4"
                                >
                                    <div class="flex items-center justify-between gap-2 border-b border-white/[0.08] pb-2.5">
                                        <div class="flex items-center gap-2 text-xs font-medium text-zinc-300">
                                            <span class="h-2 w-2 shrink-0 rounded-full bg-[#1C69D4]"></span>
                                            <span>
                                                Sẽ gửi tới
                                                <strong class="font-black text-[#70A7FF] tabular-nums" x-text="matchedCount">{{ $initialMatchedCount }}</strong>
                                                khách hàng phù hợp
                                            </span>
                                        </div>
                                        <span class="whitespace-nowrap text-[9px] font-bold uppercase tracking-[0.18em] text-zinc-500">Live Preview</span>
                                    </div>

                                    <div x-show="previewRecipients.length > 0" class="space-y-2">
                                        <template x-for="recipient in previewRecipients" :key="recipient.key">
                                            <div class="flex items-center justify-between gap-3 rounded-lg border border-white/[0.06] bg-[#070A10] px-3 py-2">
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-bold text-white" x-text="recipient.name"></p>
                                                    <p class="truncate text-[11px] text-zinc-400" x-text="recipient.email"></p>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <span class="inline-block max-w-[140px] truncate rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/10 px-2.5 py-0.5 text-[10px] font-semibold text-[#70A7FF]" x-text="recipient.vehicle"></span>
                                                </div>
                                            </div>
                                        </template>

                                        <p x-show="matchedCount > 5" class="pt-1 text-right text-[11px] font-medium text-zinc-500">
                                            và <span class="font-bold text-zinc-300" x-text="matchedCount - 5"></span> khách hàng khác...
                                        </p>
                                    </div>

                                    <p x-show="previewRecipients.length === 0" class="text-xs text-zinc-500">
                                        <span x-show="audience === 'by_product' && !productId">Vui lòng chọn một dòng xe BMW để xem danh sách người nhận phù hợp.</span>
                                        <span x-show="!(audience === 'by_product' && !productId)">Hiện chưa có khách hàng nào có email khớp điều kiện lọc này.</span>
                                    </p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="matchedCount === 0"
                                class="h-11 w-full whitespace-nowrap rounded-xl border border-[#1C69D4] bg-[#1C69D4] px-6 text-[10px] font-semibold uppercase tracking-[0.22em] text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-[#1C69D4]"
                            >
                                PHÁT CHIẾN DỊCH NGAY
                            </button>
                        </div>
                    </form>
                </x-admin.card>
            </section>
        @endif
    </div>
</x-admin-layout>
