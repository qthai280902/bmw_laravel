<x-app-layout>
    <section class="relative min-h-[calc(100vh-5rem)] overflow-hidden bg-black">
        <img
            src="{{ asset('images/cars/hero.png') }}"
            class="absolute inset-0 h-full w-full object-cover"
            alt="BMW showroom hero"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/75 to-black/10"></div>
        <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-zinc-950 to-transparent"></div>

        <div class="relative z-10 mx-auto flex min-h-[calc(100vh-5rem)] max-w-7xl items-end px-4 pb-20 sm:px-6 lg:px-8">
            <div class="max-w-4xl">
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.4em] text-[#1C69D4]">BMW showroom Vietnam</p>
                <h1 class="text-5xl font-black uppercase leading-none tracking-normal text-white sm:text-7xl lg:text-8xl">
                    Không gian khám phá BMW trực tuyến.
                </h1>
                <p class="mt-8 max-w-2xl text-lg font-medium leading-8 text-zinc-300">
                    Từ ô tô, Motorrad đến phụ kiện chính hãng, toàn bộ trải nghiệm public được tổ chức lại theo nhịp showroom: xem nhanh, so sánh, nhận tư vấn và đặt lịch.
                </p>
                <div class="mt-10 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <a href="{{ route('products.index', ['type' => 'car']) }}" class="bg-[#1C69D4] px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                        Khám phá ô tô
                    </a>
                    <a href="{{ route('accessories.index') }}" class="border border-white/70 px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                        Phụ kiện
                    </a>
                    <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="border border-zinc-700 px-8 py-5 text-center text-[10px] font-black uppercase tracking-[0.24em] text-zinc-200 transition-all hover:border-[#1C69D4] hover:text-[#1C69D4]">
                        Tư vấn
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-zinc-950 py-24 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 grid grid-cols-1 gap-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Explore</p>
                    <h2 class="text-4xl font-black uppercase tracking-normal md:text-6xl">Dòng sản phẩm</h2>
                </div>
                <p class="max-w-2xl text-sm font-medium leading-6 text-zinc-500">
                    Các lối vào chính giữ nguyên route hiện có, nhưng được trình bày như những khu vực showroom riêng cho xe, mô tô, phụ kiện và dịch vụ.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-px bg-zinc-800 lg:grid-cols-4">
                <a href="{{ route('products.index', ['type' => 'car']) }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/sedan.png') }}" alt="BMW ô tô" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-7">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Cars</p>
                        <h3 class="mt-4 text-2xl font-black uppercase tracking-normal text-white">Ô tô BMW</h3>
                    </div>
                </a>
                <a href="{{ route('products.index', ['type' => 'motorbike']) }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/superbike.png') }}" alt="BMW Motorrad" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-7">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Motorrad</p>
                        <h3 class="mt-4 text-2xl font-black uppercase tracking-normal text-white">Xe máy</h3>
                    </div>
                </a>
                <a href="{{ route('accessories.index') }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/x3m50.png') }}" alt="BMW phụ kiện" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-7">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Accessories</p>
                        <h3 class="mt-4 text-2xl font-black uppercase tracking-normal text-white">Phụ kiện</h3>
                    </div>
                </a>
                <a href="{{ route('services.index') }}" class="group bg-zinc-950">
                    <div class="aspect-[4/3] overflow-hidden bg-zinc-900">
                        <img src="{{ asset('images/cars/suv.png') }}" alt="BMW dịch vụ" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-7">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Services</p>
                        <h3 class="mt-4 text-2xl font-black uppercase tracking-normal text-white">Dịch vụ</h3>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="bg-white py-24 text-black">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-14 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Featured</p>
                    <h2 class="text-4xl font-black uppercase tracking-normal md:text-6xl">Mẫu nổi bật</h2>
                </div>
                <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center border border-black px-8 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-black transition-all hover:bg-black hover:text-white">
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

    <section class="bg-zinc-950 py-24 text-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_0.9fr] lg:items-center lg:px-8">
            <div>
                <p class="mb-5 text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Showroom flow</p>
                <h2 class="text-4xl font-black uppercase leading-tight tracking-normal md:text-6xl">Từ khám phá đến tư vấn trong cùng một trải nghiệm.</h2>
                <p class="mt-6 max-w-2xl text-base font-medium leading-7 text-zinc-500">
                    Người dùng có thể xem chi tiết, thêm xe vào so sánh, yêu cầu báo giá hoặc đặt lịch gặp cố vấn mà không thay đổi luồng CRM hiện tại.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-px bg-zinc-800 sm:grid-cols-2">
                <a href="{{ route('appointments.create', ['type' => 'advisor_meeting']) }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Advisor</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">Gặp cố vấn</h3>
                </a>
                <a href="{{ route('appointments.create', ['type' => 'quote']) }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Quote</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">Nhận báo giá</h3>
                </a>
                <a href="{{ route('products.compare') }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Compare</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">So sánh xe</h3>
                </a>
                <a href="{{ route('offers.exclusive') }}" class="bg-zinc-950 p-8 transition-colors hover:bg-zinc-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">Offers</p>
                    <h3 class="mt-4 text-2xl font-black uppercase tracking-normal">Ưu đãi</h3>
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
