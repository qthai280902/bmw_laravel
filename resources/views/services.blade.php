<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em]">
            Dịch vụ <span class="text-zinc-500">BMW Excellence</span>
        </h2>
    </x-slot>

    <!-- Services Hero Secion -->
    <div class="relative h-[60vh] overflow-hidden bg-black">
        <img src="{{ asset('images/cars/hero.png') }}" class="absolute inset-0 w-full h-full object-cover opacity-50 grayscale hover:grayscale-0 transition-all duration-1000 scale-105" alt="BMW Service">
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center max-w-3xl px-6">
                <h1 class="text-5xl md:text-7xl font-light text-white uppercase tracking-[0.2em] mb-6 leading-none">
                    Chăm sóc <br> <span class="font-black">Đẳng cấp</span>
                </h1>
                <p class="text-zinc-400 font-medium tracking-widest text-xs md:text-sm uppercase leading-relaxed">
                    Đưa chiếc BMW của bạn về trạng thái hoàn mỹ nhất với các dịch vụ bảo trì và chăm sóc chuyên dụng từ đội ngũ chuyên gia.
                </p>
            </div>
        </div>
    </div>

    <!-- Service Sections -->
    <div class="bg-zinc-950 py-24 space-y-32">
        <!-- 1. Chăm sóc xe (Detailing/Ceramic) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Precision</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Chăm sóc xe chuyên sâu</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Dịch vụ Detailing và Ceramic cao cấp giúp bảo vệ lớp sơn nguyên bản và tạo độ bóng gương hoàn hảo. Chúng tôi sử dụng các sản phẩm chăm sóc từ những thương hiệu hàng đầu thế giới được BMW khuyên dùng.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'viewing']) }}" class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300">
                        Đặt lịch Detailing
                    </a>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/sedan.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Detailing">
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Rửa xe cao cấp (Premium Wash) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/suv.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Premium Wash">
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Purity</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Rửa xe Premium</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Không chỉ là làm sạch, quy trình rửa xe 24 bước của chúng tôi loại bỏ hoàn toàn các tác nhân gây hại từ môi trường, bảo vệ từng chi tiết ngoại thất và nội thất bằng các dụng cụ chuyên dụng không gây trầy xước.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'viewing']) }}" class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300">
                        Đặt lịch Rửa xe
                    </a>
                </div>
            </div>
        </section>

        <!-- 3. Bảo dưỡng định kỳ (Maintenance) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Reliability</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Bảo dưỡng định kỳ</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Hệ thống nhắc hẹn bảo dưỡng thông minh giúp xe của bạn luôn ở trạng thái vận hành tối ưu. Đội ngũ kỹ thuật viên được đào tạo theo tiêu chuẩn BMW toàn cầu sử dụng phụ tùng chính hãng 100%.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'maintenance']) }}" class="inline-block px-12 py-5 bg-[#1C69D4] text-white text-[10px] font-black uppercase tracking-[0.3em] hover:bg-[#165bb0] transition-all duration-300 shadow-xl shadow-[#1C69D4]/20">
                        Đặt lịch Bảo dưỡng
                    </a>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/superbike.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Maintenance">
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Contact & Location -->
    <div class="bg-black py-24 border-t border-zinc-900">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h3 class="text-white font-black text-xl uppercase tracking-widest mb-8">BMW Service Center</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-zinc-500 text-xs font-black uppercase tracking-[0.2em]">
                <div>
                    <p class="mb-2 text-white">Địa chỉ</p>
                    <p>800 BMW Way, District 7, HCMC</p>
                </div>
                <div>
                    <p class="mb-2 text-white">Hotline Dịch vụ</p>
                    <p>1800 8080 (24/7)</p>
                </div>
                <div>
                    <p class="mb-2 text-white">Giờ làm việc</p>
                    <p>Mon - Sat: 08:00 - 18:00</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
