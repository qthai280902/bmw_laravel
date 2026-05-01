<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em]">
            Ưu đãi <span class="text-zinc-500">Độc quyền</span>
        </h2>
    </x-slot>

    <!-- Hero -->
    <div class="relative h-[50vh] overflow-hidden bg-black">
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/80 via-transparent to-zinc-950"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center max-w-3xl px-6">
                <h1 class="text-5xl md:text-7xl font-light text-white uppercase tracking-[0.2em] mb-6 leading-none">
                    BMW <br> <span class="font-black">Excellence VIP</span>
                </h1>
                <p class="text-zinc-400 font-medium tracking-widest text-xs md:text-sm uppercase leading-relaxed">
                    Chương trình ưu đãi đặc biệt dành riêng cho khách hàng thân thiết và chủ sở hữu BMW.
                </p>
            </div>
        </div>
    </div>

    <!-- Offers Content -->
    <div class="bg-zinc-950 py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">

            <!-- Offer 1 -->
            <div class="border border-zinc-800 p-12">
                <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Loyalty Program</div>
                <h2 class="text-2xl font-black text-white uppercase tracking-widest mb-6">Chương trình khách hàng thân thiết</h2>
                <p class="text-zinc-400 text-sm leading-8 tracking-widest mb-8">
                    Tích điểm trên mỗi giao dịch dịch vụ và mua sắm phụ kiện. Đổi điểm lấy các ưu đãi độc quyền bao gồm: Bảo dưỡng miễn phí, nâng cấp phụ kiện, và quyền ưu tiên đặt cọc xe mới.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6 bg-zinc-900/50">
                        <p class="text-3xl font-black text-accent">5%</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mt-2">Hoàn tiền dịch vụ</p>
                    </div>
                    <div class="text-center p-6 bg-zinc-900/50">
                        <p class="text-3xl font-black text-accent">10%</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mt-2">Giảm phụ kiện</p>
                    </div>
                    <div class="text-center p-6 bg-zinc-900/50">
                        <p class="text-3xl font-black text-accent">VIP</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mt-2">Ưu tiên đặt xe</p>
                    </div>
                </div>
            </div>

            <!-- Offer 2 -->
            <div class="border border-zinc-800 p-12">
                <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Trade-In</div>
                <h2 class="text-2xl font-black text-white uppercase tracking-widest mb-6">Đổi xe cũ — Lấy BMW mới</h2>
                <p class="text-zinc-400 text-sm leading-8 tracking-widest mb-8">
                    Chương trình Thu mua xe cũ định giá minh bạch. Giá trị xe cũ được quy đổi trực tiếp vào hợp đồng mua xe BMW mới, kết hợp với các gói tài chính ưu đãi lãi suất 0% trong 12 tháng đầu.
                </p>
                <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="inline-block px-12 py-5 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all duration-300">
                    Liên hệ định giá
                </a>
            </div>

            <!-- Offer 3 -->
            <div class="border border-zinc-800 p-12">
                <div class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-4">Finance</div>
                <h2 class="text-2xl font-black text-white uppercase tracking-widest mb-6">Gói tài chính linh hoạt</h2>
                <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                    BMW Financial Services cung cấp các giải pháp tài chính đa dạng: Trả góp chuẩn, BMW Select (trả góp linh hoạt với giá trị đảm bảo), và Leasing doanh nghiệp. Lãi suất ưu đãi từ 6.99%/năm cho các dòng xe chọn lọc trong tháng này.
                </p>
            </div>

        </div>
    </div>

</x-app-layout>
