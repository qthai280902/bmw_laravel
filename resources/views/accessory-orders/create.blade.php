<x-app-layout>
    @section('title', 'Đặt hàng phụ kiện - BMW Showroom')

    <x-public.form-shell
        eyebrow="BMW Accessories"
        title="Đặt hàng phụ kiện"
        description="Gửi thông tin để showroom xác nhận tồn kho, tương thích với xe và lịch bàn giao phụ kiện chính hãng."
        class="border-t border-zinc-900"
    >
        <div class="mb-8 grid grid-cols-1 gap-5 rounded-xl border border-white/10 bg-zinc-950 p-5 sm:grid-cols-[140px_1fr]">
            <div class="aspect-[4/3] overflow-hidden rounded-lg bg-black">
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
                <a href="{{ route('products.show', $product->slug) }}" class="mt-4 inline-flex rounded-lg border border-zinc-700 px-4 py-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white hover:text-white">
                    Xem lại sản phẩm
                </a>
            </div>
        </div>

        <livewire:accessory-order-calculator :product="$product" />
    </x-public.form-shell>
</x-app-layout>
