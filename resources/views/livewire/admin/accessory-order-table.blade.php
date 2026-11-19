<div @keydown.escape.window="$wire.closeInspector()">
    <x-admin.page-header
        eyebrow="Parts operations"
        title="Đơn phụ kiện"
        accent="BMW"
        description="Quản lý yêu cầu đặt phụ kiện chính hãng, xác nhận đơn hàng và điều phối giao nhận."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng đơn</p>
            <p class="mt-1 text-2xl font-black tabular-nums text-white">{{ $orders->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Contextual Quick-Filter Strip -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <button
            type="button"
            wire:click="applyQuickFilter('pending')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'pending' && ! $onlyToday ? 'border-amber-400 bg-amber-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-amber-400/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">Chờ xử lý</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đơn mới cần xác nhận</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['pending'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('confirmed')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'confirmed' && ! $onlyToday ? 'border-[#1C69D4] bg-[#1C69D4]/15' : 'border-[#242B38] bg-[#0B0F17] hover:border-[#1C69D4]' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">Đã xác nhận</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đang chuẩn bị giao hàng</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['confirmed'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('today')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $onlyToday && $status === '' ? 'border-cyan-400 bg-cyan-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-cyan-400/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-400">Hôm nay</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đơn phát sinh trong ngày</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['today'] ?? 0 }}</span>
        </button>

        <button
            type="button"
            wire:click="applyQuickFilter('completed')"
            class="flex items-center justify-between rounded-none border px-4 py-3.5 text-left transition-colors cursor-pointer {{ $status === 'completed' && ! $onlyToday ? 'border-emerald-500 bg-emerald-500/10' : 'border-[#242B38] bg-[#0B0F17] hover:border-emerald-500/60' }}"
        >
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400">Hoàn thành</p>
                <p class="mt-1 text-[11px] text-zinc-400">Đã giao thành công</p>
            </div>
            <span class="text-2xl font-black tabular-nums text-white">{{ $quickStats['completed'] ?? 0 }}</span>
        </button>
    </div>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-xs font-bold text-emerald-300">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 hover:text-white cursor-pointer">
                Đóng
            </button>
        </div>
    @endif

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.accessory-orders.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-3 md:grid-cols-[1.35fr_minmax(0,300px)_auto] md:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm mã đơn / khách hàng / phụ kiện">
                <input
                    id="search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="#12, tên khách, SĐT hoặc phụ kiện..."
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
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

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 whitespace-nowrap rounded-none border border-zinc-700 bg-zinc-900 px-4 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer">
                    Lọc
                </button>
                <button type="button" wire:click="resetFilters" class="h-10 whitespace-nowrap rounded-none border border-zinc-800 px-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 transition-colors hover:border-zinc-600 hover:text-white cursor-pointer">
                    Xóa
                </button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto lg:overflow-x-visible" wire:loading.class="opacity-60">
            <table class="w-full lg:table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-[#242B38] bg-[#0B0F17]/90">
                        <th class="w-[15%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Mã &amp; Ngày tạo</th>
                        <th class="w-[22%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Khách hàng</th>
                        <th class="w-[29%] px-4 py-3.5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Phụ kiện &amp; Giá trị</th>
                        <th class="w-[14%] px-4 py-3.5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Trạng thái</th>
                        <th class="w-[20%] px-4 py-3.5 text-right text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($orders as $order)
                        <tr wire:key="admin-accessory-order-{{ $order->id }}" class="group align-middle transition-colors hover:bg-[#0B0F17]/70">
                            <td class="whitespace-nowrap px-4 py-4">
                                <p class="text-sm font-black uppercase tracking-wider tabular-nums text-white">#{{ $order->id }}</p>
                                <p class="mt-0.5 text-[11px] font-medium tabular-nums text-zinc-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="truncate text-sm font-bold text-white">{{ $order->customer_name }}</p>
                                <p class="mt-0.5 truncate text-xs tabular-nums text-zinc-400">{{ $order->customer_phone }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="truncate text-xs font-bold uppercase tracking-wider text-[#70A7FF]">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</p>
                                <p class="mt-1 text-xs font-black tabular-nums text-white">
                                    {{ number_format($order->total_amount) }} VNĐ
                                    <span class="font-medium text-zinc-500">· SL: {{ $order->quantity }}</span>
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-none border px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.16em] {{ $order->statusColorClass() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <div class="w-32">
                                        <x-ui.custom-select
                                            compact
                                            aria-label="Cập nhật trạng thái đơn #{{ $order->id }}"
                                            :value="$order->status"
                                            :options="$statusOptions"
                                            wire-action="updateStatus"
                                            :wire-record-id="$order->id"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="inspectOrder({{ $order->id }})"
                                        data-testid="inspect-accessory-order-{{ $order->id }}"
                                        title="Xem nhanh chi tiết đơn #{{ $order->id }}"
                                        aria-label="Xem nhanh đơn phụ kiện #{{ $order->id }}"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-none border border-[#242B38] bg-[#0B0F17] text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4]/15 hover:text-[#70A7FF] cursor-pointer"
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
                                    description="Đơn đặt phụ kiện chính hãng từ khách hàng sẽ xuất hiện tại đây để đội ngũ Showroom xác nhận và giao hàng."
                                    hint="Mẹo: Khi có đơn mới, nhấn biểu tượng Con Mắt ở cột Thao tác để xem địa chỉ giao hàng và chuyển trạng thái nhanh."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

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
                        class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm"
                        @click="close()"
                    ></div>

                    <div class="fixed inset-y-0 right-0 z-[101] flex h-dvh w-full max-w-xl flex-col rounded-none border-l border-[#1C69D4] bg-[#0B0F17] text-white shadow-2xl">
                        <header class="shrink-0 flex items-start justify-between gap-4 border-b border-[#242B38] bg-[#050505] px-6 py-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">
                                        ĐƠN PHỤ KIỆN #ACC-{{ $order->id }}
                                    </span>
                                    <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] {{ $order->statusColorClass() }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </div>
                                <h3 class="mt-2 truncate text-lg font-black uppercase tracking-wider text-white">{{ $order->customer_name }}</h3>
                                <p class="mt-0.5 text-xs text-zinc-400">Đặt lúc {{ $order->created_at->format('H:i — d/m/Y') }}</p>
                            </div>

                            <button
                                type="button"
                                @click="close()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none border border-[#242B38] bg-[#0B0F17] text-lg font-black text-zinc-300 transition-colors hover:border-white hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                aria-label="Đóng hồ sơ đơn phụ kiện"
                            >
                                ×
                            </button>
                        </header>

                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6 space-y-6">
                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">1. Thông tin giao nhận khách hàng</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Số điện thoại</p>
                                        <div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="font-bold tabular-nums text-white">{{ $order->customer_phone }}</span>
                                            <a href="tel:{{ $order->customer_phone }}" class="border border-[#242B38] bg-[#0B0F17] px-2 py-0.5 text-[9px] font-bold uppercase text-emerald-400 hover:border-emerald-500">Gọi</a>
                                            <button
                                                type="button"
                                                @click="navigator.clipboard?.writeText('{{ $order->customer_phone }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                class="border border-[#242B38] bg-[#0B0F17] px-2 py-0.5 text-[9px] font-bold uppercase text-zinc-300 hover:border-[#1C69D4] cursor-pointer"
                                                x-text="copied ? 'Đã copy' : 'Copy'"
                                            ></button>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Email nhận thông báo</p>
                                        <p class="mt-1 truncate font-semibold text-zinc-200">{{ $order->customer_email ?: 'Chưa cung cấp' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Địa chỉ giao hàng</p>
                                        <p class="mt-1 text-zinc-200">{{ $order->customer_address }}</p>
                                    </div>
                                    @if(filled($order->notes))
                                        <div class="sm:col-span-2">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Ghi chú của khách</p>
                                            <p class="mt-1 text-zinc-300">{{ $order->notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">2. Sản phẩm &amp; Giá trị thanh toán</p>
                                <div class="flex items-center justify-between gap-4 border border-white/5 bg-[#0B0F17] p-3.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold uppercase text-white">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</p>
                                        <p class="mt-0.5 text-[11px] text-zinc-400">Số lượng: {{ $order->quantity }} · Danh mục: {{ $order->product?->category?->name ?? 'Phụ kiện BMW' }}</p>
                                    </div>
                                    <p class="shrink-0 text-sm font-black tabular-nums text-[#70A7FF]">{{ number_format($order->total_amount) }} VNĐ</p>
                                </div>
                            </section>

                            <x-admin.interaction-timeline
                                :events="$timelineEvents"
                                heading="3. Lịch sử tương tác Khách hàng ↔ Hệ thống"
                            />

                            <section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">4. Trung tâm Thao tác nhanh (Action Hub)</p>
                                <a
                                    href="{{ route('admin.accessory-orders.show', $order) }}"
                                    class="flex w-full items-center justify-center rounded-none border border-[#1C69D4] bg-[#1C69D4]/15 px-4 py-3 text-[10px] font-black uppercase tracking-[0.18em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white"
                                >
                                    Mở trang chi tiết &amp; ghi chú nội bộ
                                </a>
                            </section>
                        </div>

                        <footer class="shrink-0 flex items-center justify-between gap-3 border-t border-[#242B38] bg-[#050505] px-6 py-4">
                            <span class="text-[10px] font-semibold text-zinc-500">Nhấn Esc hoặc click vùng tối để đóng</span>
                            <button
                                type="button"
                                @click="close()"
                                class="rounded-none border border-[#242B38] bg-[#0B0F17] px-5 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-200 transition-colors hover:border-white hover:bg-[#1C69D4] hover:text-white cursor-pointer"
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
