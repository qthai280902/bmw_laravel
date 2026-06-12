<x-admin-layout>
    <div class="mb-12 flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
        <div>
            <h1 class="mb-2 text-6xl font-light uppercase tracking-tighter text-white">Đơn <span class="font-black text-[#1C69D4]">phụ kiện</span></h1>
            <p class="font-medium text-zinc-500">Quản lý yêu cầu đặt hàng phụ kiện chính hãng, xác nhận thủ công và ghi chú nội bộ.</p>
        </div>
        <div class="border border-zinc-800 bg-zinc-900 px-6 py-3">
            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500">Tổng đơn</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $orders->total() }}</p>
        </div>
    </div>

    <x-admin.card class="mb-8 !p-6">
        <form action="{{ route('admin.accessory-orders.index') }}" method="GET" class="flex flex-wrap items-end gap-6">
            <div class="w-72">
                <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Trạng thái</label>
                <select name="status" onchange="this.form.submit()" class="w-full border-zinc-800 bg-zinc-950 px-4 py-3 text-xs font-black uppercase tracking-widest text-zinc-400 focus:border-zinc-500 focus:ring-0">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.accessory-orders.index') }}" class="border border-zinc-800 px-6 py-3 text-center text-[10px] font-black uppercase tracking-widest text-zinc-500 transition-all hover:bg-zinc-900 hover:text-white">
                Xóa lọc
            </a>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden border-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Mã đơn</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Khách hàng</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Sản phẩm</th>
                        <th class="px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">SL</th>
                        <th class="px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Trạng thái</th>
                        <th class="px-8 py-5 text-right text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Ngày tạo</th>
                        <th class="px-8 py-5 text-right text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($orders as $order)
                        <tr class="group transition-all hover:bg-zinc-800/30">
                            <td class="px-8 py-7 text-sm font-black uppercase tracking-widest text-white">#{{ $order->id }}</td>
                            <td class="px-8 py-7">
                                <div class="text-sm font-black uppercase tracking-wider text-white">{{ $order->customer_name }}</div>
                                <div class="mt-1 text-[10px] font-bold tracking-widest text-zinc-500">{{ $order->customer_phone }}</div>
                            </td>
                            <td class="px-8 py-7">
                                <div class="text-xs font-black uppercase tracking-wider text-[#1C69D4]">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</div>
                                <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-zinc-600">{{ $order->product?->category?->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-8 py-7 text-center text-sm font-black text-zinc-300">{{ $order->quantity }}</td>
                            <td class="px-8 py-7 text-center">
                                <span class="border px-4 py-1.5 text-[9px] font-black uppercase tracking-[0.2em] {{ $order->statusColorClass() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-8 py-7 text-right text-xs font-bold uppercase tracking-widest text-zinc-500">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-8 py-7 text-right">
                                <a href="{{ route('admin.accessory-orders.show', $order) }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 transition-colors hover:text-white">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-32 text-center">
                                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500">Chưa có đơn phụ kiện</h3>
                                <p class="mt-2 text-[10px] font-bold uppercase tracking-tight text-zinc-700">Đơn mới từ form public sẽ xuất hiện tại đây.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($orders->hasPages())
        <div class="mt-12">
            {{ $orders->links() }}
        </div>
    @endif
</x-admin-layout>
