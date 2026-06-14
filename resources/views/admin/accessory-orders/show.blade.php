<x-admin-layout>
    <x-admin.page-header
        eyebrow="Accessory order #{{ $order->id }}"
        title="Chi tiết"
        accent="Đơn phụ kiện"
        :description="$order->customer_name"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.accessory-orders.index') }}" class="inline-flex items-center justify-center border border-zinc-800 px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[1fr_420px]">
        <div class="space-y-8">
            <x-admin.card class="!p-0 overflow-hidden">
                <div class="border-b border-zinc-800 p-8">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Khách hàng</p>
                    <h2 class="mt-3 text-3xl font-black uppercase tracking-normal text-white">{{ $order->customer_name }}</h2>
                </div>
                <div class="grid grid-cols-1 gap-px bg-zinc-800 md:grid-cols-2">
                    <div class="bg-zinc-900 p-6">
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Số điện thoại</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $order->customer_phone }}</p>
                    </div>
                    <div class="bg-zinc-900 p-6">
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Email</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $order->customer_email ?: 'Không cung cấp' }}</p>
                    </div>
                    <div class="bg-zinc-900 p-6 md:col-span-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Địa chỉ</p>
                        <p class="mt-2 text-sm font-bold leading-6 text-white">{{ $order->customer_address }}</p>
                    </div>
                    <div class="bg-zinc-900 p-6 md:col-span-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Ghi chú khách hàng</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-zinc-300">{{ $order->notes ?: 'Không có ghi chú.' }}</p>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Xử lý đơn</p>
                <form method="POST" action="{{ route('admin.accessory-orders.update-status', $order) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Trạng thái</label>
                        <select name="status" class="w-full border-zinc-800 bg-zinc-950 px-4 py-3 text-xs font-black uppercase tracking-widest text-zinc-300 focus:border-zinc-500 focus:ring-0">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $order->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-widest text-zinc-500">Ghi chú nội bộ</label>
                        <textarea name="admin_notes" rows="6" class="w-full border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-white focus:border-zinc-500 focus:ring-0">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                        @error('admin_notes')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="bg-white px-8 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-black transition-all hover:bg-[#1C69D4] hover:text-white">
                        Cập nhật đơn
                    </button>
                </form>
            </x-admin.card>
        </div>

        <aside class="space-y-8">
            <x-admin.card class="!p-0 overflow-hidden">
                <div class="aspect-[16/10] bg-zinc-950">
                    @if($order->product)
                        <img src="{{ $order->product->displayImageUrl() }}" alt="{{ $order->product->name }}" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="space-y-5 p-8">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#1C69D4]">{{ $order->product?->category?->name ?? 'Phụ kiện BMW' }}</p>
                        <h2 class="mt-3 text-2xl font-black uppercase leading-none text-white">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-zinc-800">
                        <div class="bg-zinc-900 p-5">
                            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Số lượng</p>
                            <p class="mt-2 text-xl font-black text-white">{{ $order->quantity }}</p>
                        </div>
                        <div class="bg-zinc-900 p-5">
                            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Giá</p>
                            <p class="mt-2 text-xl font-black text-white">{{ $order->product ? number_format($order->product->price) : 'N/A' }}</p>
                        </div>
                    </div>
                    <div>
                        <span class="inline-flex border px-4 py-2 text-[9px] font-black uppercase tracking-[0.2em] {{ $order->statusColorClass() }}">
                            {{ $order->statusLabel() }}
                        </span>
                    </div>
                    <div class="border-t border-zinc-800 pt-5 text-xs font-bold uppercase tracking-widest text-zinc-500">
                        <p>Tạo lúc: {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mt-2">Xác nhận: {{ $order->confirmed_at?->format('d/m/Y H:i') ?? 'Chưa xác nhận' }}</p>
                    </div>
                </div>
            </x-admin.card>
        </aside>
    </div>
</x-admin-layout>
