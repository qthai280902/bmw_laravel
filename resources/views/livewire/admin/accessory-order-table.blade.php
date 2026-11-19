<div>
    <x-admin.page-header
        eyebrow="Parts operations"
        title="Đơn phụ kiện"
        accent="BMW"
        description="Quản lý yêu cầu đặt phụ kiện chính hãng, xác nhận thủ công và ghi chú nội bộ."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng đơn</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $orders->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-xs font-bold text-emerald-300">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 hover:text-white">
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
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full min-w-[1140px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Mã đơn</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Khách hàng</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Sản phẩm</th>
                        <th class="px-5 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">SL</th>
                        <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng tiền</th>
                        <th class="min-w-[135px] px-5 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Trạng thái</th>
                        <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Ngày tạo</th>
                        <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($orders as $order)
                        <tr wire:key="admin-accessory-order-{{ $order->id }}" class="group transition-colors hover:bg-zinc-900/60">
                            <td class="whitespace-nowrap px-5 py-5 text-sm font-black uppercase tracking-widest tabular-nums text-white">#{{ $order->id }}</td>
                            <td class="px-5 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $order->customer_name }}</p>
                                <p class="mt-1 whitespace-nowrap text-xs font-medium tabular-nums text-zinc-400">{{ $order->customer_phone }}</p>
                            </td>
                            <td class="px-5 py-5">
                                <p class="max-w-xs truncate text-xs font-black uppercase tracking-wider text-[#70A7FF]">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-zinc-500">{{ $order->product?->category?->name ?? 'N/A' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-center text-sm font-black tabular-nums text-zinc-300">{{ $order->quantity }}</td>
                            <td class="whitespace-nowrap px-5 py-5 text-right text-sm font-black tabular-nums text-white">{{ number_format($order->total_amount) }} VNĐ</td>
                            <td class="min-w-[135px] whitespace-nowrap px-5 py-5 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-none border px-3 py-1 text-[9px] font-black uppercase tracking-[0.16em] {{ $order->statusColorClass() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-right text-xs font-bold uppercase tracking-wider tabular-nums text-zinc-400">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-5 py-5 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <div class="w-40">
                                        <x-ui.custom-select
                                            compact
                                            aria-label="Cập nhật trạng thái đơn #{{ $order->id }}"
                                            :value="$order->status"
                                            :options="$statusOptions"
                                            wire-action="updateStatus"
                                            :wire-record-id="$order->id"
                                        />
                                    </div>
                                    <a href="{{ route('admin.accessory-orders.show', $order) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 transition-colors hover:text-white">
                                        Xem
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Chưa có đơn phụ kiện"
                                    description="Đơn mới từ form public sẽ xuất hiện tại đây để admin xác nhận và ghi chú nội bộ."
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
</div>
