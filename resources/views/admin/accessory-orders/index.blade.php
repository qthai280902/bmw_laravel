<x-admin-layout>
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

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.accessory-orders.index') }}" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,360px)_auto] md:items-end">
            <x-admin.form-field name="status" label="Trạng thái">
                <select id="status" name="status" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="border border-zinc-700 bg-zinc-900 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]">
                    Lọc
                </button>
                <a href="{{ route('admin.accessory-orders.index') }}" class="border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                    Xóa
                </a>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Mã đơn</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Khách hàng</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Sản phẩm</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">SL</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Trạng thái</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Ngày tạo</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($orders as $order)
                        <tr class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5 text-sm font-black uppercase tracking-widest text-white">#{{ $order->id }}</td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $order->customer_name }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-500">{{ $order->customer_phone }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-xs truncate text-xs font-black uppercase tracking-wider text-[#70A7FF]">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-zinc-700">{{ $order->product?->category?->name ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-5 text-center text-sm font-black text-zinc-300">{{ $order->quantity }}</td>
                            <td class="px-6 py-5 text-center">
                                <span class="border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] {{ $order->statusColorClass() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right text-xs font-bold uppercase tracking-widest text-zinc-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-5 text-right">
                                <a href="{{ route('admin.accessory-orders.show', $order) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:text-white">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10">
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
</x-admin-layout>
