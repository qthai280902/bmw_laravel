<div>
    @if (session('success'))
        <div class="mb-8 rounded-none border border-emerald-500/20 bg-emerald-500/5 p-5 text-sm font-bold text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-8 rounded-none border border-rose-500/20 bg-rose-500/5 p-5 text-sm font-bold text-rose-300">
            Vui lòng kiểm tra lại thông tin đặt hàng.
        </div>
    @endif

    <form method="POST" action="{{ route('accessory-orders.store', $product->slug) }}" class="space-y-7">
        @csrf

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="customer_name" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Họ và tên *</label>
                <input
                    id="customer_name"
                    name="customer_name"
                    type="text"
                    wire:model.live.debounce.300ms="customerName"
                    required
                    class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
                @error('customer_name')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
                @error('customerName')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="customer_phone" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Số điện thoại *</label>
                <input
                    id="customer_phone"
                    name="customer_phone"
                    type="text"
                    wire:model.live.debounce.300ms="customerPhone"
                    required
                    class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
                @error('customer_phone')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
                @error('customerPhone')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="customer_address" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Địa chỉ *</label>
            <input
                id="customer_address"
                name="customer_address"
                type="text"
                wire:model.live.debounce.300ms="customerAddress"
                required
                class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
            >
            @error('customer_address')
                <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
            @enderror
            @error('customerAddress')
                <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-[1fr_200px]">
            <div>
                <label for="customer_email" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Email</label>
                <input
                    id="customer_email"
                    name="customer_email"
                    type="email"
                    wire:model.live.debounce.300ms="customerEmail"
                    class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
                @error('customer_email')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
                @error('customerEmail')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="quantity" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Số lượng *</label>
                <div class="flex rounded-none border border-zinc-800 bg-zinc-950">
                    <button
                        type="button"
                        wire:click="decrementQuantity"
                        aria-label="Giảm số lượng"
                        class="flex w-12 items-center justify-center border-r border-zinc-800 text-sm font-black text-zinc-400 transition-colors hover:bg-zinc-900 hover:text-white"
                    >
                        −
                    </button>
                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        max="99"
                        wire:model.live="quantity"
                        required
                        class="w-full rounded-none border-0 bg-transparent px-3 py-4 text-center text-sm font-black tabular-nums text-white focus:ring-0"
                    >
                    <button
                        type="button"
                        wire:click="incrementQuantity"
                        aria-label="Tăng số lượng"
                        class="flex w-12 items-center justify-center border-l border-zinc-800 text-sm font-black text-zinc-400 transition-colors hover:bg-zinc-900 hover:text-white"
                    >
                        +
                    </button>
                </div>
                @error('quantity')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Live Cost Breakdown --}}
        <div class="rounded-none border border-white/10 bg-[#121215] p-5">
            <div class="flex items-center justify-between text-xs text-zinc-400">
                <span class="font-bold uppercase tracking-[0.18em]">Đơn giá × Số lượng</span>
                <span class="font-mono tabular-nums text-zinc-200">{{ number_format($product->price) }} VNĐ × {{ $quantity }}</span>
            </div>
            <div class="mt-3 flex items-baseline justify-between border-t border-white/10 pt-3">
                <span class="text-[10px] font-black uppercase tracking-[0.24em] text-[#70A7FF]">Tổng tạm tính</span>
                <span class="text-2xl font-black tabular-nums text-white">
                    {{ number_format($this->totalAmount) }} <span class="text-xs font-bold text-zinc-500">VNĐ</span>
                </span>
            </div>
        </div>

        <div>
            <label for="notes" class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ghi chú</label>
            <textarea
                id="notes"
                name="notes"
                wire:model.live.debounce.400ms="notes"
                rows="5"
                class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
            ></textarea>
            @error('notes')
                <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-white/10 pt-8 sm:grid-cols-[1fr_auto]">
            <button type="submit" class="rounded-none bg-[#1C69D4] px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                Xác nhận đặt hàng
            </button>
            <a href="{{ route('accessories.index') }}" class="rounded-none border border-zinc-700 px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:border-white hover:text-white">
                Quay lại phụ kiện
            </a>
        </div>
    </form>
</div>
