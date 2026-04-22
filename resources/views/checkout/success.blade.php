<x-app-layout>
    <div class="py-20 bg-zinc-950 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Success Icon --}}
            <div class="text-center mb-16">
                <div class="inline-flex items-center justify-center w-24 h-24 border-2 border-accent mb-8">
                    <svg class="w-12 h-12 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-5xl font-black text-white uppercase tracking-tighter italic mb-4">
                    Đặt cọc <span class="text-accent">Thành công</span>
                </h1>
                <p class="text-zinc-500 uppercase text-xs tracking-widest font-bold">
                    Mã đơn hàng: <span class="text-white">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                </p>
            </div>

            {{-- Order Details --}}
            <div class="bg-zinc-900 border border-zinc-800 p-10 mb-8">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-accent mb-8 border-b border-zinc-800 pb-4">
                    Chi tiết đơn hàng
                </h3>

                {{-- Order Items --}}
                <div class="space-y-6 mb-8">
                    @foreach($order->items as $item)
                        <div class="flex justify-between items-center border-b border-zinc-800/50 pb-4">
                            <div>
                                <p class="font-black text-white uppercase tracking-tight">{{ $item->product_name }}</p>
                                <p class="text-[10px] text-zinc-500 uppercase tracking-widest mt-1">Số lượng: {{ $item->quantity }}</p>
                            </div>
                            <p class="font-black text-white tracking-tighter">
                                {{ number_format($item->deposit_amount_snapshot * $item->quantity) }}
                                <span class="text-xs text-zinc-500">VNĐ</span>
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Total & Expiry --}}
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Tổng tiền đặt cọc</p>
                        <p class="text-3xl font-black text-white tracking-tighter">
                            {{ number_format($order->total_deposit_amount) }} <span class="text-xs text-zinc-500">VNĐ</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Hạn chuyển khoản</p>
                        <p class="text-lg font-black text-yellow-400 tracking-tight">
                            {{ $order->expires_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-[10px] text-zinc-500 uppercase tracking-widest mt-1">
                            Còn {{ now()->diffForHumans($order->expires_at, true) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Bank Info Reminder --}}
            <div class="bg-zinc-900 border border-accent/20 p-8 mb-8">
                <p class="text-[10px] font-black uppercase tracking-widest text-accent mb-6">Chuyển khoản ngay</p>
                <div class="grid grid-cols-2 gap-4">
                    @foreach([
                        'Ngân hàng' => 'Techcombank',
                        'Số tài khoản' => '19001234567',
                        'Số tiền' => number_format($order->total_deposit_amount) . ' VNĐ',
                        'Nội dung' => 'DAT COC #' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    ] as $label => $value)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ $label }}</p>
                            <p class="text-sm font-black text-white uppercase mt-1">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-4">
                <a href="{{ route('products.index') }}"
                   class="flex-1 bg-zinc-900 text-white text-center text-[10px] font-black uppercase tracking-widest py-5 hover:bg-zinc-800 transition-all border border-zinc-800">
                    Tiếp tục xem xe
                </a>
                <a href="{{ route('home') }}"
                   class="flex-1 bg-white text-black text-center text-[10px] font-black uppercase tracking-widest py-5 hover:bg-accent hover:text-white transition-all">
                    Về trang chủ
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
