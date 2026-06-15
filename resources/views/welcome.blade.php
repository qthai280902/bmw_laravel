<x-app-layout>
    <section class="relative min-h-[calc(100vh-4.5rem)] overflow-hidden bg-black">
        <img
            src="{{ asset('images/cars/hero.png') }}"
            class="absolute inset-0 h-full w-full object-cover"
            alt="BMW showroom hero"
        >
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(0,0,0,0.92)_0%,rgba(0,0,0,0.72)_44%,rgba(0,0,0,0.18)_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-zinc-950 to-transparent"></div>

        <div class="relative z-10 mx-auto grid min-h-[calc(100vh-4.5rem)] max-w-7xl grid-cols-1 items-end gap-10 px-4 pb-14 pt-28 sm:px-6 lg:grid-cols-[0.9fr_0.55fr] lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.34em] text-[#70A7FF]">BMW showroom Vietnam</p>
                <h1 class="text-4xl font-black uppercase leading-none tracking-normal text-white sm:text-6xl lg:text-7xl">
                    Khám phá BMW theo nhịp showroom.
                </h1>
                <p class="mt-7 max-w-2xl text-base font-medium leading-7 text-zinc-300 sm:text-lg">
                    Từ catalog, landing page sản phẩm, so sánh xe đến form tư vấn, mọi điểm chạm public được tổ chức lại để khách hàng đi từ cảm hứng đến lịch hẹn rõ ràng hơn.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('products.index', ['type' => 'car']) }}" class="inline-flex items-center justify-center bg-[#1C69D4] px-7 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:bg-white hover:text-black">
                        Khám phá ô tô
                    </a>
                    <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="inline-flex items-center justify-center border border-white/70 px-7 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:bg-white hover:text-black">
                        Đặt lịch tư vấn
                    </a>
                </div>
            </div>

            <div class="mb-1 grid grid-cols-3 gap-px bg-white/10 lg:mb-10">
                <a href="{{ route('appointments.create', ['type' => 'test_drive']) }}" class="bg-black/55 p-4 backdrop-blur transition-colors hover:bg-[#1C69D4] sm:p-5">
                    <p class="text-[9px] font-black uppercase tracking-[0.22em] text-zinc-400">01</p>
                    <p class="mt-3 text-xs font-black uppercase leading-5 text-white">Lái thử</p>
                </a>
                <a href="{{ route('appointments.create', ['type' => 'quote']) }}" class="bg-black/55 p-4 backdrop-blur transition-colors hover:bg-[#1C69D4] sm:p-5">
                    <p class="text-[9px] font-black uppercase tracking-[0.22em] text-zinc-400">02</p>
                    <p class="mt-3 text-xs font-black uppercase leading-5 text-white">Báo giá</p>
                </a>
                <a href="{{ route('products.compare') }}" class="bg-black/55 p-4 backdrop-blur transition-colors hover:bg-[#1C69D4] sm:p-5">
                    <p class="text-[9px] font-black uppercase tracking-[0.22em] text-zinc-400">03</p>
                    <p class="mt-3 text-xs font-black uppercase leading-5 text-white">So sánh</p>
                </a>
            </div>
        </div>
    </section>

    <section class="bg-zinc-950 py-20 text-white sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 grid grid-cols-1 gap-6 lg:grid-cols-[0.78fr_1fr] lg:items-end">
                <div>
                    <p class="mb-4 text-[10px] font-black uppercase tracking-[0.32em] text-[#1C69D4]">Product lines</p>
                    <h2 class="text-3xl font-black uppercase tracking-normal sm:text-5xl">Dòng sản phẩm</h2>
                </div>
                <p class="max-w-2xl text-sm font-medium leading-6 text-zinc-500">
                    Mỗi lối vào được giữ đúng route hiện có, nhưng được trình bày như khu vực showroom riêng cho ô tô, Motorrad, phụ kiện và dịch vụ hậu mãi.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-px bg-zinc-800 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('products.index', ['type' => 'car']) }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/sedan.png') }}" alt="BMW ô tô" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Cars</p>
                        <h3 class="mt-3 text-2xl font-black uppercase tracking-normal text-white">Ô tô BMW</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Sedan, SUV, xe điện và các mẫu M Performance.</p>
                    </div>
                </a>
                <a href="{{ route('products.index', ['type' => 'motorbike']) }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/superbike.png') }}" alt="BMW Motorrad" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Motorrad</p>
                        <h3 class="mt-3 text-2xl font-black uppercase tracking-normal text-white">Xe máy</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Các mẫu Motorrad cho hành trình hằng ngày và đường dài.</p>
                    </div>
                </a>
                <a href="{{ route('accessories.index') }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/accessories/tham-lot-san-m-performance/lifestyle-use.png') }}" alt="BMW phụ kiện" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Accessories</p>
                        <h3 class="mt-3 text-2xl font-black uppercase tracking-normal text-white">Phụ kiện</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Phụ kiện chính hãng cho xe, nội thất và phong cách lái.</p>
                    </div>
                </a>
                <a href="{{ route('services.index') }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/bmw-550e-xdrive-sedan/design-detail.png') }}" alt="BMW dịch vụ" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Aftersales</p>
                        <h3 class="mt-3 text-2xl font-black uppercase tracking-normal text-white">Dịch vụ</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Bảo dưỡng, chăm sóc xe và đặt lịch dịch vụ.</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="bg-white py-20 text-black sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mb-4 text-[10px] font-black uppercase tracking-[0.32em] text-[#1C69D4]">Featured selection</p>
                    <h2 class="text-3xl font-black uppercase tracking-normal sm:text-5xl">Mẫu nổi bật</h2>
                </div>
                <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center border border-black px-7 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-all hover:bg-black hover:text-white">
                    Xem catalog
                </a>
            </div>

            @if($featuredProducts->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach($featuredProducts as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" :showCompare="false" />
                    @endforeach
                </div>
            @else
                <div class="border border-zinc-200 px-8 py-14 text-center">
                    <p class="text-sm font-medium text-zinc-600">Showroom đang cập nhật danh sách sản phẩm nổi bật.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="bg-black py-20 text-white sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mb-4 text-[10px] font-black uppercase tracking-[0.32em] text-[#1C69D4]">Editorial showroom</p>
                    <h2 class="text-3xl font-black uppercase tracking-normal sm:text-5xl">Tìm hiểu thêm</h2>
                    <p class="mt-4 max-w-2xl text-sm font-medium leading-6 text-zinc-500">
                        Ưu đãi, sự kiện, kinh nghiệm chọn xe và dịch vụ hậu mãi được viết theo ngữ cảnh showroom BMW.
                    </p>
                </div>
                <a href="{{ route('articles.index') }}" class="inline-flex items-center justify-center border border-white px-7 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:bg-white hover:text-black">
                    Xem tất cả
                </a>
            </div>

            @if($latestArticles->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach($latestArticles as $article)
                        <article class="group flex h-full flex-col overflow-hidden border border-zinc-900 bg-zinc-950 transition-colors hover:border-[#1C69D4]">
                            <a href="{{ route('articles.show', $article) }}" class="block">
                                <div class="aspect-[16/10] overflow-hidden bg-zinc-900">
                                    <img src="{{ $article->editorialImageUrl() }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                                </div>
                            </a>
                            <div class="flex flex-1 flex-col p-7">
                                <div class="flex-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.26em] text-[#1C69D4]">{{ $article->categoryLabel() }}</p>
                                    <a href="{{ route('articles.show', $article) }}" class="mt-4 block">
                                        <h3 class="text-xl font-black uppercase leading-tight tracking-normal text-white transition-colors group-hover:text-[#70A7FF]">{{ $article->title }}</h3>
                                    </a>
                                    <p class="mt-4 text-sm font-medium leading-6 text-zinc-500">{{ $article->excerpt ?? Str::limit(strip_tags($article->body), 140) }}</p>
                                </div>
                                <div class="mt-8 flex items-center justify-between border-t border-zinc-900 pt-5">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">
                                        {{ $article->published_at?->format('d/m/Y') }}
                                    </p>
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white">Đọc tiếp</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="border border-zinc-900 bg-zinc-950 px-8 py-14 text-center">
                    <p class="text-sm font-medium text-zinc-600">Showroom đang chuẩn bị các bài viết mới.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="bg-zinc-950 py-20 text-white sm:py-24">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-8">
            <div>
                <p class="mb-4 text-[10px] font-black uppercase tracking-[0.32em] text-[#1C69D4]">Showroom flow</p>
                <h2 class="text-3xl font-black uppercase leading-tight tracking-normal sm:text-5xl">Từ khám phá đến tư vấn trong cùng một trải nghiệm.</h2>
                <p class="mt-6 max-w-2xl text-base font-medium leading-7 text-zinc-500">
                    Người dùng có thể xem chi tiết, thêm xe vào so sánh, yêu cầu báo giá hoặc đặt lịch gặp cố vấn mà không thay đổi luồng CRM hiện tại.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-px bg-zinc-800 sm:grid-cols-2">
                <a href="{{ route('appointments.create', ['type' => 'advisor_meeting']) }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Advisor</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">Gặp cố vấn</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Trao đổi cấu hình và nhu cầu sử dụng.</p>
                </a>
                <a href="{{ route('appointments.create', ['type' => 'quote']) }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Quote</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">Nhận báo giá</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Yêu cầu showroom phản hồi chi tiết.</p>
                </a>
                <a href="{{ route('products.compare') }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Compare</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">So sánh xe</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Đặt các mẫu quan tâm cạnh nhau.</p>
                </a>
                <a href="{{ route('offers.exclusive') }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#1C69D4]">Offers</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">Ưu đãi</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-zinc-500">Xem chương trình đang áp dụng.</p>
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
