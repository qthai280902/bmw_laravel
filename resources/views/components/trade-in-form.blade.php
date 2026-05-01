{{-- Trade-In Dedicated Form Component --}}
<div x-data="{ showModal: false }" class="inline-block">
    <button @click="showModal = true" type="button"
        class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300 cursor-pointer">
        Đăng ký đổi xe
    </button>

    {{-- Modal Overlay --}}
    <div x-show="showModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        @click.self="showModal = false" style="display: none;">

        <div x-show="showModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-zinc-950 border border-zinc-800 shadow-2xl">

            <div class="p-8 md:p-12">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <p class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-2">Trade-In Program</p>
                        <h2 class="text-2xl font-black text-white uppercase tracking-widest">Đổi xe cũ — Lấy BMW mới</h2>
                    </div>
                    <button @click="showModal = false" class="text-zinc-500 hover:text-white transition-colors p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                @if ($errors->any() && old('type') === 'trade_in')
                    <div class="mb-8 p-4 bg-red-950/50 border border-red-900 text-red-500 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('appointments.store') }}" method="POST" class="space-y-8">
                    @csrf
                    <input type="hidden" name="type" value="trade_in">

                    {{-- Thông tin khách hàng --}}
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Thông tin liên hệ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Họ và tên *</label>
                                <input type="text" name="guest_name" value="{{ old('guest_name', auth()->user()?->name) }}" required
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Số điện thoại *</label>
                                <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" required
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Email</label>
                                <input type="email" name="guest_email" value="{{ old('guest_email', auth()->user()?->email) }}"
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                            </div>
                        </div>
                    </div>

                    {{-- Thông tin xe cũ (Trade-In) --}}
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Xe đang sở hữu</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Tên xe hiện tại *</label>
                                <input type="text" name="meta_data[current_car]" value="{{ old('meta_data.current_car') }}" required placeholder="Ví dụ: Toyota Camry 2.5Q"
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors placeholder:text-zinc-700">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Năm sản xuất *</label>
                                <input type="number" name="meta_data[year]" value="{{ old('meta_data.year') }}" required min="2000" max="{{ date('Y') }}" placeholder="2022"
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors placeholder:text-zinc-700">
                            </div>
                        </div>
                    </div>

                    {{-- Sản phẩm BMW mong muốn --}}
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Sản phẩm BMW muốn đổi</h3>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Chọn sản phẩm BMW *</label>
                            <input type="text" name="meta_data[desired_bmw]" value="{{ old('meta_data.desired_bmw') }}" required placeholder="Ví dụ: BMW 530i, BMW X5..."
                                class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors placeholder:text-zinc-700">
                        </div>
                    </div>

                    {{-- Ngày hẹn --}}
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Thời gian mong muốn</h3>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Ngày hẹn định giá *</label>
                            <input type="text" name="appointment_date" value="{{ old('appointment_date') }}" required
                                class="flatpickr-input w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors"
                                placeholder="Chọn ngày và giờ">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Ghi chú thêm</label>
                            <textarea name="notes" rows="3"
                                class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors" placeholder="Tình trạng xe, số km đã đi...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-accent text-white py-5 text-sm font-black uppercase tracking-[0.2em] hover:bg-white hover:text-black transition-all">
                        Gửi yêu cầu định giá
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
