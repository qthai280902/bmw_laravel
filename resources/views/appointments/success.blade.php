<x-app-layout>
    <div class="h-[80vh] flex items-center justify-center bg-zinc-950 border-t border-zinc-900">
        <div class="text-center px-4 max-w-2xl mx-auto">
            <div class="w-20 h-20 bg-accent rounded-full flex items-center justify-center mx-auto mb-8 shadow-[0_0_40px_rgba(28,105,212,0.3)]">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <h2 class="text-xs font-black uppercase tracking-[0.4em] text-zinc-500 mb-4">Ghi nhận thành công</h2>
            <h1 class="text-4xl md:text-5xl font-light uppercase tracking-tighter text-white mb-8">
                TRẢI NGHIỆM ĐẲNG CẤP ĐANG CHỜ ĐÓN BẠN
            </h1>
            
            <p class="text-zinc-400 leading-relaxed max-w-lg mx-auto mb-12">
                Yêu cầu của quý khách đã được chuyển đến chuyên viên tư vấn BMW. Chúng tôi sẽ liên hệ trong thời gian sớm nhất để xác nhận và sắp xếp lịch trình tối ưu cho quý khách.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('home') }}" class="px-8 py-4 bg-white text-black text-xs font-black uppercase tracking-widest hover:bg-zinc-200 transition-colors">
                    Trở về Trang chủ
                </a>
                <a href="{{ route('products.index') }}" class="px-8 py-4 border border-zinc-800 text-white text-xs font-black uppercase tracking-widest hover:border-zinc-600 transition-colors">
                    Khám phá thêm
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
