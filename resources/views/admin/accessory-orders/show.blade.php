<x-admin-layout>
    <x-admin.page-header
        eyebrow="Accessory order #{{ $order->id }}"
        title="Chi tiết"
        accent="Đơn phụ kiện"
        :description="$order->customer_name"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.accessory-orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[1fr_420px]">
        <div class="space-y-8">
            <x-admin.card class="!p-0 overflow-hidden">
                <div class="border-b border-white/[0.08] p-7">
                    <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-zinc-400">Khách hàng</p>
                    <h2 class="mt-2 text-3xl font-black uppercase tracking-normal text-white">{{ $order->customer_name }}</h2>
                </div>
                <div class="grid grid-cols-1 gap-3 p-6 md:grid-cols-2">
                    <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Mã tham chiếu / Chứng từ / Mã KH</p>
                        <p class="mt-2 text-sm font-bold tabular-nums text-[#70A7FF]">
                            {{ $order->publicReferenceCode() }}
                            @if($order->daily_sequence_code)
                                <span class="text-zinc-500">&middot;</span>
                                <span class="text-white">{{ $order->daily_sequence_code }}</span>
                            @endif
                            @if($order->customer_internal_code)
                                <span class="text-zinc-500">&middot;</span>
                                <span class="text-zinc-300">{{ $order->customer_internal_code }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Sales phụ trách</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $order->assignedSales?->exists ? $order->assignedSales->name : 'Chưa phân công' }}</p>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Số điện thoại</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $order->customer_phone }}</p>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Email</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $order->customer_email ?: 'Không cung cấp' }}</p>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-5 md:col-span-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Địa chỉ</p>
                        <p class="mt-2 text-sm font-bold leading-6 text-white">{{ $order->customer_address }}</p>
                    </div>
                    <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-5 md:col-span-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Ghi chú khách hàng</p>
                        <p class="mt-2 text-sm font-medium leading-6 text-zinc-300">{{ $order->notes ?: 'Không có ghi chú.' }}</p>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-zinc-400">Xử lý đơn</p>
                <form method="POST" action="{{ route('admin.accessory-orders.update-status', $order) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Trạng thái</label>
                        @if($order->isTerminalStatus())
                            <input type="hidden" name="status" value="{{ $order->status }}">
                            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-xs">
                                <span class="font-bold text-white">{{ $order->statusLabel() }}</span>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-400">
                                    Đã đóng · Chỉ sửa ghi chú
                                </span>
                            </div>
                        @else
                            <x-ui.custom-select
                                name="status"
                                :value="(string) old('status', $order->status)"
                                :options="$statusOptions"
                                :show-error="true"
                            />
                        @endif
                        @error('status')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Ghi chú nội bộ</label>
                        <textarea name="admin_notes" rows="6" class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                        @error('admin_notes')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-xl bg-[#1C69D4] px-8 py-3.5 text-[10px] font-semibold uppercase tracking-[0.22em] text-white shadow-sm shadow-[#1C69D4]/25 transition-all hover:bg-[#2575e6] cursor-pointer">
                        Cập nhật đơn
                    </button>
                </form>
            </x-admin.card>
        </div>

        <aside class="space-y-8">
            <x-admin.card class="!p-0 overflow-hidden">
                <div class="aspect-[16/10] bg-[#070A10]">
                    @if($order->product)
                        <img src="{{ $order->product->displayImageUrl() }}" alt="{{ $order->product->name }}" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="space-y-5 p-7">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#70A7FF]">{{ $order->product?->category?->name ?? 'Phụ kiện BMW' }}</p>
                        <h2 class="mt-2 text-2xl font-black uppercase leading-tight text-white">{{ $order->product?->name ?? 'Sản phẩm đã xóa' }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Số lượng</p>
                            <p class="mt-2 text-xl font-black text-white">{{ $order->quantity }}</p>
                        </div>
                        <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Đơn giá</p>
                            <p class="mt-2 text-lg font-black text-white">{{ $order->product ? number_format($order->product->price) : 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-[#1C69D4]/20 bg-[#1C69D4]/10 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-[#70A7FF]">Tổng tiền</p>
                            <p class="mt-2 text-lg font-black tabular-nums text-white">{{ number_format($order->total_amount) }} <span class="text-xs text-zinc-400">VNĐ</span></p>
                        </div>
                    </div>
                    <div>
                        <span class="inline-flex rounded-full border px-4 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $order->statusColorClass() }}">
                            {{ $order->statusLabel() }}
                        </span>
                    </div>
                    <div class="border-t border-white/[0.08] pt-5 text-xs font-semibold uppercase tracking-widest text-zinc-400">
                        <p>Tạo lúc: {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mt-2">Xác nhận: {{ $order->confirmed_at?->format('d/m/Y H:i') ?? 'Chưa xác nhận' }}</p>
                    </div>
                </div>
            </x-admin.card>
        </aside>
    </div>
</x-admin-layout>
