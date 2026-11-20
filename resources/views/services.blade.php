<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-white leading-tight tracking-tight">
            Dịch vụ <span class="text-zinc-400">BMW Excellence</span>
        </h2>
    </x-slot>

    <!-- Services Hero Section -->
    <div class="relative h-[58vh] overflow-hidden bg-[#050505] border-b border-white/10">
        <img src="{{ asset('images/cars/hero.png') }}" class="absolute inset-0 h-full w-full object-cover opacity-45 grayscale transition-all duration-1000 hover:grayscale-0" alt="BMW Service Center">
        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/60 to-transparent"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="max-w-3xl px-6 text-center">
                <p class="mb-4 text-[10px] font-black uppercase tracking-[0.35em] text-[#70A7FF]">BMW Aftersales & Care Concierge</p>
                <h1 class="mb-6 text-4xl font-light leading-tight tracking-tight text-white md:text-6xl">
                    Dịch vụ & Chăm sóc xe <br> <span class="font-extrabold">Chuẩn Chính Hãng</span>
                </h1>
                <p class="text-sm font-normal leading-relaxed text-zinc-300 md:text-base">
                    Đưa chiếc BMW của Quý khách về trạng thái hoàn mỹ nhất với quy trình kỹ thuật ISTA toàn cầu, phụ tùng chính hãng 100% và vật liệu chăm sóc độc quyền từ Đức.
                </p>
            </div>
        </div>
    </div>

    @php
        $servicePackages = [
            [
                'badge' => 'RELIABILITY · CBS STANDARD',
                'title' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)',
                'duration' => '90 — 120 phút',
                'price' => 'Từ 3.800.000 VNĐ',
                'image' => asset('images/cars/superbike.png'),
                'description' => 'Quy trình bảo dưỡng theo tiêu chuẩn Condition Based Service (CBS) giúp động cơ TwinPower Turbo và hệ truyền động vận hành mượt mà, giữ trọn bảo hành chính hãng.',
                'items' => [
                    'Thay dầu động cơ BMW TwinPower Turbo chính hãng & lọc dầu tiêu chuẩn Đức',
                    'Kiểm tra & vệ sinh hệ thống phanh, má phanh, đĩa phanh và dung dịch dầu phanh',
                    'Kiểm tra hệ thống treo, thước lái, áp suất lốp Run-flat và cân chỉnh độ chụm',
                    'Cập nhật nhật ký bảo dưỡng điện tử trực tiếp lên màn hình iDrive của xe',
                ],
            ],
            [
                'badge' => 'PRECISION · CERAMIC 9H+',
                'title' => 'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)',
                'duration' => '1 — 2 ngày làm việc',
                'price' => 'Từ 14.500.000 VNĐ',
                'image' => asset('images/cars/sedan.png'),
                'description' => 'Hiệu chỉnh bề mặt sơn đa bước xóa vết xước xoáy và phủ lớp gốm Ceramic tinh thể kép, tạo hiệu ứng lá sen chống bám nước và độ bóng gương sâu đặc trưng BMW.',
                'items' => [
                    'Rửa xe chi tiết 24 bước, tẩy bụi sơn, nhựa đường và mạt sắt chuyên sâu',
                    'Hiệu chỉnh bề mặt sơn 3 bước bằng máy đánh bóng quỹ đạo Rupes không gây hologram',
                    'Phủ 2 lớp Ceramic 9H+ bảo vệ sơn khỏi tia UV, mưa axit và quá trình oxy hóa',
                    'Phủ nano chống bám nước kính lái và dưỡng bóng hệ thống viền Shadowline',
                ],
            ],
            [
                'badge' => 'PURITY · INTERIOR & ENGINE BAY',
                'title' => 'Chăm sóc nội thất da Merino & Vệ sinh khoang máy chuyên sâu',
                'duration' => '4 — 6 giờ',
                'price' => 'Từ 4.900.000 VNĐ',
                'image' => asset('images/cars/suv.png'),
                'description' => 'Phục hồi độ mềm mịn tự nhiên cho ghế da Merino/Vernasca cao cấp, khử khuẩn hệ thống điều hòa và làm sạch khoang động cơ bằng hơi nước nóng an toàn tuyệt đối.',
                'items' => [
                    'Làm sạch & dưỡng ẩm chuyên sâu toàn bộ ghế da Merino, Vernasca, Sensafin và Alcantara',
                    'Khử trùng nội thất bằng hơi nước nóng, sục khí Ozone và vệ sinh dàn lạnh điều hòa',
                    'Vệ sinh khoang động cơ không dùng nước áp lực cao, phủ dung dịch chống lão hóa cao su & dây điện',
                    'Đánh bóng chi tiết ốp gỗ quý Fine-wood, sợi Carbon M và cụm điều khiển pha lê CraftedClarity',
                ],
            ],
            [
                'badge' => 'INTELLIGENCE · BMW ISTA',
                'title' => 'Kiểm tra tổng quát & Chẩn đoán điện tử ISTA chính hãng',
                'duration' => '60 — 90 phút',
                'price' => 'Từ 1.500.000 VNĐ',
                'image' => asset('images/cars/hero.png'),
                'description' => 'Quét toàn bộ hộp điều khiển ECU bằng hệ thống BMW ISTA kết nối máy chủ Munich, đánh giá toàn diện 50 hạng mục kỹ thuật trước các chuyến hành trình dài.',
                'items' => [
                    'Chẩn đoán toàn bộ mô-đun điện tử ECU, cảm biến ADAS và hệ thống Driving Assistant bằng BMW ISTA',
                    'Kiểm tra sức khỏe ắc quy AGM / pin cao áp BMW i (State of Health) và hệ thống sạc',
                    'Đo kiểm hệ thống làm mát, két nước, đường ống tăng áp và rò rỉ gầm xe trên cầu nâng',
                    'Cấp biên bản đánh giá kỹ thuật 50 hạng mục chính hãng từ Cố vấn dịch vụ BMW',
                ],
            ],
        ];
    @endphp

    <!-- 4 Official Service Packages -->
    <div class="bg-[#050505] py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="flex flex-col justify-between gap-4 border-b border-white/10 pb-8 md:flex-row md:items-end">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#70A7FF]">BMW Service Packages</p>
                    <h2 class="mt-2 text-2xl font-extrabold uppercase tracking-tight text-white md:text-4xl">
                        Danh Mục Gói Chăm Sóc Xe Chính Hãng
                    </h2>
                </div>
                <p class="max-w-md text-xs leading-relaxed text-zinc-400">
                    Chọn gói chăm sóc phù hợp để hệ thống tự động điền thông tin vào biểu mẫu đặt lịch hẹn với Cố vấn dịch vụ BMW.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                @foreach($servicePackages as $package)
                    <article class="flex flex-col justify-between overflow-hidden rounded-2xl border border-white/10 bg-[#0B0F17] transition-colors hover:border-[#1C69D4]/60">
                        <div>
                            <div class="relative aspect-[16/9] overflow-hidden border-b border-white/10 bg-black">
                                <img src="{{ $package['image'] }}" alt="{{ $package['title'] }}" class="h-full w-full object-cover opacity-75 grayscale transition-all duration-700 hover:scale-105 hover:grayscale-0">
                                <div class="absolute inset-0 bg-gradient-to-t from-[#0B0F17] via-transparent to-black/30"></div>
                                <div class="absolute left-5 top-5">
                                    <span class="inline-block rounded-full border border-[#1C69D4]/40 bg-[#050505]/90 px-3.5 py-1 text-[9px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">
                                        {{ $package['badge'] }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-7 sm:p-8">
                                <h3 class="text-lg font-extrabold leading-snug text-white sm:text-xl">
                                    {{ $package['title'] }}
                                </h3>
                                <p class="mt-3 text-xs leading-relaxed text-zinc-400">
                                    {{ $package['description'] }}
                                </p>

                                <div class="mt-5 grid grid-cols-2 gap-px border border-white/10 bg-white/10">
                                    <div class="bg-[#050505] px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-500">Thời gian dự kiến</p>
                                        <p class="mt-1 text-xs font-bold text-white">{{ $package['duration'] }}</p>
                                    </div>
                                    <div class="bg-[#050505] px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-500">Chi phí tham khảo</p>
                                        <p class="mt-1 text-xs font-black text-[#70A7FF]">{{ $package['price'] }}</p>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <p class="mb-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-400">Hạng mục kỹ thuật thực hiện</p>
                                    <ul class="bmw-checklist">
                                        @foreach($package['items'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-white/10 bg-[#050505] px-7 py-5 sm:px-8">
                            <a
                                href="{{ route('appointments.create', ['type' => 'service', 'service_package' => $package['title']]) }}"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-[#1C69D4] px-6 py-4 text-[11px] font-black uppercase tracking-[0.24em] text-white transition-colors hover:bg-white hover:text-black"
                            >
                                ĐẶT LỊCH CHĂM SÓC XE
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Contact & Location -->
    <div class="border-t border-zinc-900 bg-black py-20">
        <div class="mx-auto max-w-7xl px-4 text-center">
            <h3 class="mb-8 text-xl font-bold tracking-tight text-white">BMW Service Center</h3>
            <div class="grid grid-cols-1 gap-10 text-xs font-medium text-zinc-400 md:grid-cols-3">
                <div>
                    <p class="mb-2 font-bold uppercase tracking-wider text-white">Địa chỉ</p>
                    <p class="leading-relaxed">800 BMW Way, Quận 7, TP. HCM</p>
                </div>
                <div>
                    <p class="mb-2 font-bold uppercase tracking-wider text-white">Hotline Dịch vụ</p>
                    <p class="leading-relaxed">1800 8080 (24/7)</p>
                </div>
                <div>
                    <p class="mb-2 font-bold uppercase tracking-wider text-white">Giờ làm việc</p>
                    <p class="leading-relaxed">Thứ Hai — Thứ Bảy: 08:00 - 18:00</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
