<x-app-layout>
    @section('title', $vehicle->name . ' - BMW Showroom')

    @php
        $fallbackImageUrl = asset('images/cars/hero.png');
        $images = $vehicle->images->sortBy('sort_order')->values();
        $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();

        $imageUrl = function ($image) use ($fallbackImageUrl): string {
            if (! $image || blank($image->path)) {
                return $fallbackImageUrl;
            }

            $path = $image->path;

            if (Str::startsWith($path, ['http://', 'https://'])) {
                return $path;
            }

            if (Str::startsWith($path, ['/storage', 'storage/', 'images/'])) {
                return asset(ltrim($path, '/'));
            }

            return Storage::url($path);
        };

        $formatSpecValue = function ($value): string {
            if (is_array($value)) {
                return collect($value)->flatten()->filter(fn ($item) => filled($item))->implode(', ');
            }

            return filled($value) ? (string) $value : 'Liên hệ showroom';
        };

        $specifications = collect($vehicle->specifications ?? [])
            ->filter(fn ($value) => is_array($value) ? count($value) > 0 : filled($value));

        $translatedSpecs = collect($vehicle->translated_specs ?? [])
            ->filter(fn ($value) => is_array($value) ? count($value) > 0 : filled($value));

        $highlightLabels = [
            'Engine' => 'Động cơ',
            'Horsepower' => 'Công suất',
            'Max_Power_RPM' => 'Công suất',
            'Torque' => 'Mô-men xoắn',
            'Max_Torque_RPM' => 'Mô-men xoắn',
            '0-100km/h' => 'Tăng tốc',
            '0-60mph' => 'Tăng tốc',
            'Zero_To_Hundred' => 'Tăng tốc',
            'Top Speed' => 'Tốc độ tối đa',
            'Top_Speed_KMH' => 'Tốc độ tối đa',
            'Drivetrain' => 'Dẫn động',
            'Drive_Type' => 'Dẫn động',
            'Transmission' => 'Hộp số',
            'Transmission_Type' => 'Hộp số',
        ];

        $highlightSpecs = collect(array_keys($highlightLabels))
            ->filter(fn ($key) => $specifications->has($key))
            ->unique(fn ($key) => $highlightLabels[$key])
            ->take(6)
            ->map(fn ($key) => [
                'label' => $highlightLabels[$key],
                'value' => $formatSpecValue($specifications->get($key)),
            ])
            ->values();

        $overviewHighlights = $highlightSpecs->take(3);
        $galleryImages = $images
            ->filter(fn ($image) => $primaryImage ? $image->id !== $primaryImage->id : true)
            ->values();
        $designImage = $galleryImages->get(0) ?? $primaryImage;
        $technologyImage = $galleryImages->get(1) ?? $designImage ?? $primaryImage;
        $isAccessory = Str::contains(Str::lower($vehicle->category?->name ?? ''), ['phụ kiện', 'phu kien']);
    @endphp

    <div class="bg-zinc-950 text-white" x-data="{ showDetailedSpecs: false }">
        <section class="relative min-h-[80vh] overflow-hidden">
            <img
                src="{{ $imageUrl($primaryImage) }}"
                alt="{{ $vehicle->name }}"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/55 to-transparent"></div>
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-zinc-950 to-transparent"></div>

            <div class="relative z-10 flex min-h-[80vh] items-end">
                <div class="mx-auto w-full max-w-7xl px-4 pb-14 sm:px-6 lg:px-8 lg:pb-20">
                    <div class="max-w-4xl">
                        <p class="mb-5 text-xs font-black uppercase tracking-[0.35em] text-[#1C69D4]">
                            {{ $vehicle->category?->name ?? 'BMW Showroom' }}
                        </p>
                        <h1 class="text-5xl font-black uppercase leading-none tracking-tight text-white sm:text-7xl lg:text-8xl">
                            {{ $vehicle->name }}
                        </h1>
                        <div class="mt-8 flex flex-col gap-5 border-l-2 border-[#1C69D4] pl-6 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Giá niêm yết từ</p>
                                <p class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                                    {{ number_format($vehicle->price) }} <span class="text-sm text-zinc-500">VNĐ</span>
                                </p>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'test_drive']) }}" class="bg-[#1C69D4] px-7 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:bg-white hover:text-black">
                                    Đăng ký lái thử
                                </a>
                                <a href="{{ route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'quote']) }}" class="border border-white/70 px-7 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:bg-white hover:text-black">
                                    Nhận báo giá
                                </a>
                                @unless($isAccessory)
                                    <button type="button" onclick="toggleComparison({{ $vehicle->id }})" class="border border-zinc-700 px-7 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-200 transition-all hover:border-[#1C69D4] hover:text-[#1C69D4]">
                                        Thêm so sánh
                                    </button>
                                @endunless
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <nav class="sticky top-20 z-40 border-y border-zinc-800 bg-zinc-950/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl gap-8 overflow-x-auto px-4 py-5 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 sm:px-6 lg:px-8">
                <a href="#overview" class="shrink-0 transition-colors hover:text-white">Tổng quan</a>
                <a href="#design" class="shrink-0 transition-colors hover:text-white">Thiết kế</a>
                <a href="#technology" class="shrink-0 transition-colors hover:text-white">Công nghệ</a>
                <a href="#technical-data" class="shrink-0 transition-colors hover:text-white">Thông số</a>
                <a href="#services" class="shrink-0 transition-colors hover:text-white">Dịch vụ</a>
                <a href="#booking" class="shrink-0 text-[#1C69D4] transition-colors hover:text-white">Đặt lịch</a>
            </div>
        </nav>

        <section id="overview" class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 py-24 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Tổng quan</p>
                <h2 class="text-4xl font-black uppercase leading-tight tracking-tight text-white md:text-6xl">
                    Một mẫu xe được đặt ở trung tâm trải nghiệm showroom.
                </h2>
            </div>
            <div class="space-y-10">
                <p class="text-lg font-medium leading-8 text-zinc-300">
                    {{ filled($vehicle->description) ? $vehicle->description : 'Liên hệ showroom để nhận tư vấn chi tiết về cấu hình, trang bị và trải nghiệm vận hành của mẫu xe này.' }}
                </p>

                @if($overviewHighlights->isNotEmpty())
                    <div class="grid grid-cols-1 border-y border-zinc-800 sm:grid-cols-3">
                        @foreach($overviewHighlights as $highlight)
                            <div class="border-b border-zinc-800 py-6 sm:border-b-0 sm:border-r sm:px-6 first:sm:pl-0 last:sm:border-r-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">{{ $highlight['label'] }}</p>
                                <p class="mt-3 text-xl font-black uppercase tracking-tight text-white">{{ $highlight['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="border-y border-zinc-900 bg-zinc-900/30 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="mb-4 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Điểm nổi bật</p>
                        <h2 class="text-3xl font-black uppercase tracking-tight text-white md:text-5xl">Hiệu năng và cấu hình chính</h2>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-zinc-500">
                        Dữ liệu bên dưới được lấy trực tiếp từ thông số đang lưu cho mẫu xe. Thông tin thiếu sẽ được tư vấn tại showroom.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-px bg-zinc-800 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($highlightSpecs as $highlight)
                        <div class="bg-zinc-950 p-8">
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">{{ $highlight['label'] }}</p>
                            <p class="mt-5 text-2xl font-black uppercase tracking-tight text-white">{{ $highlight['value'] }}</p>
                        </div>
                    @empty
                        <div class="bg-zinc-950 p-8 md:col-span-2 xl:col-span-3">
                            <p class="text-sm font-medium text-zinc-400">Liên hệ showroom để nhận thông số vận hành chi tiết cho mẫu xe này.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="design" class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-24 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
            <div class="aspect-[16/10] overflow-hidden border border-zinc-800 bg-zinc-900">
                <img src="{{ $imageUrl($designImage) }}" alt="{{ $vehicle->name }} - thiết kế ngoại thất" class="h-full w-full object-cover">
            </div>
            <div class="lg:pl-10">
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Thiết kế</p>
                <h2 class="text-4xl font-black uppercase leading-tight tracking-tight text-white">Thiết kế ngoại thất</h2>
                <p class="mt-6 text-base font-medium leading-7 text-zinc-400">
                    Hình ảnh showroom được ưu tiên hiển thị từ gallery của mẫu xe. Khi dữ liệu hình ảnh còn hạn chế, hệ thống dùng ảnh fallback nội bộ để không làm rỗng trải nghiệm.
                </p>
            </div>
        </section>

        <section id="technology" class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 pb-24 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
            <div class="order-2 lg:order-1">
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Công nghệ</p>
                <h2 class="text-4xl font-black uppercase leading-tight tracking-tight text-white">Cảm giác lái và kết nối</h2>
                <p class="mt-6 text-base font-medium leading-7 text-zinc-400">
                    Các thông tin công nghệ, vận hành và trang bị được phản ánh trong bảng thông số bên dưới khi dữ liệu sản phẩm có sẵn.
                </p>
            </div>
            <div class="order-1 aspect-[16/10] overflow-hidden border border-zinc-800 bg-zinc-900 lg:order-2">
                <img src="{{ $imageUrl($technologyImage) }}" alt="{{ $vehicle->name }} - công nghệ và cảm giác lái" class="h-full w-full object-cover">
            </div>
        </section>

        <section id="technical-data" class="border-y border-zinc-800 bg-zinc-950 py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Thông số</p>
                        <h2 class="text-4xl font-black uppercase tracking-tight text-white md:text-6xl">Thông số kỹ thuật</h2>
                    </div>
                    <button type="button" @click="showDetailedSpecs = true" class="border border-zinc-700 px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:border-white hover:bg-white hover:text-black">
                        Thông số kỹ thuật chi tiết
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
                            Thông số kỹ thuật chưa được cập nhật cho mẫu xe này. Vui lòng liên hệ showroom để được tư vấn.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="services" class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 border border-zinc-800 bg-zinc-900/40 p-8 lg:grid-cols-[1fr_0.9fr] lg:p-12">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Mức giá và dịch vụ</p>
                    <h2 class="text-4xl font-black uppercase tracking-tight text-white">Tư vấn cấu hình và chi phí sở hữu</h2>
                    <p class="mt-6 max-w-3xl text-base font-medium leading-7 text-zinc-400">
                        Liên hệ showroom để nhận tư vấn giá lăn bánh và chương trình hiện hành.
                    </p>
                </div>
                <div class="border-l-0 border-zinc-800 lg:border-l lg:pl-10">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-500">Giá niêm yết</p>
                    <p class="mt-3 text-4xl font-black tracking-tight text-white">
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
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Đặt lịch</p>
                    <h2 class="text-4xl font-black uppercase tracking-tight md:text-6xl">Sẵn sàng trải nghiệm {{ $vehicle->name }}?</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <a href="{{ route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'test_drive']) }}" class="bg-black px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:bg-[#1C69D4]">
                        Đăng ký lái thử
                    </a>
                    <a href="{{ route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'quote']) }}" class="border border-black px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:bg-black hover:text-white">
                        Nhận báo giá
                    </a>
                    <a href="{{ route('contact.index') }}" class="border border-zinc-300 px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:border-black sm:col-span-2">
                        Liên hệ tư vấn
                    </a>
                    @unless($isAccessory)
                        <button type="button" onclick="toggleComparison({{ $vehicle->id }})" class="border border-zinc-300 px-8 py-5 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:border-[#1C69D4] hover:text-[#1C69D4] sm:col-span-2">
                            Thêm vào so sánh
                        </button>
                    @endunless
                </div>
            </div>
        </section>

        @if($relatedVehicles->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                <div class="mb-12 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">More models</p>
                        <h2 class="text-4xl font-black uppercase tracking-tight text-white">Cùng phân khúc</h2>
                    </div>
                    <a href="{{ route('products.index', ['category_id' => $vehicle->category_id]) }}" class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                        Xem thêm mẫu xe
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
                    <h4 class="text-xl font-black uppercase tracking-tight text-white">
                        Thông số <span class="text-[#1C69D4]">chi tiết</span>
                    </h4>
                    <button type="button" @click="showDetailedSpecs = false" class="text-zinc-500 transition-colors hover:text-white" aria-label="Đóng thông số kỹ thuật">
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
                                    <th class="py-4 font-black">Thông số kỹ thuật</th>
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
                        <p class="text-sm font-medium text-zinc-400">Thông số kỹ thuật chưa được cập nhật cho mẫu xe này.</p>
                    @endif
                </div>

                <div class="border-t border-zinc-900 bg-zinc-900/50 p-6 sm:p-8">
                    <p class="text-[10px] uppercase leading-relaxed tracking-widest text-zinc-500">
                        * Thông số có thể thay đổi tùy theo phiên bản và điều kiện vận hành thực tế. Liên hệ showroom để biết thêm chi tiết.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
