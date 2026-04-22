<x-admin-layout>
    <div class="flex justify-between items-end mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2">Order <span class="font-black text-[#1C69D4]">Management</span></h1>
            <p class="text-zinc-500 font-medium">Quản lý và xác nhận các đơn đặt cọc từ khách hàng.</p>
        </div>
    </div>

    <!-- Filters -->
    <x-admin.card class="mb-12">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap gap-6 items-end">
            <div class="w-64">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Trạng thái đơn hàng</label>
                <select name="status" class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(\App\Enums\OrderStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-6 py-2 bg-zinc-800 text-white font-black uppercase text-xs tracking-widest hover:bg-zinc-700 transition-colors">
                Lọc kết quả
            </button>
            <a href="{{ route('admin.orders.index') }}" class="px-6 py-2 border border-zinc-800 text-zinc-500 font-black uppercase text-xs tracking-widest hover:text-white transition-colors">
                Xóa lọc
            </a>
        </form>
    </x-admin.card>

    <!-- Table -->
    <x-admin.card class="p-0">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Mã đơn</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Khách hàng</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-right">Tổng tiền cọc</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-center">Trạng thái</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest">Ngày tạo</th>
                    <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-900">
                @forelse($orders as $order)
                    <tr class="group hover:bg-zinc-950/50 transition-colors">
                        <td class="px-8 py-6">
                            <span class="text-sm font-black text-zinc-400">#{{ $order->id }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <div class="text-sm font-black uppercase tracking-wider group-hover:text-[#1C69D4] transition-colors">{{ $order->user->name }}</div>
                            <div class="text-[10px] text-zinc-600 font-bold">{{ $order->user->email }}</div>
                        </td>
                        <td class="px-8 py-6 text-right font-black text-sm tabular-nums text-white">
                            {{ number_format($order->total_deposit_amount) }} <span class="text-[10px] text-zinc-600 uppercase">VNĐ</span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            @php
                                $color = match($order->status) {
                                    \App\Enums\OrderStatus::PendingPayment => 'text-yellow-500 border-yellow-500/20 bg-yellow-500/5',
                                    \App\Enums\OrderStatus::Paid => 'text-emerald-500 border-emerald-500/20 bg-emerald-500/5',
                                    \App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Expired => 'text-rose-500 border-rose-500/20 bg-rose-500/5',
                                    default => 'text-zinc-500 border-zinc-800 bg-zinc-900',
                                };
                            @endphp
                            <span class="px-3 py-1 border {{ $color }} text-[9px] font-black uppercase tracking-widest">
                                {{ $order->status->label() }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-xs text-zinc-500 font-medium">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-8 py-6 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-[10px] font-black uppercase tracking-widest px-4 py-2 bg-zinc-900 hover:bg-[#1C69D4] hover:text-white transition-all"> Chi tiết </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-8 py-24 text-center">
                            <div class="text-zinc-600 font-black uppercase text-xs tracking-widest italic">Chưa có đơn hàng nào cần xử lý.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.card>

    <div class="mt-8">
        {{ $orders->links() }}
    </div>
</x-admin-layout>
