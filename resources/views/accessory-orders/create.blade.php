<x-app-layout>
    @section('title', 'Đặt hàng phụ kiện - BMW Showroom')

    <x-public.form-shell
        eyebrow="BMW Accessories"
        title="Đặt hàng phụ kiện"
        description="Gửi thông tin để showroom xác nhận tồn kho, tương thích với xe và lịch bàn giao phụ kiện chính hãng."
        class="border-t border-zinc-900"
    >
        <div class="mb-8 grid grid-cols-1 gap-5 border border-white/10 bg-zinc-950 p-5 sm:grid-cols-[140px_1fr]">
            <div class="aspect-[4/3] overflow-hidden bg-black">
                <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#70A7FF]">
                    {{ $product->category?->name ?? 'Phụ kiện BMW' }}
                </p>
                <h2 class="mt-3 text-2xl font-black uppercase leading-tight tracking-normal text-white">
                    {{ $product->name }}
                </h2>
                <p class="mt-3 text-xl font-black tracking-normal text-white">
                    {{ number_format($product->price) }} <span class="text-xs text-zinc-500">VNĐ</span>
                </p>
                <a href="{{ route('products.show', $product->slug) }}" class="mt-4 inline-flex border border-zinc-700 px-4 py-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white hover:text-white">
                    Xem lại sản phẩm
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-8 border border-emerald-500/20 bg-emerald-500/5 p-5 text-sm font-bold text-emerald-400">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-8 border border-rose-500/20 bg-rose-500/5 p-5 text-sm font-bold text-rose-300">
                Vui lòng kiểm tra lại thông tin đặt hàng.
            </div>
        @endif

        <form method="POST" action="{{ route('accessory-orders.store', $product->slug) }}" class="space-y-7">
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="customer_name" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Họ và tên *</label>
                    <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    @error('customer_name')
                        <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_phone" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Số điện thoại *</label>
                    <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone') }}" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    @error('customer_phone')
                        <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="customer_address" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Địa chỉ *</label>
                <input id="customer_address" name="customer_address" type="text" value="{{ old('customer_address') }}" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                @error('customer_address')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-[1fr_150px]">
                <div>
                    <label for="customer_email" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Email</label>
                    <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    @error('customer_email')
                        <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quantity" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Số lượng *</label>
                    <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    @error('quantity')
                        <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ghi chú</label>
                <textarea id="notes" name="notes" rows="5" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 border-t border-white/10 pt-8 sm:grid-cols-[1fr_auto]">
                <button type="submit" class="bg-[#1C69D4] px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                    Xác nhận đặt hàng
                </button>
                <a href="{{ route('accessories.index') }}" class="inline-flex items-center justify-center border border-zinc-700 px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-zinc-300 transition-all hover:border-white hover:text-white">
                    Xem phụ kiện khác
                </a>
            </div>
        </form>
    </x-public.form-shell>
</x-app-layout>
