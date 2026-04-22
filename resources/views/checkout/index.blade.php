<x-app-layout>
    <x-slot name="header">
        <h2 class="text-4xl font-black text-white uppercase tracking-tighter italic">
            Xác nhận <span class="text-accent underline decoration-4">Đặt cọc</span>
        </h2>
        <p class="text-zinc-500 mt-2 uppercase text-xs tracking-widest font-bold">Đơn hàng sẽ hết hạn sau 24 giờ</p>
    </x-slot>

    <div class="py-16 bg-zinc-950 min-h-screen">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

                {{-- Order Summary --}}
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400 mb-6">Đơn hàng của bạn</h3>

                    @foreach($items as $item)
                        <div class="flex gap-4 bg-zinc-900 border border-zinc-800 p-4">
                            <div class="w-24 h-16 flex-shrink-0 overflow-hidden bg-zinc-950">
                                @if(isset($item->product->primaryImage) && $item->product->primaryImage)
                                    @if(Str::startsWith($item->product->primaryImage->path, 'http'))
                                        <img src="{{ $item->product->primaryImage->path }}" class="w-full h-full object-cover grayscale" alt="{{ $item->product->name }}">
                                    @else
                                        <img src="{{ Storage::url($item->product->primaryImage->path) }}" class="w-full h-full object-cover grayscale" alt="{{ $item->product->name }}">
                                    @endif
                                @else
                                    <div class="w-full h-full bg-zinc-900"></div>
                                @endif
                            </div>
                            <div class="flex-grow">
                                <p class="text-[10px] font-black text-accent uppercase tracking-widest">{{ $item->product?->brand?->name }}</p>
                                <p class="font-black text-white uppercase tracking-tight">{{ $item->product?->name }}</p>
                                <p class="text-xs text-zinc-500 mt-1">Đặt cọc: <span class="text-white">{{ number_format($item->product?->deposit_amount ?? 0) }} VNĐ</span></p>
                            </div>
                        </div>
                    @endforeach

                    {{-- Total --}}
                    <div class="border-t border-zinc-800 pt-6 flex justify-between items-baseline">
                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Tổng tiền cọc</span>
                        <span class="text-3xl font-black text-white tracking-tighter">
                            {{ number_format($total) }} <span class="text-sm text-zinc-500">VNĐ</span>
                        </span>
                    </div>
                </div>

                {{-- Checkout Form & Bank Info --}}
                <div class="space-y-8">
                    {{-- 24h Warning --}}
                    <div class="border border-yellow-600/30 bg-yellow-900/10 p-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-yellow-500 mb-1">Lưu ý quan trọng</p>
                                <p class="text-xs text-zinc-400 leading-relaxed">
                                    Đơn đặt cọc sẽ <strong class="text-white">hết hạn sau 24 giờ</strong>. Vui lòng chuyển khoản trong thời gian này. Sau khi hết hạn, xe sẽ được mở bán trở lại.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Bank Transfer Info --}}
                    <div class="bg-zinc-900 border border-zinc-800 p-8 space-y-4">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-accent mb-6">Thông tin chuyển khoản</h4>
                        <div class="space-y-3">
                            @foreach([
                                'Ngân hàng' => 'Techcombank',
                                'Số tài khoản' => '19001234567',
                                'Chủ tài khoản' => 'CONG TY CP BMW SHOWROOM',
                                'Nội dung' => 'DAT COC - ' . auth()->user()->name,
                            ] as $label => $value)
                                <div class="flex justify-between items-center border-b border-zinc-800/50 pb-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ $label }}</span>
                                    <span class="text-sm font-black text-white uppercase">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- QR Placeholder --}}
                        <div class="mt-6 flex justify-center">
                            <div class="w-40 h-40 bg-zinc-800 border border-zinc-700 flex items-center justify-center">
                                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600 text-center">QR Thanh toán<br>(Placeholder)</p>
                            </div>
                        </div>
                    </div>

                    {{-- Confirm Button --}}
                    <form action="{{ route('checkout.store') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full bg-white text-black text-[10px] font-black uppercase tracking-widest py-6 hover:bg-accent hover:text-white transition-all shadow-xl">
                            Xác nhận đặt cọc & Khóa xe
                        </button>
                        <p class="mt-3 text-center text-[10px] text-zinc-600 uppercase tracking-widest">
                            Nhấn xác nhận để giữ chỗ ngay lập tức
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
