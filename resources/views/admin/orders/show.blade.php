<x-admin-layout>
    <div class="mb-12">
        <a href="{{ route('admin.orders.index') }}" class="text-[10px] font-black uppercase text-zinc-500 hover:text-[#1C69D4] transition-colors mb-4 inline-block">
            &larr; Quay lại danh sách
        </a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-6xl font-light uppercase tracking-tighter mb-2">Order <span class="font-black text-[#1C69D4]">#{{ $order->id }}</span></h1>
                <p class="text-zinc-500 font-medium italic">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            @php
                $color = match($order->status) {
                    \App\Enums\OrderStatus::PendingPayment => 'text-yellow-500 border-yellow-500',
                    \App\Enums\OrderStatus::Paid => 'text-emerald-500 border-emerald-500',
                    \App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Expired => 'text-rose-500 border-rose-500',
                    default => 'text-zinc-500 border-zinc-800',
                };
            @endphp
            <div class="px-6 py-3 border-2 {{ $color }} text-sm font-black uppercase tracking-[0.2em]">
                {{ $order->status->label() }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-12">
            <!-- Order Items -->
            <x-admin.card class="p-0 overflow-hidden">
                <div class="px-8 py-6 border-b border-zinc-900 bg-zinc-950/50">
                    <h2 class="text-xs font-black uppercase tracking-widest text-[#1C69D4]">Chi tiết xe đặt cọc</h2>
                </div>
                <div class="divide-y divide-zinc-900">
                    @foreach($order->items as $item)
                        <div class="px-8 py-8 flex gap-8 items-center group">
                            <div class="flex-1">
                                <div class="text-xl font-black uppercase tracking-tight group-hover:text-[#1C69D4] transition-colors">
                                    {{ $item->product_name }}
                                </div>
                                <div class="text-[10px] text-zinc-600 font-bold uppercase mt-1 tracking-widest">Snapshot pricing</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black tabular-nums">{{ number_format($item->deposit_amount_snapshot) }} <small class="text-zinc-600 uppercase text-[10px]">VNĐ</small></div>
                                <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest">Số lượng: {{ $item->quantity }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="px-8 py-8 bg-zinc-950/80 border-t border-zinc-900 flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-zinc-500">Tổng tiền cọc đã thu</span>
                    <span class="text-3xl font-black tabular-nums text-[#1C69D4]">{{ number_format($order->total_deposit_amount) }} <small class="text-xs uppercase font-light text-zinc-500 italic">vnđ</small></span>
                </div>
            </x-admin.card>

            <!-- Metadata / Timeline -->
            <div class="grid grid-cols-2 gap-6">
                <x-admin.card>
                    <h3 class="text-[10px] font-black uppercase text-zinc-600 mb-4 tracking-widest">Thời hạn thanh toán</h3>
                    @if($order->status === \App\Enums\OrderStatus::PendingPayment)
                        <div class="text-rose-500 font-black text-sm uppercase">Hết hạn vào: {{ $order->expires_at->format('d/m/Y H:i') }}</div>
                        <div class="text-[10px] text-zinc-500 mt-2 italic font-medium">Đơn hàng sẽ tự động hủy nếu quá thời gian trên.</div>
                    @else
                        <div class="text-zinc-400 font-black text-sm uppercase italic">SESSION EXPIRED / ORDER PROCESSED</div>
                    @endif
                </x-admin.card>
                <x-admin.card>
                    <h3 class="text-[10px] font-black uppercase text-zinc-600 mb-4 tracking-widest">Ghi chú hệ thống</h3>
                    <div class="text-zinc-400 text-xs leading-relaxed italic">
                        {{ $order->notes ?? 'Không có ghi chú nào được thêm vào đơn hàng này.' }}
                    </div>
                </x-admin.card>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="space-y-6">
            <!-- Customer Card -->
            <x-admin.card class="bg-[#1C69D4]/5 border-[#1C69D4]/20">
                <h3 class="text-[10px] font-black uppercase text-[#1C69D4] mb-6 tracking-widest">Thông tin khách hàng</h3>
                <div class="space-y-4">
                    <div>
                        <div class="text-[10px] font-black uppercase text-zinc-600 mb-1">Tên đầy đủ</div>
                        <div class="text-sm font-black uppercase tracking-wider text-white">{{ $order->user->name }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase text-zinc-600 mb-1">Email liên hệ</div>
                        <div class="text-sm font-bold text-zinc-300 underline underline-offset-4 decoration-[#1C69D4]">{{ $order->user->email }}</div>
                    </div>
                </div>
            </x-admin.card>

            <!-- Payment Action -->
            @if($order->status === \App\Enums\OrderStatus::PendingPayment)
                <x-admin.card class="border-yellow-500/30">
                    <h3 class="text-[10px] font-black uppercase text-yellow-500 mb-4 tracking-widest">Hành động khả dụng</h3>
                    <p class="text-[10px] text-zinc-500 leading-relaxed mb-6 italic">Nhấn xác nhận sau khi bạn đã kiểm tra và nhận được tiền chuyển khoản từ khách hàng.</p>
                    
                    <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                onclick="return confirm('Bạn xác nhận đã nhận được tiền đặt cọc cho đơn hàng này?')"
                                class="w-full py-4 bg-[#1C69D4] text-white font-black uppercase text-xs tracking-[0.2em] shadow-[0_10px_30px_rgba(28,105,212,0.3)] hover:bg-blue-600 transition-all hover:-translate-y-1 block text-center">
                            Xác nhận đã nhận tiền
                        </button>
                    </form>
                </x-admin.card>
            @endif

            <x-admin.card class="bg-zinc-950 border-zinc-900">
                <div class="text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Trợ giúp</div>
                <p class="text-[10px] text-zinc-500 leading-relaxed italic">Dữ liệu xe trong đơn hàng là Snapshot tại thời điểm khách mua. Việc thay đổi giá xe trong Showroom sẽ không làm ảnh hưởng đến đơn hàng này.</p>
            </x-admin.card>
        </div>
    </div>
</x-admin-layout>
