<x-app-layout>
    @php
        $currentTypeStr = old('type', request('type'));
        $requestedType = \App\Enums\AppointmentType::tryFrom($currentTypeStr);
        $serviceTypes = [
            \App\Enums\AppointmentType::Maintenance->value,
            \App\Enums\AppointmentType::Detailing->value,
            \App\Enums\AppointmentType::CarWash->value,
        ];
        $isService = in_array($currentTypeStr, $serviceTypes, true);
    @endphp

    <x-public.form-shell
        eyebrow="{{ $isService ? 'BMW Aftersales' : 'BMW Advisor' }}"
        title="{{ $requestedType?->label() ?? 'Đăng ký dịch vụ' }}"
        description="Gửi thông tin để showroom chuẩn bị mẫu xe, cấu hình hoặc lịch chăm sóc phù hợp trước khi cố vấn liên hệ xác nhận."
        class="border-t border-zinc-900"
    >
        <div class="mb-10">
            <p id="form-subtitle" class="text-[10px] font-black uppercase tracking-[0.34em] text-[#70A7FF]">
                {{ $isService ? 'Chăm sóc chuyên nghiệp' : 'Trải nghiệm showroom' }}
            </p>
            <h2 id="form-title" class="mt-3 text-3xl font-black uppercase leading-none tracking-normal text-white">
                {{ $requestedType?->label() ?? 'Đăng ký dịch vụ' }}
            </h2>
            <p class="mt-4 text-sm font-medium leading-6 text-zinc-500">
                Các trường có dấu * là bắt buộc. Dữ liệu sẽ được gửi vào luồng lịch hẹn hiện tại.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-8 border border-rose-500/20 bg-rose-500/5 p-5 text-sm font-bold text-rose-300">
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

            <section class="space-y-5">
                <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Bạn cần hỗ trợ gì?</h3>
                <select id="type-select" name="type" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-xs font-black uppercase tracking-[0.18em] text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]" onchange="updateFormHeader(this.value)">
                    @if($isService)
                        @foreach([\App\Enums\AppointmentType::Maintenance, \App\Enums\AppointmentType::Detailing, \App\Enums\AppointmentType::CarWash] as $type)
                            <option value="{{ $type->value }}" {{ $currentTypeStr == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    @else
                        @foreach([\App\Enums\AppointmentType::TestDrive, \App\Enums\AppointmentType::Viewing, \App\Enums\AppointmentType::Quote, \App\Enums\AppointmentType::Consult, \App\Enums\AppointmentType::AdvisorMeeting] as $type)
                            <option value="{{ $type->value }}" {{ $currentTypeStr == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </section>

            <section class="space-y-5">
                <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Thông tin liên hệ</h3>

                @guest
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Họ và tên *</label>
                            <input type="text" name="guest_name" value="{{ old('guest_name') }}" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        </div>
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Số điện thoại *</label>
                            <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" required class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Email</label>
                            <input type="email" name="guest_email" value="{{ old('guest_email') }}" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        </div>
                    </div>
                @else
                    <div class="border border-zinc-800 bg-zinc-950 p-5 text-sm font-medium text-zinc-300">
                        Xin chào <span class="font-black text-white">{{ auth()->user()->name }}</span>, thông tin tài khoản của bạn sẽ được gắn với yêu cầu này.
                    </div>
                @endguest
            </section>

            @if($isService)
                <section class="space-y-5">
                    <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Xe của bạn</h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Tên xe của bạn *</label>
                            <input type="text" name="customer_car_model" value="{{ old('customer_car_model') }}" required placeholder="Ví dụ: BMW 320i 2020" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        </div>
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Biển số / Tình trạng</label>
                            <input type="text" name="customer_car_condition" value="{{ old('customer_car_condition') }}" placeholder="Ví dụ: 30A-12345, đã đi 50.000km" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        </div>
                    </div>
                </section>
            @else
                <section class="space-y-5">
                    <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Sản phẩm quan tâm</h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Danh mục *</label>
                            <select id="category_selector" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-xs font-black uppercase tracking-[0.18em] text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                                <option value="">-- Chọn danh mục --</option>
                                <option value="oto">BMW Ô tô</option>
                                <option value="xe_may">BMW Motorrad</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Chọn sản phẩm *</label>
                            <select id="product_selector" name="product_id" required disabled class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors disabled:cursor-not-allowed disabled:opacity-50 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                                <option value="">-- Vui lòng chọn danh mục trước --</option>
                            </select>
                        </div>
                    </div>
                </section>
            @endif

            <section class="space-y-5">
                <h3 class="border-b border-white/10 pb-3 text-[10px] font-black uppercase tracking-[0.26em] text-zinc-500">Thời gian & ghi chú</h3>
                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ngày dự kiến *</label>
                    <input type="text" name="appointment_date" value="{{ old('appointment_date') }}" required class="flatpickr-input w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]" placeholder="Chọn ngày và giờ">
                </div>

                <div>
                    <label class="mb-2 block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Ghi chú thêm</label>
                    <textarea name="notes" rows="4" class="w-full border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-white transition-colors focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">{{ old('notes') }}</textarea>
                </div>
            </section>

            <div class="border-t border-white/10 pt-8">
                <button type="submit" class="w-full bg-[#1C69D4] px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                    Xác nhận gửi yêu cầu
                </button>
            </div>
        </form>

        <script>
            function updateFormHeader(value) {
                const title = document.getElementById('form-title');
                const subtitle = document.getElementById('form-subtitle');

                const serviceTypes = ['maintenance', 'detailing', 'car_wash'];
                const isService = serviceTypes.includes(value);

                subtitle.innerText = isService ? 'Chăm sóc chuyên nghiệp' : 'Trải nghiệm showroom';

                const labels = {
                    'test_drive': 'Lái thử xe',
                    'viewing': 'Xem xe trực tiếp',
                    'maintenance': 'Bảo dưỡng định kỳ',
                    'detailing': 'Chăm sóc xe chuyên sâu',
                    'car_wash': 'Rửa xe Premium',
                    'quote': 'Yêu cầu báo giá',
                    'consult': 'Tư vấn trực tiếp',
                    'advisor_meeting': 'Gặp gỡ cố vấn'
                };

                title.innerText = labels[value] || 'Đăng ký dịch vụ';
            }

            document.addEventListener('DOMContentLoaded', function() {
                const typeSelect = document.getElementById('type-select');
                if (typeSelect) {
                    updateFormHeader(typeSelect.value);
                }

                const categorySelector = document.getElementById('category_selector');
                const productSelector = document.getElementById('product_selector');

                if (!categorySelector || !productSelector) {
                    return;
                }

                categorySelector.addEventListener('change', async function() {
                    const type = this.value;

                    if (!type) {
                        productSelector.innerHTML = '<option value="">-- Vui lòng chọn danh mục trước --</option>';
                        productSelector.disabled = true;
                        return;
                    }

                    productSelector.innerHTML = '<option value="">Đang tải sản phẩm...</option>';
                    productSelector.disabled = true;

                    try {
                        const response = await fetch(`{{ route('api.products.category') }}?category_type=${encodeURIComponent(type)}`);
                        const data = await response.json();

                        productSelector.innerHTML = '<option value="">-- Chọn sản phẩm --</option>';
                        data.products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.name;
                            productSelector.appendChild(option);
                        });

                        productSelector.disabled = false;
                    } catch (error) {
                        console.error('Error fetching products:', error);
                        productSelector.innerHTML = '<option value="">Lỗi khi tải dữ liệu</option>';
                    }
                });
            });
        </script>
    </x-public.form-shell>
</x-app-layout>
