<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em]">
            Trải nghiệm <span class="text-zinc-500">BMW Showroom</span>
        </h2>
    </x-slot>

    <!-- Experiences Hero Section -->
    <div class="relative h-[60vh] overflow-hidden bg-black">
        <img src="{{ asset('images/cars/hero.png') }}" class="absolute inset-0 w-full h-full object-cover opacity-50 grayscale hover:grayscale-0 transition-all duration-1000 scale-105" alt="BMW Experience">
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center max-w-3xl px-6">
                <h1 class="text-5xl md:text-7xl font-light text-white uppercase tracking-[0.2em] mb-6 leading-none">
                    Khát khao <br> <span class="font-black">Bứt phá</span>
                </h1>
                <p class="text-zinc-400 font-medium tracking-widest text-xs md:text-sm uppercase leading-relaxed">
                    Khám phá thế giới BMW qua những trải nghiệm thực tế đầy phấn khích. Từ việc lái thử các dòng xe mới nhất đến nhận tư vấn chuyên sâu từ chuyên gia.
                </p>
            </div>
        </div>
    </div>

    <!-- Experience Sections -->
    <div class="bg-zinc-950 py-24 space-y-32">
        <!-- 1. Lái thử xe (Test Drive) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Adrenaline</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Đăng ký lái thử</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Ngồi sau tay lái và trực tiếp cảm nhận sức mạnh, sự linh hoạt và công nghệ đỉnh cao của các dòng xe BMW M, BMW i hay BMW X. Khơi dậy mọi giác quan trên những cung đường đầy phấn khích.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'test_drive']) }}" class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300">
                        Đặt lịch lái thử
                    </a>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/sedan.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Test Drive">
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Xem xe trực tiếp (Viewing) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/suv.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Viewing">
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Elegance</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Xem xe trực tiếp</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Tận mắt chiêm ngưỡng những tuyệt tác cơ khí tại không gian trưng bày sang trọng. Tìm hiểu kỹ hơn về các gói trang bị cá nhân hóa và các giải pháp nội thất tinh tế từ chuyên gia BMW.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'viewing']) }}" class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300">
                        Hẹn lịch xem xe
                    </a>
                </div>
            </div>
        </section>

        <!-- 3. Nhận báo giá (Quote) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Decision</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Yêu cầu báo giá</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Nhận báo giá chi tiết và các chương trình ưu đãi độc quyền dành riêng cho từng dòng xe. Chúng tôi cung cấp các giải pháp tài chính linh hoạt giúp bạn sớm sở hữu chiếc BMW mơ ước.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'quote']) }}" class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300">
                        Nhận báo giá ngay
                    </a>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/hero.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Quote">
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Tư vấn trực tiếp (Consult) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="aspect-video bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <img src="{{ asset('images/cars/superbike.png') }}" class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" alt="Consult">
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Expertise</div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-6">Tư vấn trực tiếp</h2>
                    <p class="text-zinc-400 text-sm leading-8 mb-10 tracking-widest">
                        Kết nối trực tiếp với các cố vấn chuyên nghiệp của BMW. Chúng tôi sẵn sàng giải đáp mọi thắc mắc về tính năng xe, công nghệ mới nhất và các đặc quyền dành cho chủ sở hữu BMW Excellence.
                    </p>
                    <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="inline-block px-12 py-5 bg-[#1C69D4] text-white text-[10px] font-black uppercase tracking-[0.3em] hover:bg-[#165bb0] transition-all duration-300 shadow-xl shadow-[#1C69D4]/20">
                        Kết nối ngay
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Contact & Location Footer -->
    <div class="bg-black py-24 border-t border-zinc-900">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h3 class="text-white font-black text-xl uppercase tracking-widest mb-8">BMW Excellence Showroom</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-zinc-500 text-xs font-black uppercase tracking-[0.2em]">
                <div>
                    <p class="mb-2 text-white">Địa chỉ Shop</p>
                    <p>800 BMW Way, District 7, HCMC</p>
                </div>
                <div>
                    <p class="mb-2 text-white">Hotline Trải nghiệm</p>
                    <p>1800 8080 (Showroom)</p>
                </div>
                <div>
                    <p class="mb-2 text-white">Giờ đón khách</p>
                    <p>Mon - Sun: 09:00 - 21:00</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
