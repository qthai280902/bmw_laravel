<x-app-layout>
    @section('title', $vehicle->name . ' - BMW Showroom')

    <div class="bg-zinc-950 pb-20">
        <!-- Hero Section: Premium Image Showcase -->
        <div class="relative h-[70vh] w-full overflow-hidden">
            @if($vehicle->primaryImage)
                @if(Str::startsWith($vehicle->primaryImage->path, 'http'))
                    <img src="{{ $vehicle->primaryImage->path }}" class="w-full h-full object-cover grayscale-[0.5] hover:grayscale-0 transition-all duration-1000 transform scale-105 hover:scale-100" alt="{{ $vehicle->name }}">
                @else
                    <img src="{{ Storage::url($vehicle->primaryImage->path) }}" class="w-full h-full object-cover grayscale-[0.5] hover:grayscale-0 transition-all duration-1000 transform scale-105 hover:scale-100" alt="{{ $vehicle->name }}">
                @endif
            @else
                <img src="https://placehold.co/1920x1080/111111/ffffff?text=No+Image" class="w-full h-full object-cover" alt="No Image">
            @endif
            
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
                                @if(Str::startsWith($image->path, 'http'))
                                    <img src="{{ $image->path }}" class="w-full h-full object-cover grayscale opacity-50 group-hover/thumb:grayscale-0 group-hover/thumb:opacity-100 transition-all duration-500">
                                @else
                                    <img src="{{ Storage::url($image->path) }}" class="w-full h-full object-cover grayscale opacity-50 group-hover/thumb:grayscale-0 group-hover/thumb:opacity-100 transition-all duration-500">
                                @endif
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
                            @foreach($vehicle->specifications as $key => $value)
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
                    </div>

                    <!-- Call to Action -->
                    <div class="space-y-4">
                        @auth
                            <button 
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'book-appointment')"
                                class="w-full bg-white text-black py-6 font-black uppercase tracking-[0.2em] text-xs hover:bg-[#1C69D4] hover:text-white transition-all shadow-xl">
                                Đặt lịch ngay
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="block w-full bg-zinc-800 text-white py-6 font-black uppercase tracking-[0.2em] text-xs text-center hover:bg-[#1C69D4] transition-all">
                                Đăng nhập để đặt lịch
                            </a>
                        @endauth

                        <form action="{{ route('cart.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $vehicle->id }}">
                            <button type="submit" class="w-full border border-zinc-800 text-white py-6 font-black uppercase tracking-[0.2em] text-xs hover:border-white transition-all">
                                Đặt cọc cấu hình này
                            </button>
                        </form>
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

    <!-- Appointment Modal -->
    <x-modal name="book-appointment" focusable>
        <div class="p-10 bg-zinc-950 border border-zinc-800">
            <h2 class="text-3xl font-black uppercase tracking-tighter text-white mb-6">
                Book an <span class="text-[#1C69D4]">Appointment</span>
            </h2>
            <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mb-10 italic">Yêu cầu lái thử, xem xe hoặc bảo dưỡng định kỳ cho dòng {{ $vehicle->name }}.</p>

            <form action="{{ route('appointments.store') }}" method="POST" class="space-y-8">
                @csrf
                <input type="hidden" name="product_id" value="{{ $vehicle->id }}">

                <div>
                    <x-input-label for="type" value="Loại hình dịch vụ" />
                    <select id="type" name="type" class="w-full bg-zinc-900 border-zinc-800 text-white font-black uppercase text-xs tracking-widest px-6 py-4 focus:border-[#1C69D4] focus:ring-0 rounded-none">
                        @foreach(\App\Enums\AppointmentType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="appointment_date" value="Thời gian mong muốn" />
                    <x-text-input id="appointment_date" name="appointment_date" type="datetime-local" class="block w-full" required />
                    <x-input-error :messages="$errors->get('appointment_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="Ghi chú thêm" />
                    <textarea id="notes" name="notes" rows="4" class="w-full bg-zinc-900 border-zinc-800 text-white font-medium text-sm px-6 py-4 focus:border-[#1C69D4] focus:ring-0 rounded-none placeholder-zinc-700" placeholder="Yêu cầu cụ thể của bạn..."></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="pt-4 flex justify-end gap-4">
                    <x-secondary-button x-on:click="$dispatch('close')">Hủy bỏ</x-secondary-button>
                    <x-primary-button>Xác nhận yêu cầu</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
