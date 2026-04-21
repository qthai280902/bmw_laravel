<x-app-layout>
    <!-- Hero Section: Cinematic Showroom -->
    <section class="relative h-[90vh] flex items-center overflow-hidden bg-black">
        <div class="absolute inset-0 z-0">
            <!-- Simulated High-End Photography Background -->
            <div class="absolute inset-0 bg-gradient-to-r from-black via-black/80 to-transparent z-10"></div>
            <img src="https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&q=80&w=2070" 
                 class="w-full h-full object-cover object-center scale-105 animate-pulse-slow" 
                 alt="BMW M Vision">
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
            <div class="max-w-2xl">
                <h2 class="text-xs font-bold uppercase tracking-[0.4em] text-accent mb-6 animate-fade-in-up">
                    The Ultimate Driving Machine
                </h2>
                <h1 class="text-5xl md:text-7xl font-light uppercase leading-[1.1] tracking-tight mb-8 animate-fade-in-up transition-all duration-700">
                    SỰ KIÊU HÃNH <br/> 
                    <span class="font-black italic">DẪN ĐẦU</span>
                </h1>
                <p class="text-zinc-400 text-lg mb-10 max-w-lg leading-relaxed animate-fade-in-up">
                    Khám phá thế hệ xe hiệu năng cao mới nhất từ BMW M. Nơi công nghệ cơ khí đỉnh cao gặp gỡ ngôn ngữ thiết kế của tương lai.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 animate-fade-in-up">
                    <a href="#" class="inline-flex items-center justify-center px-10 py-5 bg-accent text-white text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all group">
                        Đăng ký lái thử
                        <svg class="w-4 h-4 ms-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a href="#" class="inline-flex items-center justify-center px-10 py-5 border border-white text-white text-xs font-black uppercase tracking-widest hover:bg-white hover:text-black transition-all">
                        Xem bảng giá
                    </a>
                </div>
            </div>
        </div>

        <!-- Vertical Text Decorative -->
        <div class="absolute right-12 bottom-12 hidden lg:block rotate-90 origin-right">
            <span class="text-[10px] font-black uppercase tracking-[0.5em] text-zinc-700 select-none">
                BMW VISION NEXT 100 // 2026 EDITION
            </span>
        </div>
    </section>

    <!-- Featured Section: Performance Grid -->
    <section class="py-24 bg-white text-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-16">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-zinc-400 mb-4">Danh mục tiêu điểm</h2>
                    <h3 class="text-4xl font-black uppercase tracking-tighter">Mẫu Xe <span class="text-accent underline">Nổi Bật</span></h3>
                </div>
                <a href="#" class="text-xs font-black uppercase tracking-widest border-b-2 border-black pb-1 hover:text-accent hover:border-accent transition-all">
                    Xem tất cả mẫu xe
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Vehicle Card 1 -->
                <div class="group border border-zinc-100 hover:border-black transition-all duration-500">
                    <div class="aspect-[16/10] overflow-hidden bg-zinc-100">
                        <img src="https://images.unsplash.com/photo-1603584173870-7f37feb4ecad?auto=format&fit=crop&q=80&w=1000" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" 
                             alt="BMW M5">
                    </div>
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="text-2xl font-black uppercase tracking-tighter">BMW M5 Competition</h4>
                                <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Sedan Hiệu Năng Cao</p>
                            </div>
                        </div>
                        <div class="space-y-3 mb-8">
                            <div class="flex justify-between text-sm border-b border-zinc-100 pb-2">
                                <span class="text-zinc-500">Động cơ</span>
                                <span class="font-bold uppercase">V8 M TwinPower</span>
                            </div>
                            <div class="flex justify-between text-sm border-b border-zinc-100 pb-2">
                                <span class="text-zinc-500">Công suất</span>
                                <span class="font-bold uppercase">625 HP / 750 NM</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-black tracking-tighter">5.990.000.000 VNĐ</span>
                            <button class="w-10 h-10 bg-black text-white flex items-center justify-center hover:bg-accent transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Card 2 -->
                <div class="group border border-zinc-100 hover:border-black transition-all duration-500">
                    <div class="aspect-[16/10] overflow-hidden bg-zinc-100">
                        <img src="https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&q=80&w=1000" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" 
                             alt="BMW S1000RR">
                    </div>
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="text-2xl font-black uppercase tracking-tighter">BMW S1000RR</h4>
                                <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Superbike</p>
                            </div>
                        </div>
                        <div class="space-y-3 mb-8">
                            <div class="flex justify-between text-sm border-b border-zinc-100 pb-2">
                                <span class="text-zinc-500">Động cơ</span>
                                <span class="font-bold uppercase">999cc 4-Cylinder</span>
                            </div>
                            <div class="flex justify-between text-sm border-b border-zinc-100 pb-2">
                                <span class="text-zinc-500">Tốc độ tối đa</span>
                                <span class="font-bold uppercase">> 299 KM/H</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-black tracking-tighter">1.090.000.000 VNĐ</span>
                            <button class="w-10 h-10 bg-black text-white flex items-center justify-center hover:bg-accent transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Card 3 -->
                <div class="group border border-zinc-100 hover:border-black transition-all duration-500">
                    <div class="aspect-[16/10] overflow-hidden bg-zinc-100">
                        <img src="https://images.unsplash.com/photo-1635832793132-ce0566ff7328?auto=format&fit=crop&q=80&w=1000" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" 
                             alt="BMW i7">
                    </div>
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="text-2xl font-black uppercase tracking-tighter">BMW i7 xDrive60</h4>
                                <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest">Luxary Electric Sedan</p>
                            </div>
                        </div>
                        <div class="space-y-3 mb-8">
                            <div class="flex justify-between text-sm border-b border-zinc-100 pb-2">
                                <span class="text-zinc-500">Pin</span>
                                <span class="font-bold uppercase">101.7 kWh (WLTP)</span>
                            </div>
                            <div class="flex justify-between text-sm border-b border-zinc-100 pb-2">
                                <span class="text-zinc-500">Quãng đường</span>
                                <span class="font-bold uppercase">625 KM / SẠC</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-black tracking-tighter">7.199.000.000 VNĐ</span>
                            <button class="w-10 h-10 bg-black text-white flex items-center justify-center hover:bg-accent transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action: Showroom Contact -->
    <section class="py-24 bg-zinc-950 text-white border-t border-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-xs font-bold uppercase tracking-[0.4em] text-accent mb-8">Trải nghiệm trực tiếp</h2>
            <h3 class="text-5xl md:text-6xl font-light uppercase tracking-tighter mb-12 leading-none">
                SHOWROOM <span class="font-black italic text-zinc-800">KỸ THUẬT SỐ</span>
            </h3>
            <p class="text-zinc-500 max-w-2xl mx-auto mb-16 leading-relaxed">
                BMW giới thiệu không gian trải nghiệm mua sắm hoàn toàn mới. Nơi bạn có thể tùy biến mẫu xe mơ ước và thực hiện thanh toán cọc trực tuyến chỉ trong vài phút.
            </p>
            <div class="inline-grid grid-cols-1 sm:grid-cols-2 gap-px bg-zinc-900 border border-zinc-900">
                <div class="bg-zinc-950 p-12 text-center group cursor-pointer hover:bg-zinc-900 transition-all">
                    <h4 class="font-black uppercase tracking-widest mb-4">Gặp gỡ cố vấn</h4>
                    <span class="text-xs text-accent uppercase font-bold group-hover:underline">Đặt lịch hẹn &rarr;</span>
                </div>
                <div class="bg-zinc-950 p-12 text-center group cursor-pointer hover:bg-zinc-900 transition-all">
                    <h4 class="font-black uppercase tracking-widest mb-4">Ưu đãi độc quyền</h4>
                    <span class="text-xs text-accent uppercase font-bold group-hover:underline">Xem chính sách &rarr;</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Animations Layer -->
    <style>
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.8s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }
        .animate-pulse-slow {
            animation: pulse-slow 10s infinite;
        }
        @keyframes pulse-slow {
            0%, 100% { transform: scale(1.05); }
            50% { transform: scale(1.1); }
        }
    </style>
</x-app-layout>
