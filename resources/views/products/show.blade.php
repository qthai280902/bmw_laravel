<x-app-layout>
    @section('title', $vehicle->name . ' - BMW Showroom')

    @php
        $imageSet = $vehicle->detailImageSet();
        $mediaUrls = $imageSet['gallery'];
        $heroImageUrl = $imageSet['hero'];
        $designImageUrl = $imageSet['design'];
        $technologyImageUrl = $imageSet['technology'];
        $lifestyleImageUrl = $imageSet['lifestyle'];
        $galleryImageUrls = $mediaUrls->take(8)->values();
        $secondaryGalleryUrls = $galleryImageUrls
            ->skip(1)
            ->reject(fn (string $url): bool => $url === $lifestyleImageUrl)
            ->take(6)
            ->values();

        $formatSpecValue = function ($value): string {
            if (is_array($value)) {
                return collect($value)->flatten()->filter(fn ($item) => filled($item))->implode(', ');
            }

            return filled($value) ? (string) $value : 'Liên hệ showroom';
        };

        $translatedSpecs = collect($vehicle->translated_specs ?? [])
            ->filter(fn ($value) => is_array($value) ? count($value) > 0 : filled($value));

        $highlightSpecs = $translatedSpecs
            ->take(6)
            ->map(fn ($value, $label) => [
                'label' => $label,
                'value' => $formatSpecValue($value),
            ])
            ->values();

        $isAccessory = $vehicle->canOrderAccessory();
        $categoryName = $vehicle->category?->name ?? 'BMW Showroom';
        $primaryCtaLabel = $isAccessory ? 'Đặt hàng ngay' : 'Đăng ký lái thử';
        $secondaryCtaLabel = $isAccessory ? 'Liên hệ tư vấn' : 'Nhận báo giá';
        $primaryCtaUrl = $isAccessory
            ? route('accessory-orders.create', $vehicle->slug)
            : route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'test_drive']);
        $secondaryCtaUrl = $isAccessory
            ? route('contact.index')
            : route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'quote']);
        $quoteCtaUrl = $isAccessory
            ? route('contact.index')
            : route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'quote']);
        $heroCtaGridClass = $isAccessory ? 'sm:grid-cols-2' : 'sm:grid-cols-3';
        $sectionLabel = $isAccessory ? 'Accessory detail' : 'Vehicle detail';
        $overviewTitle = $isAccessory
            ? 'Phụ kiện chính hãng được tư vấn theo đúng dòng xe.'
            : 'Một cấu hình BMW được đặt ở trung tâm trải nghiệm showroom.';
        $designTitle = $isAccessory ? 'Thiết kế, vật liệu và độ hoàn thiện' : 'Tỷ lệ thiết kế và dấu ấn vận hành';
        $technologyTitle = $isAccessory ? 'Tương thích, lắp đặt và trải nghiệm sử dụng' : 'Công nghệ, tiện nghi và cảm giác lái';
        $technicalTitle = $isAccessory ? 'Thông tin sản phẩm' : 'Thông số kỹ thuật';
        $servicesTitle = $isAccessory ? 'Tư vấn giá, tương thích và lắp đặt' : 'Chi phí sở hữu và dịch vụ showroom';
        $bookingTitle = $isAccessory ? 'Sẵn sàng nhận tư vấn phụ kiện?' : 'Sẵn sàng trải nghiệm ' . $vehicle->name . '?';
        $relatedTitle = $isAccessory ? 'Phụ kiện liên quan' : 'Cùng phân khúc';
        $relatedIndexUrl = $isAccessory
            ? route('accessories.index', ['category_id' => $vehicle->category_id])
            : route('products.index', ['category_id' => $vehicle->category_id]);
    @endphp

    <div class="bg-zinc-950 text-white" x-data="{ showDetailedSpecs: false }">
        <section class="relative min-h-[calc(100vh-5rem)] overflow-hidden">
            <img
                src="{{ $heroImageUrl }}"
                alt="{{ $vehicle->name }}"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/70 to-zinc-950/10"></div>
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-zinc-950 to-transparent"></div>

            <div class="relative z-10 flex min-h-[calc(100vh-5rem)] items-end">
                <div class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-24">
                    <div class="max-w-5xl">
                        <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $sectionLabel }}</p>
                        <h1 class="text-5xl font-black uppercase leading-none tracking-normal text-white sm:text-7xl lg:text-8xl">
                            {{ $vehicle->name }}
                        </h1>
                        <div class="mt-8 grid grid-cols-1 gap-6 border-l-2 border-[#1C69D4] pl-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-end">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">{{ $categoryName }}</p>
                                <p class="mt-3 text-3xl font-black tracking-normal text-white sm:text-4xl">
                                    {{ number_format($vehicle->price) }} <span class="text-sm text-zinc-500">VNĐ</span>
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 {{ $heroCtaGridClass }}">
                                <a href="{{ $primaryCtaUrl }}" class="bg-[#1C69D4] px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:bg-white hover:text-black">
                                    {{ $primaryCtaLabel }}
                                </a>
                                <a href="{{ $secondaryCtaUrl }}" class="border border-white/70 px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:bg-white hover:text-black">
                                    {{ $secondaryCtaLabel }}
                                </a>
                                @if($vehicle->canCompare())
                                    <button type="button" onclick="toggleComparison({{ $vehicle->id }})" class="border border-zinc-700 px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-200 transition-all hover:border-[#1C69D4] hover:text-[#1C69D4]">
                                        Thêm so sánh
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <nav class="sticky top-20 z-40 border-y border-zinc-800 bg-zinc-950/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl gap-7 overflow-x-auto px-4 py-5 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 sm:px-6 lg:px-8">
                <a href="#overview" class="shrink-0 transition-colors hover:text-white">Tổng quan</a>
                <a href="#highlights" class="shrink-0 transition-colors hover:text-white">Điểm nổi bật</a>
                <a href="#design" class="shrink-0 transition-colors hover:text-white">{{ $isAccessory ? 'Vật liệu' : 'Thiết kế' }}</a>
                <a href="#technology" class="shrink-0 transition-colors hover:text-white">{{ $isAccessory ? 'Tương thích' : 'Công nghệ' }}</a>
                <a href="#gallery" class="shrink-0 transition-colors hover:text-white">Gallery</a>
                <a href="#technical-data" class="shrink-0 transition-colors hover:text-white">Thông tin</a>
                <a href="#booking" class="shrink-0 text-[#1C69D4] transition-colors hover:text-white">{{ $isAccessory ? 'Tư vấn' : 'Đặt lịch' }}</a>
            </div>
        </nav>

        <section id="overview" class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 py-24 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Tổng quan</p>
                <h2 class="text-4xl font-black uppercase leading-tight tracking-normal text-white md:text-6xl">
                    {{ $overviewTitle }}
                </h2>
            </div>
            <div class="space-y-10">
                <p class="text-lg font-medium leading-8 text-zinc-300">
                    {{ filled($vehicle->description) ? $vehicle->description : ($isAccessory ? 'Liên hệ showroom để nhận tư vấn về khả năng tương thích, lắp đặt và lựa chọn phụ kiện phù hợp với xe của bạn.' : 'Liên hệ showroom để nhận tư vấn chi tiết về cấu hình, trang bị và trải nghiệm vận hành của mẫu xe này.') }}
                </p>

                <div class="grid grid-cols-1 border-y border-zinc-800 sm:grid-cols-3">
                    <div class="border-b border-zinc-800 py-6 sm:border-b-0 sm:border-r sm:px-6 sm:pl-0">
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">{{ $isAccessory ? 'Tư vấn' : 'Trải nghiệm' }}</p>
                        <p class="mt-3 text-xl font-black uppercase tracking-normal text-white">{{ $isAccessory ? 'Đúng cấu hình xe' : 'Showroom & lái thử' }}</p>
                    </div>
                    <div class="border-b border-zinc-800 py-6 sm:border-b-0 sm:border-r sm:px-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">{{ $isAccessory ? 'Lắp đặt' : 'Tài chính' }}</p>
                        <p class="mt-3 text-xl font-black uppercase tracking-normal text-white">{{ $isAccessory ? 'Theo lịch hẹn' : 'Báo giá cá nhân' }}</p>
                    </div>
                    <div class="py-6 sm:px-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">{{ $isAccessory ? 'Nguồn gốc' : 'Dịch vụ' }}</p>
                        <p class="mt-3 text-xl font-black uppercase tracking-normal text-white">{{ $isAccessory ? 'Chính hãng BMW' : 'Hậu mãi đồng bộ' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="highlights" class="border-y border-zinc-900 bg-zinc-900/30 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 grid grid-cols-1 gap-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-end">
                    <div>
                        <p class="mb-4 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Điểm nổi bật</p>
                        <h2 class="text-4xl font-black uppercase tracking-normal text-white md:text-5xl">
                            {{ $isAccessory ? 'Thông tin nổi bật của phụ kiện' : 'Hiệu năng và cấu hình chính' }}
                        </h2>
                    </div>
                    <p class="text-sm font-medium leading-6 text-zinc-500">
                        Nội dung bên dưới được lấy từ dữ liệu sản phẩm hiện có. Khi thông tin chưa đầy đủ, showroom sẽ tư vấn trực tiếp thay vì hiển thị dữ liệu giả.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-px bg-zinc-800 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($highlightSpecs as $highlight)
                        <div class="bg-zinc-950 p-8">
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">{{ $highlight['label'] }}</p>
                            <p class="mt-5 text-2xl font-black uppercase tracking-normal text-white">{{ $highlight['value'] }}</p>
                        </div>
                    @empty
                        <div class="bg-zinc-950 p-8 md:col-span-2 xl:col-span-3">
                            <p class="text-sm font-medium text-zinc-400">
                                {{ $isAccessory ? 'Thông tin vật liệu và tương thích sẽ được tư vấn trực tiếp tại showroom.' : 'Thông số vận hành chi tiết sẽ được tư vấn trực tiếp tại showroom.' }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="design" class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-24 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
            <div class="aspect-[16/10] overflow-hidden border border-zinc-800 bg-zinc-900">
                <img src="{{ $designImageUrl }}" alt="{{ $vehicle->name }} - {{ $isAccessory ? 'vật liệu' : 'thiết kế' }}" class="h-full w-full object-cover">
            </div>
            <div class="lg:pl-10">
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $isAccessory ? 'Vật liệu' : 'Thiết kế' }}</p>
                <h2 class="text-4xl font-black uppercase leading-tight tracking-normal text-white">{{ $designTitle }}</h2>
                <p class="mt-6 text-base font-medium leading-7 text-zinc-400">
                    {{ $isAccessory ? 'Phụ kiện được trình bày như một sản phẩm riêng: tập trung vào chất liệu, độ hoàn thiện, tính tương thích và trải nghiệm sử dụng hằng ngày.' : 'Bố cục hình ảnh ưu tiên tỷ lệ lớn, khoảng thở rộng và nhịp section giống một landing page showroom thay vì trang thông tin ngắn.' }}
                </p>
            </div>
        </section>

        <section id="technology" class="border-y border-zinc-900 bg-white py-24 text-black">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $isAccessory ? 'Tư vấn lắp đặt' : 'Công nghệ' }}</p>
                    <h2 class="text-4xl font-black uppercase leading-tight tracking-normal md:text-6xl">{{ $technologyTitle }}</h2>
                    <p class="mt-6 text-base font-medium leading-7 text-zinc-600">
                        {{ $isAccessory ? 'CTA phụ kiện dẫn về form đặt hàng riêng để đội showroom xác nhận tương thích trước khi chốt mua hàng.' : 'Luồng đặt lịch, báo giá và so sánh vẫn giữ nguyên để người dùng chuyển từ khám phá sang hành động mà không mất logic CRM.' }}
                    </p>
                </div>
                <div class="aspect-[16/10] overflow-hidden bg-zinc-200">
                    <img src="{{ $technologyImageUrl }}" alt="{{ $vehicle->name }} - trải nghiệm" class="h-full w-full object-cover">
                </div>
            </div>
        </section>

        <section id="gallery" class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="mb-12 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Gallery</p>
                    <h2 class="text-4xl font-black uppercase tracking-normal text-white md:text-6xl">Không gian hình ảnh</h2>
                </div>
                <p class="max-w-xl text-sm font-medium leading-6 text-zinc-500">
                    Gallery ưu tiên nhiều góc ảnh khác nhau theo thứ tự ảnh sản phẩm. Nếu dữ liệu còn ít, hệ thống vẫn fallback có kiểm soát để không tạo khung trống.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="min-h-80 overflow-hidden border border-zinc-800 bg-zinc-900 lg:col-span-2 lg:row-span-2 lg:min-h-[540px]">
                    <img src="{{ $galleryImageUrls->first() }}" alt="{{ $vehicle->name }} gallery chính" class="h-full w-full object-cover">
                </div>

                @foreach($secondaryGalleryUrls as $galleryImageUrl)
                    <div class="aspect-[4/3] overflow-hidden border border-zinc-800 bg-zinc-900">
                        <img src="{{ $galleryImageUrl }}" alt="{{ $vehicle->name }} gallery {{ $loop->iteration + 1 }}" class="h-full w-full object-cover">
                    </div>
                @endforeach

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:col-span-3 lg:grid-cols-[1fr_1fr]">
                    <div class="aspect-[16/9] overflow-hidden border border-zinc-800 bg-zinc-900">
                        <img src="{{ $lifestyleImageUrl }}" alt="{{ $vehicle->name }} lifestyle" class="h-full w-full object-cover">
                    </div>
                    <div class="border border-zinc-800 bg-zinc-900/40 p-8">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">{{ $isAccessory ? 'Purchase support' : 'Showroom support' }}</p>
                        <h3 class="mt-5 text-3xl font-black uppercase tracking-normal text-white">{{ $isAccessory ? 'Xác nhận đúng phụ kiện trước khi mua' : 'Từ xem xe đến đặt lịch trong một flow' }}</h3>
                        <p class="mt-5 text-sm font-medium leading-6 text-zinc-500">
                            {{ $isAccessory ? 'Ảnh sản phẩm được dùng để kể rõ vật liệu, tương thích và trải nghiệm sử dụng.' : 'Bộ ảnh detail được phân phối theo từng section để tránh lặp một hình ở toàn bộ landing page.' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="technical-data" class="border-y border-zinc-800 bg-zinc-950 py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Thông tin</p>
                        <h2 class="text-4xl font-black uppercase tracking-normal text-white md:text-6xl">{{ $technicalTitle }}</h2>
                    </div>
                    <button type="button" @click="showDetailedSpecs = true" class="border border-zinc-700 px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:border-white hover:bg-white hover:text-black">
                        Xem chi tiết
                    </button>
                </div>

                <div class="border border-zinc-800">
                    @forelse($translatedSpecs as $label => $value)
                        <div class="grid grid-cols-1 border-b border-zinc-900 last:border-b-0 md:grid-cols-[0.8fr_1.2fr]">
                            <div class="bg-zinc-900/40 px-6 py-5 text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">
                                {{ $label }}
                            </div>
                            <div class="px-6 py-5 text-sm font-bold text-white">
                                {{ $formatSpecValue($value) }}
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-sm font-medium text-zinc-400">
                            {{ $isAccessory ? 'Thông tin sản phẩm đang được showroom cập nhật. Vui lòng gửi yêu cầu tư vấn để được xác nhận chi tiết.' : 'Thông số kỹ thuật chưa được cập nhật cho mẫu xe này. Vui lòng liên hệ showroom để được tư vấn.' }}
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="services" class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 border border-zinc-800 bg-zinc-900/40 p-8 lg:grid-cols-[1fr_0.9fr] lg:p-12">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $isAccessory ? 'Mua hàng' : 'Sở hữu' }}</p>
                    <h2 class="text-4xl font-black uppercase tracking-normal text-white">{{ $servicesTitle }}</h2>
                    <p class="mt-6 max-w-3xl text-base font-medium leading-7 text-zinc-400">
                        {{ $isAccessory ? 'Showroom sẽ kiểm tra dòng xe, nhu cầu sử dụng và phương án lắp đặt trước khi xác nhận báo giá cuối cùng.' : 'Liên hệ showroom để nhận tư vấn giá lăn bánh, cấu hình, lịch lái thử và chương trình hiện hành.' }}
                    </p>
                </div>
                <div class="border-l-0 border-zinc-800 lg:border-l lg:pl-10">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Giá niêm yết</p>
                    <p class="mt-3 text-4xl font-black tracking-normal text-white">
                        {{ number_format($vehicle->price) }} <span class="text-sm text-zinc-500">VNĐ</span>
                    </p>
                    @if($vehicle->deposit_amount)
                        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-zinc-500">
                            Mức cọc tham khảo: <span class="text-zinc-300">{{ number_format($vehicle->deposit_amount) }} VNĐ</span>
                        </p>
                    @endif
                </div>
            </div>
        </section>

        <section id="booking" class="bg-white py-20 text-black">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-8">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">{{ $isAccessory ? 'Tư vấn' : 'Đặt lịch' }}</p>
                    <h2 class="text-4xl font-black uppercase tracking-normal md:text-6xl">{{ $bookingTitle }}</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <a href="{{ $primaryCtaUrl }}" class="bg-black px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:bg-[#1C69D4]">
                        {{ $primaryCtaLabel }}
                    </a>
                    @unless($isAccessory)
                        <a href="{{ $quoteCtaUrl }}" class="border border-black px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:bg-black hover:text-white">
                            Nhận báo giá
                        </a>
                    @endunless
                    <a href="{{ $secondaryCtaUrl }}" class="border border-zinc-300 px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:border-black">
                        {{ $secondaryCtaLabel }}
                    </a>
                    @if($vehicle->canCompare())
                        <button type="button" onclick="toggleComparison({{ $vehicle->id }})" class="border border-zinc-300 px-8 py-5 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:border-[#1C69D4] hover:text-[#1C69D4]">
                            Thêm vào so sánh
                        </button>
                    @endif
                </div>
            </div>
        </section>

        @if($relatedVehicles->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                <div class="mb-12 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Related</p>
                        <h2 class="text-4xl font-black uppercase tracking-normal text-white md:text-6xl">{{ $relatedTitle }}</h2>
                    </div>
                    <a href="{{ $relatedIndexUrl }}" class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                        {{ $isAccessory ? 'Xem thêm phụ kiện' : 'Xem thêm mẫu xe' }}
                    </a>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    @foreach($relatedVehicles as $relatedVehicle)
                        <x-vehicle-card :vehicle="$relatedVehicle" />
                    @endforeach
                </div>
            </section>
        @endif

        <div
            x-show="showDetailedSpecs"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showDetailedSpecs = false"></div>

            <div
                class="relative max-h-[90vh] w-full max-w-3xl overflow-hidden border border-zinc-800 bg-zinc-950 shadow-2xl"
                x-show="showDetailedSpecs"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-y-8"
                x-transition:enter-end="translate-y-0"
            >
                <div class="flex items-center justify-between border-b border-zinc-900 p-6 sm:p-8">
                    <h4 class="text-xl font-black uppercase tracking-normal text-white">
                        {{ $technicalTitle }} <span class="text-[#1C69D4]">chi tiết</span>
                    </h4>
                    <button type="button" @click="showDetailedSpecs = false" class="text-zinc-500 transition-colors hover:text-white" aria-label="Đóng thông tin chi tiết">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-6 sm:p-8">
                    @if($translatedSpecs->isNotEmpty())
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="border-b border-zinc-800 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">
                                    <th class="py-4 font-black">{{ $technicalTitle }}</th>
                                    <th class="py-4 font-black">Giá trị</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-900/70 text-sm font-medium text-white">
                                @foreach($translatedSpecs as $label => $value)
                                    <tr>
                                        <td class="py-4 pr-6 text-zinc-400">{{ $label }}</td>
                                        <td class="py-4 font-bold text-white">{{ $formatSpecValue($value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm font-medium text-zinc-400">Thông tin chi tiết đang được showroom cập nhật.</p>
                    @endif
                </div>

                <div class="border-t border-zinc-900 bg-zinc-900/50 p-6 sm:p-8">
                    <p class="text-[10px] uppercase leading-relaxed tracking-widest text-zinc-500">
                        * Thông tin có thể thay đổi tùy phiên bản, cấu hình, phụ kiện đi kèm và điều kiện thực tế tại showroom.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
