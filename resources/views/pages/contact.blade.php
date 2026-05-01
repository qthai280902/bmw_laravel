<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em]">
            Liên hệ <span class="text-zinc-500">BMW Showroom</span>
        </h2>
    </x-slot>

    <div class="bg-zinc-950 py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <!-- Contact Info -->
                <div class="space-y-12">
                    <div>
                        <h2 class="text-3xl font-black text-white uppercase tracking-widest mb-8">
                            Kết nối <span class="text-accent">với chúng tôi</span>
                        </h2>
                        <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                            Đội ngũ chuyên gia BMW luôn sẵn sàng hỗ trợ bạn. Hãy liên hệ để được tư vấn về sản phẩm, dịch vụ, hoặc đặt lịch hẹn trải nghiệm tại showroom.
                        </p>
                    </div>

                    <div class="space-y-8">
                        <div class="border border-zinc-800 p-8">
                            <p class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-2">Showroom Hà Nội</p>
                            <p class="text-white font-black uppercase tracking-widest text-sm">268 Đội Cấn, Ba Đình, Hà Nội</p>
                            <p class="text-zinc-500 text-xs mt-2">Tel: (024) 3771 8888</p>
                        </div>
                        <div class="border border-zinc-800 p-8">
                            <p class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-2">Showroom TP. HCM</p>
                            <p class="text-white font-black uppercase tracking-widest text-sm">800 BMW Way, Quận 7, TP. HCM</p>
                            <p class="text-zinc-500 text-xs mt-2">Tel: (028) 3773 8888</p>
                        </div>
                        <div class="border border-zinc-800 p-8">
                            <p class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-2">Hotline 24/7</p>
                            <p class="text-white font-black uppercase tracking-widest text-sm">1800-BMW-SERIES</p>
                            <p class="text-zinc-500 text-xs mt-2">Email: info@bmw-showroom.vn</p>
                        </div>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="space-y-8">
                    <div class="bg-zinc-900/50 border border-zinc-800 p-12">
                        <h3 class="text-xl font-black text-white uppercase tracking-widest mb-6">Đặt lịch hẹn nhanh</h3>
                        <p class="text-zinc-400 text-sm leading-8 tracking-widest mb-8">
                            Đăng ký lái thử, tư vấn trực tiếp hoặc nhận báo giá chỉ trong vài bước đơn giản.
                        </p>
                        <a href="{{ route('appointments.create') }}" class="inline-block w-full text-center px-12 py-5 bg-accent text-white text-[10px] font-black uppercase tracking-[0.3em] hover:bg-blue-700 transition-all duration-300">
                            Đặt lịch ngay
                        </a>
                    </div>

                    <div class="bg-zinc-900/50 border border-zinc-800 p-12">
                        <h3 class="text-xl font-black text-white uppercase tracking-widest mb-6">Giờ làm việc</h3>
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-500 uppercase tracking-widest text-xs font-black">Thứ 2 - Thứ 6</span>
                                <span class="text-white font-black">08:00 — 18:00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500 uppercase tracking-widest text-xs font-black">Thứ 7</span>
                                <span class="text-white font-black">08:00 — 17:00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500 uppercase tracking-widest text-xs font-black">Chủ nhật</span>
                                <span class="text-white font-black">09:00 — 15:00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
