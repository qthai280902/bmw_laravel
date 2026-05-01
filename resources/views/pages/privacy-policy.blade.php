<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em]">
            Chính sách <span class="text-zinc-500">Bảo mật</span>
        </h2>
    </x-slot>

    <div class="bg-zinc-950 py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <article class="prose prose-invert prose-zinc max-w-none space-y-12">

                <section>
                    <h2 class="text-xl font-black text-white uppercase tracking-widest mb-4">1. Thu thập thông tin</h2>
                    <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                        BMW Showroom thu thập các thông tin cá nhân cần thiết khi bạn đặt lịch hẹn, đăng ký tài khoản hoặc liên hệ tư vấn. Các thông tin này bao gồm: Họ tên, số điện thoại, địa chỉ email và mẫu xe quan tâm. Chúng tôi cam kết chỉ thu thập những thông tin tối thiểu cần thiết để phục vụ bạn.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-black text-white uppercase tracking-widest mb-4">2. Sử dụng thông tin</h2>
                    <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                        Thông tin thu thập được sử dụng cho mục đích: Xác nhận lịch hẹn, liên hệ tư vấn, gửi thông tin ưu đãi (nếu được đồng ý), và cải thiện trải nghiệm dịch vụ. Chúng tôi không bán hoặc chia sẻ thông tin cá nhân của bạn cho bên thứ ba vì mục đích thương mại.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-black text-white uppercase tracking-widest mb-4">3. Bảo vệ thông tin</h2>
                    <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                        BMW Showroom áp dụng các biện pháp bảo mật tiêu chuẩn ngành bao gồm: Mã hóa SSL/TLS, lưu trữ mật khẩu bằng thuật toán hash an toàn (bcrypt), kiểm soát truy cập theo vai trò (RBAC), và sao lưu dữ liệu định kỳ. Hệ thống được giám sát liên tục để phát hiện và ngăn chặn các hoạt động trái phép.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-black text-white uppercase tracking-widest mb-4">4. Quyền của bạn</h2>
                    <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                        Bạn có quyền: Truy cập, chỉnh sửa hoặc xóa thông tin cá nhân bất cứ lúc nào thông qua trang Hồ sơ cá nhân. Bạn cũng có quyền từ chối nhận thông tin tiếp thị và yêu cầu xuất dữ liệu cá nhân theo quy định của pháp luật Việt Nam về bảo vệ dữ liệu cá nhân.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-black text-white uppercase tracking-widest mb-4">5. Liên hệ</h2>
                    <p class="text-zinc-400 text-sm leading-8 tracking-widest">
                        Mọi thắc mắc về chính sách bảo mật, vui lòng liên hệ:
                        <br>Email: privacy@bmw-showroom.vn
                        <br>Hotline: 1800-BMW-SERIES (24/7)
                    </p>
                </section>

            </article>

            <div class="mt-16 pt-8 border-t border-zinc-800 text-center">
                <p class="text-[10px] font-black text-zinc-600 uppercase tracking-widest">
                    Cập nhật lần cuối: {{ date('d/m/Y') }}
                </p>
            </div>
        </div>
    </div>

</x-app-layout>
