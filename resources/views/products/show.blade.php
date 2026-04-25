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
                        <p class="text-[#1C69D4] text-sm font-black uppercase tracking-[0.3em] mb-4">{{ $vehicle->category->name }}</p>
                        <h1 class="text-7xl md:text-9xl font-black text-white uppercase tracking-tighter leading-none ">
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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-20" x-data="{ showDetailedSpecs: false }">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-20">
                
                <!-- Left: Description & Gallery -->
                <div class="lg:col-span-2 space-y-16">
                    <!-- Description -->
                    <section>
                        <h2 class="text-xs font-black uppercase tracking-[0.5em] text-[#1C69D4] mb-8">Trải nghiệm & Triết lý</h2>
                        <div class="prose prose-invert max-w-none text-zinc-400 leading-relaxed text-lg  font-light">
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
                            @php 
                                // Only show basic 4 specs in the sidebar for clean UI
                                $basicSpecs = array_intersect_key($vehicle->specifications, array_flip(['Engine', 'Horsepower', 'Torque', '0-60mph'])); 
                            @endphp
                            @foreach($basicSpecs as $key => $value)
                                <div class="group">
                                    <div class="flex justify-between items-baseline mb-2">
                                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600 group-hover:text-zinc-400 transition-colors">
                                            {{ \App\Models\Product::SPEC_TRANSLATIONS[$key] ?? $key }}
                                        </span>
                                        <span class="text-right text-sm font-black text-white uppercase tracking-wider">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                    </div>
                                    <div class="h-px bg-zinc-900 group-hover:bg-[#1C69D4] transition-all duration-500"></div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Modal Trigger Button -->
                        <button @click="showDetailedSpecs = true" 
                            class="w-full mt-10 py-4 border border-zinc-800 text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white hover:border-accent transition-all">
                            Thông số kỹ thuật chi tiết
                        </button>
                    </div>

                    <!-- Detailed Specs Modal -->
                    <div x-show="showDetailedSpecs" 
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                        style="display: none;">
                        
                        <!-- Backdrop with blur -->
                        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showDetailedSpecs = false"></div>

                        <!-- Modal Content -->
                        <div class="relative bg-zinc-950 border border-zinc-800 w-full max-w-2xl shadow-2xl overflow-hidden"
                            x-show="showDetailedSpecs"
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="translate-y-8"
                            x-transition:enter-end="translate-y-0">
                            
                            <div class="p-8 border-b border-zinc-900 flex justify-between items-center">
                                <h4 class="text-xl font-black uppercase tracking-wider text-white">
                                    Thông số <span class="text-accent italic">chi tiết</span>
                                </h4>
                                <button @click="showDetailedSpecs = false" class="text-zinc-500 hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-8 max-h-[70vh] overflow-y-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-zinc-800 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">
                                            <th class="py-4 font-black">Thông số kỹ thuật</th>
                                            <th class="py-4 font-black">Giá trị</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm font-medium text-white divide-y divide-zinc-900/50">
                                        @php
                                            $detailSpecs = [
                                                'Dài x Rộng x Cao' => $vehicle->specifications['Length_Width_Height'] ?? '...',
                                                'Chiều dài cơ sở' => $vehicle->specifications['Wheelbase'] ?? '...',
                                                'Trọng lượng không tải' => $vehicle->specifications['Curb_Weight'] ?? '...',
                                                'Động cơ' => $vehicle->specifications['Engine'] ?? '...',
                                                'Thể tích bình nhiên liệu' => $vehicle->specifications['Fuel_Tank_Cap'] ?? '...',
                                                'Công suất cực đại' => $vehicle->specifications['Max_Power_RPM'] ?? '...',
                                                'Mô-men xoắn cực đại' => $vehicle->specifications['Max_Torque_RPM'] ?? '...',
                                                'Dẫn động' => $vehicle->specifications['Drive_Type'] ?? '...',
                                                'Hộp số' => $vehicle->specifications['Transmission_Type'] ?? '...',
                                                '0 - 100 km/h' => $vehicle->specifications['Zero_To_Hundred'] ?? '...',
                                                'Tốc độ tối đa' => $vehicle->specifications['Top_Speed_KMH'] ?? '...',
                                            ];
                                        @endphp
                                        @foreach($detailSpecs as $label => $value)
                                        <tr class="group">
                                            <td class="py-4 text-zinc-400 group-hover:text-accent transition-colors">{{ $label }}</td>
                                            <td class="py-4 font-bold text-white">{{ $value }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-8 bg-zinc-900/50 border-t border-zinc-900">
                                <p class="text-[10px] uppercase text-zinc-500 tracking-widest leading-relaxed">
                                    * Thông số có thể thay đổi tùy theo phiên bản và điều kiện vận hành thực tế. Liên kết với đại lý để biết thêm chi tiết.
                                </p>
                            </div>
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
