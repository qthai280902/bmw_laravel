<x-app-layout>
    @section('title', $vehicle->name . ' - BMW Showroom')

    <div class="bg-zinc-950 pb-20">
        <!-- Hero Section: Premium Image Showcase -->
        <div class="relative h-[70vh] w-full overflow-hidden">
            <img src="{{ $vehicle->primaryImage ? (Str::startsWith($vehicle->primaryImage->path, 'http') ? $vehicle->primaryImage->path : Storage::url($vehicle->primaryImage->path)) : 'https://placehold.co/1920x1080/111111/ffffff?text=BMW+Premium' }}" class="w-full h-full object-cover grayscale-[0.5] hover:grayscale-0 transition-all duration-1000 transform scale-105 hover:scale-100" alt="{{ $vehicle->name }}">
            
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent"></div>
            
            <div class="absolute bottom-12 left-1/2 -translate-x-1/2 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-[#1C69D4] text-sm font-black uppercase tracking-[0.3em] mb-4">{{ $vehicle->brand->name }}</p>
                        <h1 class="text-7xl md:text-9xl font-black text-white uppercase tracking-tighter leading-none italic">
                            {{ $vehicle->name }}
                        </h1>
                    </div>
                    <div class="text-right pb-4">
                        <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-2">Giá niêm yết từ</p>
                        <p class="text-4xl font-black text-white tracking-tighter">
                            {{ number_format($vehicle->price) }} <span class="text-sm text-zinc-500">VNĐ</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-20">
                
                <!-- Left: Description & Gallery -->
                <div class="lg:col-span-2 space-y-16">
                    <!-- Description -->
                    <section>
                        <h2 class="text-xs font-black uppercase tracking-[0.5em] text-[#1C69D4] mb-8">Trải nghiệm & Triết lý</h2>
                        <div class="prose prose-invert max-w-none text-zinc-400 leading-relaxed text-lg italic font-light">
                            {!! nl2br(e($vehicle->description)) !!}
                        </div>
                    </section>

                    <!-- Large Gallery Grid -->
                    <section class="grid grid-cols-2 gap-4">
                        @foreach($vehicle->images->where('is_primary', false) as $image)
                            <div class="aspect-[4/3] bg-zinc-900 border border-zinc-800 overflow-hidden cursor-pointer group/thumb">
                                <img src="{{ $image ? (Str::startsWith($image->path, 'http') ? $image->path : Storage::url($image->path)) : 'https://placehold.co/800x600/111111/ffffff?text=BMW+Premium' }}" class="w-full h-full object-cover grayscale opacity-50 group-hover/thumb:grayscale-0 group-hover/thumb:opacity-100 transition-all duration-500">
                            </div>
                        @endforeach
                    </section>
                </div>

                <!-- Right: Specs & Action -->
                <div class="space-y-12">
                    <!-- Specifications Card -->
                    <div class="bg-zinc-950 p-10 border border-zinc-800">
                        <h3 class="text-xs font-black uppercase tracking-[0.4em] text-[#1C69D4] mb-12 flex items-center">
                            Specs <span class="ml-4 flex-grow h-px bg-zinc-900"></span>
                        </h3>
                        <div class="space-y-8">
                            @foreach($vehicle->translated_specs as $key => $value)
                                <div class="group">
                                    <div class="flex justify-between items-baseline mb-2">
                                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600 group-hover:text-zinc-400 transition-colors">
                                            {{ $key }}
                                        </span>
                                        <span class="text-right text-sm font-black text-white uppercase tracking-wider">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                    </div>
                                    <div class="h-px bg-zinc-900 group-hover:bg-[#1C69D4] transition-all duration-500"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Call to Action -->
                    <div class="space-y-4">
                        <a href="{{ route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'test_drive']) }}" 
                            class="block w-full bg-accent text-white py-6 font-black uppercase tracking-[0.2em] text-xs text-center hover:bg-white hover:text-black transition-all shadow-xl">
                            Đăng ký lái thử
                        </a>

                        <a href="{{ route('appointments.create', ['product_id' => $vehicle->id, 'type' => 'quote']) }}" 
                            class="block w-full border border-zinc-800 text-white py-6 font-black uppercase tracking-[0.2em] text-xs text-center hover:border-white transition-all">
                            Nhận Báo Giá
                        </a>
                    </div>

                    <!-- Comparison Link -->
                    <div class="p-6 border-t border-zinc-900 flex items-center justify-between">
                        <span class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Muốn so sánh xe này?</span>
                        <button onclick="toggleComparison({{ $vehicle->id }})" class="text-[#1C69D4] text-[10px] font-black uppercase tracking-widest hover:underline">
                            + Thêm vào danh sách
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
