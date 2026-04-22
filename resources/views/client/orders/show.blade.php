<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <a href="{{ route('dashboard') }}" class="text-[10px] font-black uppercase text-zinc-600 hover:text-[#1C69D4] transition-colors mb-4 inline-block tracking-widest leading-none">&larr; Quay lại Garage</a>
                <h2 class="text-5xl font-black uppercase tracking-tighter text-white leading-tight">
                    Order <span class="text-[#1C69D4]">#{{ $order->id }}</span>
                </h2>
                <p class="text-zinc-500 font-medium uppercase text-xs tracking-[0.2em] italic">Đặt cọc vào lúc {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            <div class="px-8 py-3 border-2 {{ $order->status === \App\Enums\OrderStatus::Paid ? 'border-emerald-500 text-emerald-500' : ($order->status === \App\Enums\OrderStatus::PendingPayment ? 'border-yellow-500 text-yellow-500' : 'border-rose-500 text-rose-500') }} text-sm font-black uppercase tracking-[0.3em] bg-black shadow-[0_0_50px_rgba(0,0,0,0.8)]">
                {{ $order->status->label() }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                
                <!-- Main Order Details -->
                <div class="lg:col-span-2 space-y-12">
                    <div class="bg-zinc-900/10 border border-zinc-800 overflow-hidden shadow-2xl">
                        <div class="px-8 py-6 border-b border-zinc-800 bg-zinc-900/30">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-[#1C69D4]">Danh sách phương tiện đã chọn</h3>
                        </div>

                        <div class="divide-y divide-zinc-900">
                            @foreach($order->items as $item)
                                <div class="px-8 py-10 flex flex-col md:flex-row gap-10 items-center group">
                                    <!-- Snapshot Product UI -->
                                    <div class="w-full md:w-48 aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden relative flex-shrink-0">
                                        @if($item->product && $item->product->images->where('is_primary', true)->first())
                                            @php $orderImgModel = $item->product->images->where('is_primary', true)->first(); @endphp
                                            @if(Str::startsWith($orderImgModel->path, 'http'))
                                                <img src="{{ $orderImgModel->path }}" class="w-full h-full object-cover grayscale brightness-75 group-hover:grayscale-0 group-hover:brightness-100 transition-all duration-700">
                                            @else
                                                <img src="{{ Storage::url($orderImgModel->path) }}" class="w-full h-full object-cover grayscale brightness-75 group-hover:grayscale-0 group-hover:brightness-100 transition-all duration-700">
                                            @endif
                                        @else
                                            <div class="w-full h-full flex flex-col items-center justify-center bg-zinc-950 p-4">
                                                <svg class="w-8 h-8 text-zinc-800 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                <span class="text-[8px] font-black uppercase text-zinc-700 tracking-[0.2em] text-center italic">Image Unavailable (Snapshot context)</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <h4 class="text-2xl font-black uppercase tracking-tighter text-white group-hover:text-[#1C69D4] transition-colors mb-2">
                                            {{ $item->product_name }}
                                        </h4>
                                        <div class="inline-block px-3 py-1 bg-zinc-900 border border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-500 italic">
                                            Fixed Configuration @ {{ number_format($item->deposit_amount_snapshot) }} VNĐ
                                        </div>
                                    </div>

                                    <div class="text-right flex-shrink-0">
                                        <div class="text-2xl font-black tabular-nums text-white group-hover:tracking-widest transition-all duration-500">
                                            {{ number_format($item->deposit_amount_snapshot * $item->quantity) }} 
                                            <span class="text-xs font-medium text-zinc-600 ml-2">VNĐ</span>
                                        </div>
                                        <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1">SỐ LƯỢNG: {{ $item->quantity }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Summary -->
                        <div class="px-8 py-10 bg-zinc-950 border-t border-zinc-900 flex flex-col md:flex-row justify-between items-center gap-6 shadow-[inset_0_10px_30px_rgba(0,0,0,0.5)]">
                            <div>
                                <h3 class="text-[10px] font-black uppercase tracking-[0.4em] text-zinc-600 mb-1 leading-none">Tổng cộng đặt cọc</h3>
                                <p class="text-[10px] text-zinc-800 font-medium uppercase italic">Đã bao gồm phí snapshot dịch vụ</p>
                            </div>
                            <div class="text-5xl font-black tabular-nums text-[#1C69D4] leading-none">
                                {{ number_format($order->total_deposit_amount) }} <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600">vnđ</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-zinc-900/10 border border-zinc-800 p-8 shadow-xl">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#1C69D4] mb-4">Ghi chú đơn hàng</h3>
                            <p class="text-xs text-zinc-500 italic leading-relaxed">
                                {{ $order->notes ?? 'Bạn không để lại ghi chú nào khi đặt cọc.' }}
                            </p>
                        </div>
                        <div class="bg-zinc-900/10 border border-zinc-800 p-8 shadow-xl">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#1C69D4] mb-4">Thời gian hiệu lực</h3>
                            @if($order->status === \App\Enums\OrderStatus::PendingPayment)
                                <p class="text-xs text-rose-500 font-black uppercase leading-relaxed tracking-wider">
                                    Đang chờ thanh toán trước: {{ $order->expires_at->format('d/m/Y H:i') }}
                                </p>
                                <p class="text-[10px] text-zinc-600 mt-2 font-medium">Nếu quá thời gian trên, suất đặt cọc sẽ bị hủy và xe được trả lại Showroom.</p>
                            @else
                                <p class="text-xs text-zinc-400 font-bold uppercase italic leading-relaxed">
                                    Xử lý giao dịch vào lúc: {{ $order->updated_at->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Guidance Sidebar -->
                <div class="space-y-8">
                    @if($order->status === \App\Enums\OrderStatus::PendingPayment)
                        <div class="bg-[#1C69D4]/10 border-2 border-[#1C69D4] p-8 shadow-[0_0_60px_rgba(28,105,212,0.15)] relative overflow-hidden">
                            <div class="absolute -top-4 -right-4 text-6xl text-[#1C69D4]/10 font-black italic select-none">PAY</div>
                            <h3 class="text-sm font-black uppercase tracking-[0.2em] text-white mb-6">Hướng dẫn thanh toán</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <div class="text-[10px] font-black uppercase text-[#1C69D4] mb-2 tracking-widest">Ngân hàng thụ hưởng</div>
                                    <div class="text-sm font-black text-white italic uppercase tracking-tighter">VIETCOMBANK - BMW GROUP VIETNAM</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase text-[#1C69D4] mb-2 tracking-widest">Số tài khoản</div>
                                    <div class="text-lg font-black text-white tracking-[0.2em] tabular-nums">1900 8888 7777</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase text-[#1C69D4] mb-2 tracking-widest">Nội dung chuyển khoản</div>
                                    <div class="bg-black/50 border border-zinc-800 p-4 text-white font-black text-sm uppercase tracking-widest text-center shadow-inner">
                                        COC {{ $order->id }}
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-3 font-medium text-center italic leading-none">Vui lòng nhập ĐÚNG nội dung để hệ thống tự động xác nhận.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-xl">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-[#1C69D4] mb-4">Cam kết của BMW</h3>
                        <p class="text-[10px] text-zinc-500 leading-relaxed italic mb-4">
                            Dữ liệu cấu hình xe và số tiền đặt cọc được snapshot trọn đời tại thời điểm giao dịch. Mọi thay đổi về giá bán lẻ sau này sẽ không ảnh hưởng đến quyền lợi của bạn.
                        </p>
                        <p class="text-[10px] text-zinc-500 leading-relaxed italic">
                            Suất đặt cọc này đảm bảo quyền ưu tiên bàn giao xe ngay khi có hàng tại Showroom bạn đã đăng ký.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
