<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Comparison studio</p>
                <h2 class="mt-3 text-4xl font-black uppercase tracking-normal text-white md:text-6xl">
                    Bản đối chiếu thông số
                </h2>
                <p class="mt-4 max-w-2xl text-sm font-medium leading-6 text-zinc-500">
                    So sánh cấu hình, mức giá và thông số đang lưu trong showroom. Ảnh sản phẩm dùng dữ liệu nội bộ và fallback an toàn khi thiếu media.
                </p>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center border border-zinc-700 px-8 py-4 text-[10px] font-black uppercase tracking-[0.25em] text-white transition-all hover:border-white hover:bg-white hover:text-black">
                Quay lại catalog
            </a>
        </div>
    </x-slot>

    <div class="bg-zinc-950 py-16 text-white sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if($products->isEmpty())
                <section class="border border-zinc-800 bg-zinc-900/30 px-6 py-20 text-center">
                    <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">Chưa có lựa chọn</p>
                    <h1 class="mt-4 text-4xl font-black uppercase tracking-normal text-white">Chọn sản phẩm để so sánh</h1>
                    <p class="mx-auto mt-5 max-w-xl text-sm font-medium leading-6 text-zinc-500">
                        Hãy quay lại catalog và chọn tối thiểu hai mẫu xe để xem bảng đối chiếu rõ ràng hơn.
                    </p>
                    <a href="{{ route('products.index') }}" class="mt-10 inline-flex items-center justify-center bg-white px-9 py-4 text-[10px] font-black uppercase tracking-[0.25em] text-black transition-all hover:bg-[#1C69D4] hover:text-white">
                        Chọn sản phẩm
                    </a>
                </section>
            @else
                @php
                    $compareGridClass = match (min($products->count(), 4)) {
                        1 => 'md:grid-cols-1',
                        2 => 'md:grid-cols-2',
                        3 => 'md:grid-cols-3',
                        default => 'md:grid-cols-4',
                    };
                @endphp

                <section class="mb-12 grid grid-cols-1 gap-px bg-zinc-800 {{ $compareGridClass }}">
                    @foreach($products as $product)
                        <article class="bg-zinc-950">
                            <div class="aspect-[16/10] overflow-hidden bg-zinc-900">
                                <img
                                    src="{{ $product->displayImageUrl() }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                                >
                            </div>
                            <div class="space-y-5 p-6">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#1C69D4]">{{ $product->category?->name ?? 'BMW Showroom' }}</p>
                                    <h3 class="mt-3 text-2xl font-black uppercase leading-none tracking-normal text-white">{{ $product->name }}</h3>
                                </div>
                                <div class="border-t border-zinc-900 pt-5">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Giá niêm yết</p>
                                    <p class="mt-2 text-2xl font-black tracking-normal text-white">
                                        {{ number_format($product->price) }} <span class="text-xs text-zinc-500">VNĐ</span>
                                    </p>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <a href="{{ route('products.show', $product->slug) }}" class="border border-zinc-700 px-5 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:border-white hover:bg-white hover:text-black">
                                        Xem chi tiết
                                    </a>
                                    <button
                                        type="button"
                                        onclick="toggleComparison({{ $product->id }}); window.location.reload();"
                                        class="px-5 py-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 transition-colors hover:text-[#1C69D4]"
                                    >
                                        Bỏ so sánh
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="overflow-x-auto border border-zinc-800">
                    <table class="w-full min-w-[820px] border-collapse">
                        <thead>
                            <tr class="bg-zinc-900/60">
                                <th class="w-72 border-b border-zinc-800 p-6 text-left">
                                    <span class="text-[10px] font-black uppercase tracking-[0.28em] text-zinc-500">Thông số</span>
                                </th>
                                @foreach($products as $product)
                                    <th class="border-b border-zinc-800 p-6 text-center">
                                        <span class="text-[10px] font-black uppercase tracking-[0.24em] text-white">{{ $product->name }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matrix as $specKey => $values)
                                <tr class="group transition-colors hover:bg-zinc-900/50">
                                    <td class="border-b border-zinc-900 px-6 py-5">
                                        <span class="text-xs font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors group-hover:text-white">
                                            {{ \Illuminate\Support\Str::headline($specKey) }}
                                        </span>
                                    </td>
                                    @foreach($values as $value)
                                        <td class="border-b border-zinc-900 px-6 py-5 text-center">
                                            <span class="text-sm font-bold text-zinc-300">
                                                {{ is_array($value) ? implode(', ', $value) : $value }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
