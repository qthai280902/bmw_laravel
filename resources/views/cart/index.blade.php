<x-app-layout>
    <x-slot name="header">
        <h2 class="text-4xl font-black text-white uppercase tracking-tighter italic">
            Giỏ <span class="text-accent underline decoration-4">Hàng</span>
        </h2>
        <p class="text-zinc-500 mt-2 uppercase text-xs tracking-widest font-bold">Danh sách xe đã chọn</p>
    </x-slot>

    <div class="py-16 bg-zinc-950 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-8 border-l-4 border-green-400 bg-zinc-900 p-4 text-green-400 text-xs font-black uppercase tracking-widest">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-8 border-l-4 border-red-500 bg-zinc-900 p-4 text-red-400 text-xs font-black uppercase tracking-widest">
                    {{ session('error') }}
                </div>
            @endif

            @if($items->isEmpty())
                {{-- Empty State --}}
                <div class="text-center py-32 border border-zinc-900">
                    <svg class="w-16 h-16 text-zinc-800 mx-auto mb-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-4H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-2xl font-black text-zinc-800 uppercase italic tracking-tighter mb-4">Giỏ hàng trống</h3>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-accent text-xs font-black uppercase tracking-widest border-b-2 border-accent pb-1 hover:text-white hover:border-white transition-colors">
                        Khám phá showroom
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-12">

                    {{-- Cart Items List --}}
                    <div class="xl:col-span-2 space-y-4">
                        <div class="flex items-center justify-between mb-8 pb-4 border-b border-zinc-900">
                            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500">
                                {{ $items->count() }} mẫu xe đã chọn
                            </p>
                            <form action="{{ route('cart.clear') }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-zinc-600 hover:text-red-400 transition-colors">
                                    Xóa tất cả
                                </button>
                            </form>
                        </div>

                        @foreach($items as $item)
                            <div class="bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors flex gap-6 p-6">
                                {{-- Vehicle Image --}}
                                <div class="w-40 h-28 flex-shrink-0 overflow-hidden bg-zinc-950">
                                    @if(isset($item->product->primaryImage) && $item->product->primaryImage)
                                        <img src="{{ asset('storage/' . $item->product->primaryImage->path) }}"
                                             class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-500"
                                             alt="{{ $item->product->name }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-zinc-800 font-black text-xs uppercase">No Image</div>
                                    @endif
                                </div>

                                {{-- Vehicle Info --}}
                                <div class="flex-grow">
                                    <p class="text-[10px] font-black text-accent uppercase tracking-widest mb-1">
                                        {{ $item->product?->brand?->name }}
                                    </p>
                                    <h3 class="text-xl font-black text-white uppercase tracking-tighter leading-none mb-4">
                                        {{ $item->product?->name }}
                                    </h3>
                                    <p class="text-xs text-zinc-500 font-bold uppercase tracking-widest">
                                        Tiền đặt cọc: <span class="text-white">{{ number_format($item->product?->deposit_amount ?? 0) }} VNĐ</span>
                                    </p>
                                </div>

                                {{-- Remove Button --}}
                                <div class="flex flex-col justify-between items-end">
                                    <form action="{{ route('cart.destroy', $item->product_id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-zinc-600 hover:text-red-400 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <p class="text-lg font-black text-white tracking-tighter">
                                        {{ number_format(($item->product?->deposit_amount ?? 0) * $item->quantity) }}
                                        <span class="text-xs text-zinc-500">VNĐ</span>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Order Summary --}}
                    <div class="xl:col-span-1">
                        <div class="bg-zinc-900 border border-zinc-800 p-8 sticky top-8">
                            <h3 class="text-xs font-black uppercase tracking-widest text-white mb-10 border-b border-zinc-800 pb-4">
                                Tổng đặt cọc
                            </h3>

                            <div class="space-y-4 mb-8">
                                @foreach($items as $item)
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-zinc-500 font-bold uppercase tracking-widest truncate mr-4">{{ $item->product?->name }}</span>
                                        <span class="text-white font-black whitespace-nowrap">{{ number_format($item->product?->deposit_amount ?? 0) }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-zinc-800 pt-6 mb-8">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Tổng tiền cọc</span>
                                    <span class="text-2xl font-black text-white tracking-tighter">
                                        {{ number_format($total) }} <span class="text-xs text-zinc-500">VNĐ</span>
                                    </span>
                                </div>
                            </div>

                            @auth
                                <a href="{{ route('checkout.index') }}"
                                   class="block w-full bg-white text-black text-center text-[10px] font-black uppercase tracking-widest py-5 hover:bg-accent hover:text-white transition-all shadow-xl">
                                    Tiến hành đặt cọc
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="block w-full bg-zinc-800 text-white text-center text-[10px] font-black uppercase tracking-widest py-5 hover:bg-accent transition-all mb-3">
                                    Đăng nhập để đặt cọc
                                </a>
                                <p class="text-center text-[10px] text-zinc-600 uppercase tracking-widest">
                                    Giỏ hàng sẽ được giữ sau khi đăng nhập
                                </p>
                            @endauth
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
