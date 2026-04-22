<x-app-layout>
    <x-slot name="header">
        <h2 class="text-5xl font-black uppercase tracking-tighter text-white">
            My <span class="text-[#1C69D4]">Garage</span>
        </h2>
        <p class="text-zinc-500 mt-2 font-medium uppercase text-xs tracking-widest italic">Lịch sử đơn đặt cọc và quản lý lịch hẹn dịch vụ.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl transition-all hover:border-[#1C69D4]/50 group">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest leading-none">Tổng đơn hàng</div>
                    <div class="text-4xl font-black text-white px-0">{{ $orders->total() }}</div>
                </div>
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl transition-all hover:border-emerald-500/50 group">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest leading-none">Đã thanh toán</div>
                    <div class="text-4xl font-black text-white px-0">{{ $orders->where('status', \App\Enums\OrderStatus::Paid)->count() }}</div>
                </div>
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl transition-all hover:border-[#1C69D4]/50 group relative overflow-hidden">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest leading-none">Lịch hẹn</div>
                    <div class="text-4xl font-black text-white px-0">{{ Auth::user()->appointments()->count() }}</div>
                    <a href="{{ route('appointments.index') }}" class="absolute inset-0 z-10"></a>
                </div>
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl flex flex-col justify-center items-center group">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-4 tracking-widest leading-none">Phân hạng VIP</div>
                    <div class="px-4 py-2 border border-[#1C69D4] text-[#1C69D4] text-xs font-black uppercase tracking-[0.2em]">
                        {{ Auth::user()->vip_tier }} MEMBER
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-zinc-900/10 border border-zinc-800 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                <div class="px-8 py-6 border-b border-zinc-800 bg-zinc-900/50 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-white">Lịch sử đặt cọc</h3>
                    <a href="{{ route('appointments.index') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-[#1C69D4] transition-colors underline underline-offset-8">Xem lịch hẹn &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-800 bg-zinc-950/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Mã đơn</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Sản phẩm</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic text-right">Tổng tiền cọc</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic text-center">Trạng thái</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-900">
                            @forelse($orders as $order)
                                <tr class="group hover:bg-zinc-900/30 transition-all duration-300">
                                    <td class="px-8 py-8 font-black text-xs text-zinc-400">#{{ $order->id }}</td>
                                    <td class="px-8 py-8">
                                        @foreach($order->items as $item)
                                            <div class="text-sm font-black uppercase tracking-wider text-zinc-200 group-hover:text-[#1C69D4] transition-colors">
                                                {{ $item->product_name }}
                                            </div>
                                            @if(!$loop->last) <div class="h-2"></div> @endif
                                        @endforeach
                                    </td>
                                    <td class="px-8 py-8 text-right font-black text-sm tabular-nums text-white">
                                        {{ number_format($order->total_deposit_amount) }} <span class="text-[10px] text-zinc-600">VNĐ</span>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        @php
                                            $color = match($order->status) {
                                                \App\Enums\OrderStatus::PendingPayment => 'text-yellow-500 border-yellow-500/20 bg-yellow-500/5',
                                                \App\Enums\OrderStatus::Paid => 'text-emerald-500 border-emerald-500/20 bg-emerald-500/5',
                                                default => 'text-rose-500 border-rose-500/20 bg-rose-500/5',
                                            };
                                        @endphp
                                        <span class="px-4 py-1.5 border {{ $color }} text-[9px] font-black uppercase tracking-widest">
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-right">
                                        <a href="{{ route('orders.show', $order) }}" 
                                           class="inline-block px-6 py-2 bg-zinc-800 text-white font-black uppercase text-[10px] tracking-widest hover:bg-[#1C69D4] transition-all hover:-translate-x-2">
                                            XEM CHI TIẾT
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-32 text-center">
                                        <div class="text-zinc-600 font-black uppercase text-sm tracking-[0.2em] italic mb-4">Bạn chưa thực hiện bất kỳ giao dịch nào.</div>
                                        <a href="{{ route('products.index') }}" class="text-[#1C69D4] font-black uppercase text-xs tracking-widest hover:underline decoration-2 underline-offset-8">Bắt đầu khám phá Showroom &rarr;</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="px-8 py-6 border-t border-zinc-800 bg-zinc-950/50">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
