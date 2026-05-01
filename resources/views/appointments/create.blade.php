<x-app-layout>
    <div class="py-24 bg-zinc-950 min-h-screen border-t border-zinc-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-black border border-zinc-800 p-8 md:p-12 shadow-2xl">
                @php
                    $currentTypeStr = old('type', request('type'));
                    $requestedType = \App\Enums\AppointmentType::tryFrom($currentTypeStr);
                    $serviceTypes = [
                        \App\Enums\AppointmentType::Maintenance->value,
                        \App\Enums\AppointmentType::Detailing->value,
                        \App\Enums\AppointmentType::CarWash->value
                    ];
                    $isService = in_array($currentTypeStr, $serviceTypes);
                @endphp

                <div class="text-center mb-12">
                    <h2 id="form-subtitle" class="text-xs font-black uppercase tracking-[0.4em] text-accent mb-4">Trải nghiệm khác biệt</h2>
                    <h1 id="form-title" class="text-3xl md:text-4xl font-light uppercase tracking-tighter text-white">
                        Đăng ký dịch vụ
                    </h1>
                </div>

                @if ($errors->any())
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
                    
                    <!-- Loại yêu cầu -->
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Bạn cần hỗ trợ gì?</h3>
                        <div>
                            <select id="type-select" name="type" required class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors uppercase text-xs font-black tracking-widest" onchange="updateFormHeader(this.value)">
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
                        </div>
                    </div>

                    <script>
                        function updateFormHeader(value) {
                            const title = document.getElementById('form-title');
                            const subtitle = document.getElementById('form-subtitle');
                            
                            const serviceTypes = ['maintenance', 'detailing', 'car_wash'];
                            const isService = serviceTypes.includes(value);

                            subtitle.innerText = isService ? 'Chăm sóc chuyên nghiệp' : 'Trải nghiệm khác biệt';
                            
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

                        // Initialize on load
                        document.addEventListener('DOMContentLoaded', function() {
                            const select = document.getElementById('type-select');
                            if(select) updateFormHeader(select.value);
                        });
                    </script>

                    <!-- Thông tin khách hàng -->
                    <div class="space-y-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Thông tin liên hệ</h3>
                        
                        @guest
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Họ và tên *</label>
                                    <input type="text" name="guest_name" value="{{ old('guest_name') }}" required 
                                        class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Số điện thoại *</label>
                                    <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" required 
                                        class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Email (Tùy chọn)</label>
                                    <input type="email" name="guest_email" value="{{ old('guest_email') }}" 
                                        class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                                </div>
                            </div>
                        @else
                            <div class="p-4 bg-zinc-900/50 border border-zinc-800 text-zinc-300 text-sm">
                                Xin chào <span class="font-bold text-white">{{ auth()->user()->name }}</span>, thông tin của bạn đã được ghi nhận vào yêu cầu này.
                            </div>
                        @endguest
                    </div>

                    <!-- Thông tin xe -->
                    <div class="space-y-6 pt-4">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-500 border-b border-zinc-800 pb-2">Dòng xe quan tâm</h3>
                        
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Chọn mẫu xe *</label>
                            <select name="product_id" required class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                                <option value="">-- Vui lòng chọn xe --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id || old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Ngày dự kiến *</label>
                            <input type="text" name="appointment_date" value="{{ old('appointment_date') }}" required 
                                class="flatpickr-input w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors"
                                placeholder="Chọn ngày và giờ">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Ghi chú thêm</label>
                            <textarea name="notes" rows="4" 
                                class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="pt-8">
                        <button type="submit" class="w-full bg-accent text-white py-5 text-sm font-black uppercase tracking-[0.2em] hover:bg-white hover:text-black transition-all">
                            Xác nhận Gửi yêu cầu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
