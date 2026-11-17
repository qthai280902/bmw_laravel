<div>
    <div class="mb-10">
        <p id="form-subtitle" class="text-[10px] font-black uppercase tracking-[0.34em] text-[#70A7FF]">
            {{ $this->isService ? 'Chăm sóc chuyên nghiệp' : 'Trải nghiệm showroom' }}
        </p>
        <h2 id="form-title" class="mt-3 text-3xl font-black uppercase leading-none tracking-normal text-white">
            {{ $requestedType?->label() ?? 'Đăng ký dịch vụ' }}
        </h2>
        <p class="mt-4 text-sm font-medium leading-6 text-zinc-500">
            Các trường có dấu * là bắt buộc. Dữ liệu sẽ được gửi vào luồng lịch hẹn hiện tại.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-8 rounded-none border border-rose-500/20 bg-rose-500/5 p-5 text-sm font-bold text-rose-300">
            <p>Vui lòng kiểm tra lại thông tin trước khi gửi.</p>
            <ul class="mt-3 list-inside list-disc space-y-1 text-xs font-medium text-rose-200">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('appointments.store') }}" method="POST" class="space-y-8">
        @csrf
        <input
            type="hidden"
            name="ai_visitor_id"
            value="{{ old('ai_visitor_id', request()->cookie('bmw_ai_visitor_id', '')) }}"
            data-ai-visitor-field="true"
        >

        <section class="space-y-5">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <h3 class="text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Bạn cần hỗ trợ gì?</h3>
                <span wire:loading wire:target="type,categoryType,productId" class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF]">
                    Đang cập nhật...
                </span>
            </div>

            <select
                id="type-select"
                name="type"
                wire:model.live="type"
                required
                class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-xs font-black uppercase tracking-[0.18em] text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
            >
                <optgroup label="Trải nghiệm & Mua xe" class="bg-zinc-950 text-zinc-400">
                    @foreach($salesTypes as $saleType)
                        <option value="{{ $saleType->value }}">{{ $saleType->label() }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Dịch vụ & Chăm sóc xe" class="bg-zinc-950 text-zinc-400">
                    @foreach($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->value }}">{{ $serviceType->label() }}</option>
                    @endforeach
                </optgroup>
            </select>
        </section>

        <section class="space-y-5">
            <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Thông tin liên hệ</h3>

            @guest
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Họ và tên *</label>
                        <input
                            type="text"
                            name="guest_name"
                            wire:model.live.debounce.300ms="guestName"
                            required
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                        @error('guestName')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Số điện thoại *</label>
                        <input
                            type="text"
                            name="guest_phone"
                            wire:model.live.debounce.300ms="guestPhone"
                            required
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                        @error('guestPhone')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Email</label>
                        <input
                            type="email"
                            name="guest_email"
                            wire:model.live.debounce.300ms="guestEmail"
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                        @error('guestEmail')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @else
                <div class="rounded-none border border-zinc-800 bg-zinc-950 p-5 text-sm font-medium text-zinc-300">
                    Xin chào <span class="font-black text-white">{{ auth()->user()->name }}</span>, thông tin tài khoản của bạn sẽ được gắn với yêu cầu này.
                </div>
            @endguest
        </section>

        @if($this->isService)
            <section class="space-y-5" wire:key="booking-service-section">
                <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Xe của bạn</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Tên xe của bạn *</label>
                        <input
                            type="text"
                            name="customer_car_model"
                            wire:model.live.debounce.300ms="customerCarModel"
                            required
                            placeholder="Ví dụ: BMW 320i 2020"
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                        @error('customerCarModel')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Biển số / Tình trạng</label>
                        <input
                            type="text"
                            name="customer_car_condition"
                            wire:model.live.debounce.300ms="customerCarCondition"
                            placeholder="Ví dụ: 30A-12345, đã đi 50.000km"
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                    </div>
                </div>
            </section>
        @else
            <section class="space-y-5" wire:key="booking-product-section">
                <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Sản phẩm quan tâm</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">
                            Danh mục {{ $this->isProductOptional ? '' : '*' }}
                        </label>
                        <select
                            id="category_selector"
                            wire:model.live="categoryType"
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-xs font-black uppercase tracking-[0.18em] text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                            <option value="">-- Chọn danh mục --</option>
                            <option value="oto">BMW Ô tô</option>
                            <option value="xe_may">BMW Motorrad</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">
                            Chọn sản phẩm {{ $this->isProductOptional ? '' : '*' }}
                        </label>
                        <select
                            id="product_selector"
                            name="product_id"
                            wire:model.live="productId"
                            {{ $this->isProductOptional ? '' : 'required' }}
                            @disabled($categoryType === '')
                            class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors disabled:cursor-not-allowed disabled:opacity-50 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                            @if($categoryType === '')
                                <option value="">-- Vui lòng chọn danh mục trước --</option>
                            @else
                                <option value="">-- Chọn sản phẩm --</option>
                                @foreach($this->availableProducts as $availableProduct)
                                    <option value="{{ $availableProduct->id }}">{{ $availableProduct->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('productId')
                            <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if($this->selectedProduct)
                    <div class="grid grid-cols-1 gap-4 rounded-none border border-white/10 bg-[#121215] p-4 sm:grid-cols-[120px_1fr]">
                        <div class="aspect-[4/3] overflow-hidden bg-black">
                            <img src="{{ $this->selectedProduct->displayImageUrl() }}" alt="{{ $this->selectedProduct->name }}" class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">
                                {{ $this->selectedProduct->category?->name ?? 'BMW Showroom' }}
                            </p>
                            <p class="mt-1 truncate text-base font-black uppercase tracking-wide text-white">
                                {{ $this->selectedProduct->name }}
                            </p>
                            <p class="mt-2 text-sm font-black tabular-nums text-zinc-200">
                                {{ number_format($this->selectedProduct->price) }} <span class="text-xs text-zinc-500">VNĐ</span>
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <section class="space-y-5">
            <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Thời gian & ghi chú</h3>

            <div wire:ignore>
                <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ngày dự kiến *</label>
                <input
                    type="text"
                    name="appointment_date"
                    value="{{ $appointmentDate }}"
                    x-on:change="$wire.set('appointmentDate', $event.target.value)"
                    required
                    class="flatpickr-input w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    placeholder="Chọn ngày và giờ"
                >
            </div>
            @error('appointmentDate')
                <p class="text-xs font-bold text-rose-400">{{ $message }}</p>
            @enderror

            <div>
                <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ghi chú thêm</label>
                <textarea
                    name="notes"
                    wire:model.live.debounce.400ms="notes"
                    rows="4"
                    class="w-full rounded-none border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                ></textarea>
                @error('notes')
                    <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <div class="border-t border-white/10 pt-8">
            <button type="submit" class="w-full rounded-none bg-[#1C69D4] px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                Xác nhận gửi yêu cầu
            </button>
        </div>
    </form>
</div>
