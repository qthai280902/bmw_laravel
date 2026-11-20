@php
    $salesFilterOptions = [
        '' => 'Tất cả Sales',
        'mine' => 'Đơn của tôi',
        'unassigned' => 'Chưa phân công',
    ];
    foreach ($salesRoster as $salesMember) {
        $salesFilterOptions[(string) $salesMember->id] = $salesMember->name;
    }

    $datePresetOptions = [
        '' => 'Mọi thời điểm',
        'today' => 'Hôm nay',
        '7d' => '7 ngày qua',
        '30d' => '30 ngày qua',
        'this_month' => 'Tháng này',
        'custom' => 'Tùy chọn ngày',
    ];
@endphp

<div @keydown.escape.window="$wire.closeInspector()">
    <x-admin.page-header
        eyebrow="Parts operations"
        title="Đơn phụ kiện"
        accent="BMW"
        description="Quản lý yêu cầu đặt phụ kiện chính hãng, xác nhận đơn hàng và điều phối giao nhận."
    >
        <x-slot name="metric">
            <p class="text-[11px] font-semibold text-zinc-400">Tổng đơn</p>
            <p class="mt-0.5 text-2xl font-extrabold tabular-nums text-white">{{ $orders->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Contextual Quick-Filter Strip -->
    <div class="mb-6 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <button
            type="button"
            wire:click="applyQuickFilter('pending')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'pending' && ! $onlyToday ? 'border-amber-400/70 bg-amber-500/15 shadow-lg shadow-amber-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-amber-400/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-amber-300">Chờ xử lý</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đơn mới cần xác nhận</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['pending'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('confirmed')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'confirmed' && ! $onlyToday ? 'border-[#1C69D4]/70 bg-[#1C69D4]/15 shadow-lg shadow-[#1C69D4]/15' : 'border-white/[0.08] bg-[#0D121C] hover:border-[#1C69D4]/50 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-[#70A7FF]">Đã xác nhận</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đang chuẩn bị giao hàng</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['confirmed'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('today')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $onlyToday && $status === '' ? 'border-sky-400/70 bg-sky-500/15 shadow-lg shadow-sky-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-sky-400/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-sky-300">Hôm nay</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đơn phát sinh trong ngày</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['today'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('completed')"
            class="flex items-center justify-between rounded-xl border px-4 py-4 text-left transition-all hover:-translate-y-0.5 cursor-pointer {{ $status === 'completed' && ! $onlyToday ? 'border-emerald-500/70 bg-emerald-500/15 shadow-lg shadow-emerald-950/20' : 'border-white/[0.08] bg-[#0D121C] hover:border-emerald-500/40 hover:bg-[#131A29]' }}"
        >
            <div>
                <p class="text-xs font-bold text-emerald-300">Hoàn thành</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã giao thành công</p>
            </div>
            <span class="text-2xl font-extrabold tabular-nums text-white">{{ $quickStats['completed'] ?? 0 }}</span>
        </button>
    </div>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-emerald-500/30 bg-emerald-500/15 px-5 py-3.5 text-xs font-semibold text-emerald-200">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-300 hover:bg-white/10 hover:text-white cursor-pointer">
                Đóng
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-rose-500/35 bg-rose-500/15 px-5 py-3.5 text-xs font-semibold text-rose-200">
            <span>{{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage', null)" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-rose-300 hover:bg-white/10 hover:text-white cursor-pointer">
                Đóng
            </button>
        </div>
    @endif

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.accessory-orders.index') }}" method="GET" wire:submit.prevent="$refresh" class="space-y-3.5">
            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 lg:grid-cols-[1.45fr_1fr_1fr_1fr_auto] lg:items-end">
                <x-admin.form-field name="search" label="Tìm kiếm mã kép / khách / phụ kiện">
                    <input
                        id="search"
                        type="text"
                        name="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Mã #SĐT_STT, dd/mm/yyyy/NN, #12, tên, SĐT..."
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-2 focus:ring-[#1C69D4]/40"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="status" label="Trạng thái">
                    <x-ui.custom-select
                        id="status"
                        name="status"
                        :value="$status"
                        :options="array_merge(['' => 'Tất cả trạng thái'], $statusOptions)"
                        wire-model="status"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="sales_id" label="Sales phụ trách">
                    <x-ui.custom-select
                        id="order-sales-filter"
                        name="sales_id"
                        :value="$salesId"
                        :options="$salesFilterOptions"
                        wire-model="salesId"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="date_preset" label="Khoảng ngày tạo">
                    <x-ui.custom-select
                        id="order-date-preset"
                        name="date_preset"
                        :value="$datePreset"
                        :options="$datePresetOptions"
                        wire-model="datePreset"
                    />
                </x-admin.form-field>

                <div class="flex items-center gap-2">
                    <button type="submit" class="h-10 whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer">
                        Lọc
                    </button>
                    <button type="button" wire:click="resetFilters" class="h-10 whitespace-nowrap rounded-xl border border-white/10 bg-[#131A29] px-4 text-center text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white cursor-pointer">
                        Xóa @if($activeFilterCount > 0)({{ $activeFilterCount }})@endif
                    </button>
                </div>
            </div>

            @if($datePreset === 'custom' || $dateFrom !== '' || $dateTo !== '')
                <div class="grid grid-cols-1 gap-3.5 border-t border-white/[0.06] pt-3.5 sm:grid-cols-2 lg:max-w-lg">
                    <x-admin.form-field name="dateFrom" label="Từ ngày">
                        <input id="orderDateFrom" type="date" name="date_from" wire:model.live="dateFrom" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    </x-admin.form-field>
                    <x-admin.form-field name="dateTo" label="Đến ngày">
                        <input id="orderDateTo" type="date" name="date_to" wire:model.live="dateTo" class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    </x-admin.form-field>
                </div>
            @endif
        </form>
    </x-admin.card>

    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
        <div class="overflow-x-auto lg:overflow-x-visible" wire:loading.class="opacity-60">
            <table class="w-full lg:table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/60">
                        <th class="w-[18%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Mã kép &amp; Ngày tạo</th>
                        <th class="w-[22%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Khách hàng &amp; Sales</th>
                        <th class="w-[26%] px-5 py-3.5 text-xs font-semibold text-zinc-400">Phụ kiện &amp; Giá trị</th>
                        <th class="w-[14%] px-5 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                        <th class="w-[20%] px-5 py-3.5 text-right text-xs font-semibold text-zinc-400">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($orders as $order)
                        <tr wire:key="admin-accessory-order-{{ $order->id }}" class="group align-middle transition-colors hover:bg-white/[0.03]">
                            <td class="whitespace-nowrap px-5 py-4">
                                @if($order->customer_order_code)
                                    <p class="text-xs font-bold tabular-nums text-[#70A7FF]">{{ $order->customer_order_code }}</p>
                                @endif
                                @if($order->daily_sequence_code)
                                    <p class="mt-0.5 text-[11px] font-semibold tabular-nums text-zinc-300">{{ $order->daily_sequence_code }}</p>
                                @endif
                                <p class="mt-1 text-xs font-semibold tabular-nums text-white">#{{ $order->id }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $order->customer_name }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-300">{{ $order->customer_phone }}</p>
                                <p class="mt-1.5">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ filled($order->assigned_sales_name) ? 'border-sky-500/25 bg-sky-500/10 text-sky-300' : 'border-white/10 bg-white/[0.04] text-zinc-400' }}">
                                        {{ filled($order->assigned_sales_name) ? 'Sales: ' . $order->assigned_sales_name : 'Chưa gán Sales' }}
                                    </span>
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="truncate text-xs font-semibold text-[#70A7FF]">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</p>
                                <p class="mt-1 text-xs font-bold tabular-nums text-white">
                                    {{ number_format($order->total_amount) }} VNĐ
                                    <span class="font-medium text-zinc-400">· SL: {{ $order->quantity }}</span>
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold {{ $order->statusColorClass() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if($order->isTerminalStatus())
                                        <span class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-[#070A10] px-3 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                            {{ $order->status === \App\Models\AccessoryOrder::STATUS_COMPLETED ? 'Đã đóng · Hoàn tất' : 'Đã đóng · Đã hủy' }}
                                        </span>
                                    @else
                                        <div class="w-36">
                                            <x-ui.custom-select
                                                compact
                                                aria-label="Cập nhật trạng thái đơn #{{ $order->id }}"
                                                :value="$order->status"
                                                :options="$order->allowedStatusOptions()"
                                                wire-action="updateStatus"
                                                :wire-record-id="$order->id"
                                            />
                                        </div>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="inspectOrder({{ $order->id }})"
                                        data-testid="inspect-accessory-order-{{ $order->id }}"
                                        title="Xem nhanh chi tiết đơn #{{ $order->id }}"
                                        aria-label="Xem nhanh đơn phụ kiện #{{ $order->id }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/20 hover:text-white cursor-pointer"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6">
                                <x-admin.empty-state
                                    title="Chưa có đơn phụ kiện"
                                    description="Chưa có đơn hàng nào khớp bộ lọc hiện tại."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @endif

    <!-- Accessory Order Quick Inspector Drawer (Teleported to body, Z-100/101, Independent Scroll & 2-Way Timeline) -->
    @if($this->inspectedOrder)
        @php
            $order = $this->inspectedOrder;
            $timelineEvents = \App\Services\Crm\InteractionTimelineRecorder::timelineForAccessoryOrder($order);
        @endphp
        <div
            wire:key="accessory-order-inspector-drawer-{{ $order->id }}"
            x-data="{
                init() { document.body.classList.add('overflow-hidden'); },
                destroy() { document.body.classList.remove('overflow-hidden'); },
                close() {
                    document.body.classList.remove('overflow-hidden');
                    $wire.closeInspector();
                }
            }"
            @keydown.escape.window="close()"
        >
            <template x-teleport="body">
                <div
                    class="fixed inset-0 z-[100]"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Hồ sơ Đơn Phụ Kiện"
                >
                    <div
                        class="fixed inset-0 z-[100] bg-black/75 backdrop-blur-sm"
                        @click="close()"
                    ></div>

                    <div class="fixed inset-y-0 right-0 z-[101] flex h-dvh w-full max-w-xl flex-col overflow-hidden rounded-l-2xl border-l border-white/10 bg-[#0B101B] text-white shadow-2xl shadow-black/70">
                        <header class="shrink-0 flex items-start justify-between gap-4 border-b border-white/[0.08] bg-[#0D121C] px-6 py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/15 px-3 py-0.5 text-[10px] font-bold tracking-wider text-[#70A7FF]">
                                        ĐƠN PHỤ KIỆN #ACC-{{ $order->id }}
                                    </span>
                                    @if($order->customer_order_code)
                                        <span class="rounded-full border border-sky-500/30 bg-sky-500/15 px-3 py-0.5 text-[11px] font-bold tabular-nums text-sky-300">
                                            {{ $order->customer_order_code }}
                                        </span>
                                    @endif
                                    @if($order->daily_sequence_code)
                                        <span class="rounded-full border border-white/15 bg-white/5 px-3 py-0.5 text-[11px] font-semibold tabular-nums text-zinc-200">
                                            {{ $order->daily_sequence_code }}
                                        </span>
                                    @endif
                                    <span class="rounded-full border px-3 py-0.5 text-[11px] font-semibold {{ $order->statusColorClass() }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </div>
                                <h3 class="mt-2.5 truncate text-lg font-bold text-white">{{ $order->customer_name }}</h3>
                                <p class="mt-0.5 text-xs text-zinc-400">Đặt lúc {{ $order->created_at->format('H:i — d/m/Y') }}</p>
                            </div>

                            <button
                                type="button"
                                @click="close()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] text-lg font-bold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                aria-label="Đóng hồ sơ đơn phụ kiện"
                            >
                                ×
                            </button>
                        </header>

                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 space-y-5">
                            <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                <p class="text-xs font-semibold text-[#38BDF8]">1. Thông tin giao nhận khách hàng</p>
                                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 text-xs">
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Số điện thoại</p>
                                        <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="font-bold tabular-nums text-white">{{ $order->customer_phone }}</span>
                                            <a href="tel:{{ $order->customer_phone }}" class="rounded-lg border border-emerald-500/30 bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300 hover:bg-emerald-500/25">Gọi</a>
                                            <button
                                                type="button"
                                                @click="navigator.clipboard?.writeText('{{ $order->customer_phone }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                class="rounded-lg border border-white/10 bg-[#131A29] px-2.5 py-0.5 text-[11px] font-semibold text-zinc-300 hover:border-[#1C69D4] cursor-pointer"
                                                x-text="copied ? 'Đã copy' : 'Copy'"
                                            ></button>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-medium text-zinc-400">Email nhận thông báo</p>
                                        <p class="mt-1 truncate font-semibold text-zinc-200">{{ $order->customer_email ?: 'Chưa cung cấp' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-[11px] font-medium text-zinc-400">Địa chỉ giao hàng</p>
                                        <p class="mt-1 text-zinc-200">{{ $order->customer_address }}</p>
                                    </div>
                                    @if(filled($order->notes))
                                        <div class="sm:col-span-2">
                                            <p class="text-[11px] font-medium text-zinc-400">Ghi chú của khách</p>
                                            <p class="mt-1 text-zinc-300">{{ $order->notes }}</p>
                                        </div>
                                    @endif
                                    <div class="sm:col-span-2 border-t border-white/[0.06] pt-3">
                                        <p class="text-[11px] font-medium text-zinc-400">Sales phụ trách</p>
                                        @if(auth()->user()?->canManageSystem())
                                            <select
                                                aria-label="Phân công Sales cho đơn phụ kiện #{{ $order->id }}"
                                                wire:change="assignToSales({{ $order->id }}, $event.target.value)"
                                                class="mt-1.5 h-9 w-full rounded-lg border border-white/15 bg-[#070A10] px-3 text-xs font-semibold text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                            >
                                                <option value="">-- Chưa phân công --</option>
                                                @foreach($salesRoster as $salesMember)
                                                    <option value="{{ $salesMember->id }}" @selected((int) $order->assigned_to === (int) $salesMember->id)>
                                                        {{ $salesMember->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <p class="mt-1 font-semibold text-sky-300">{{ $order->assignedSales?->name ?? 'Chưa phân công' }}</p>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3">
                                <p class="text-xs font-semibold text-[#38BDF8]">2. Sản phẩm &amp; Giá trị thanh toán</p>
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-white/[0.06] bg-[#0D121C] p-3.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-semibold text-white">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</p>
                                        <p class="mt-0.5 text-[11px] text-zinc-400">Số lượng: {{ $order->quantity }} · Danh mục: {{ $order->product?->category?->name ?? 'Phụ kiện BMW' }}</p>
                                    </div>
                                    <p class="shrink-0 text-sm font-bold tabular-nums text-[#38BDF8]">{{ number_format($order->total_amount) }} VNĐ</p>
                                </div>
                            </section>

                            <x-admin.interaction-timeline
                                :events="$timelineEvents"
                                heading="3. Lịch sử tương tác Khách hàng ↔ Hệ thống"
                            />

                            <x-admin.crm-action-hub
                                context-key="accessory-order"
                                :record-id="$order->id"
                                :status-value="$order->status"
                                :status-label="$order->statusLabel()"
                                confirm-label="Duyệt & Xác nhận"
                                confirm-hint="Xác nhận đơn & gửi email"
                                complete-label="Hoàn tất"
                                complete-hint="Đã giao phụ kiện thành công"
                                cancel-label="Hủy đơn hàng"
                                completed-message="Đơn phụ kiện đã hoàn tất giao nhận — Trạng thái đã khóa."
                                cancelled-message="Đơn phụ kiện đã bị hủy và hoàn tồn kho — Không thể duyệt lại."
                                :error-message="$errorMessage"
                            >
                                <x-slot name="extraLinks">
                                    <a
                                        href="{{ route('admin.accessory-orders.show', $order) }}"
                                        class="flex h-10 w-full items-center justify-center rounded-xl border border-white/10 bg-[#0D121C] px-4 text-xs font-semibold text-zinc-200 transition-colors hover:border-[#1C69D4] hover:text-white"
                                    >
                                        Mở trang chi tiết &amp; ghi chú nội bộ
                                    </a>
                                </x-slot>
                            </x-admin.crm-action-hub>

                            @php
                                $accessoryPreviewItems = [
                                    [
                                        'href' => route('admin.mail-preview.accessory-order', ['accessoryOrder' => $order, 'mode' => 'confirmation']),
                                        'label' => 'Thư xác nhận đặt phụ kiện',
                                        'toneClass' => 'border-[#1C69D4]/30 text-[#70A7FF] hover:border-[#1C69D4] hover:bg-[#1C69D4]/15',
                                    ],
                                    [
                                        'href' => route('admin.mail-preview.accessory-order', ['accessoryOrder' => $order, 'mode' => 'status']),
                                        'label' => 'Thư cập nhật trạng thái đơn',
                                        'toneClass' => 'border-emerald-500/30 text-emerald-300 hover:border-emerald-400 hover:bg-emerald-500/15',
                                    ],
                                ];
                            @endphp
                            <x-admin.mail-preview-accordion
                                :key-id="'accessory-order-'.$order->id"
                                :items="$accessoryPreviewItems"
                            />
                        </div>

                        <footer class="shrink-0 flex items-center justify-between gap-3 border-t border-white/[0.08] bg-[#0D121C] px-6 py-4">
                            <span class="text-xs text-zinc-500">Nhấn Esc hoặc click vùng tối để đóng</span>
                            <button
                                type="button"
                                @click="close()"
                                class="h-9 rounded-xl border border-white/10 bg-[#131A29] px-5 text-xs font-semibold text-zinc-200 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                            >
                                Đóng
                            </button>
                        </footer>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
