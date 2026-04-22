<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Bản đối chiếu <span class="text-accent underline decoration-4">Thông số</span>
                </h2>
                <p class="text-zinc-500 mt-2 uppercase text-xs tracking-widest font-bold">So sánh hiệu năng & tiện nghi</p>
            </div>
            <a href="{{ route('products.index') }}" class="bg-zinc-900 text-white text-[10px] font-black uppercase tracking-widest px-8 py-4 hover:bg-white hover:text-black transition-all">
                Quay lại Catalogue
            </a>
        </div>
    </x-slot>

    <div class="py-20 bg-zinc-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            @if($products->isEmpty())
                <div class="text-center py-20 border border-zinc-900">
                    <p class="text-zinc-500 uppercase text-xs font-black tracking-widest mb-4">Chưa có xe nào được chọn để so sánh</p>
                    <a href="{{ route('products.index') }}" class="text-accent uppercase text-xs font-black tracking-widest border-b-2 border-accent pb-1">Quay lại chọn xe</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <!-- Main Header (Images & Names) -->
                        <thead>
                            <tr>
                                <th class="p-8 text-left border-b border-zinc-900 bg-zinc-950/50 min-w-[250px]">
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-accent">Thông số so sánh</span>
                                </th>
                                @foreach($products as $product)
                                    <th class="p-8 border-b border-zinc-900 bg-zinc-950 min-w-[300px]">
                                        <div class="space-y-6">
                                            @if($product->primaryImage)
                                                @if(Str::startsWith($product->primaryImage->path, 'http'))
                                                    <img src="{{ $product->primaryImage->path }}" class="w-full h-40 object-cover grayscale hover:grayscale-0 transition-all duration-700">
                                                @else
                                                    <img src="{{ Storage::url($product->primaryImage->path) }}" class="w-full h-40 object-cover grayscale hover:grayscale-0 transition-all duration-700">
                                                @endif
                                            @else
                                                <div class="w-full h-40 bg-zinc-900"></div>
                                            @endif
                                            <div class="text-center">
                                                <p class="text-[10px] font-black text-accent uppercase tracking-widest">{{ $product->brand->name }}</p>
                                                <h3 class="text-xl font-black text-white uppercase tracking-tighter">{{ $product->name }}</h3>
                                                <p class="text-lg font-black text-white mt-2">{{ number_format($product->price) }} <span class="text-xs text-zinc-500">VNĐ</span></p>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <!-- Comparison Matrix -->
                        <tbody>
                            @foreach($matrix as $specKey => $values)
                                <tr class="group hover:bg-zinc-900/30 transition-colors">
                                    <td class="p-6 border-b border-zinc-900/50">
                                        <span class="text-xs font-black text-zinc-400 uppercase tracking-widest group-hover:text-white transition-colors">
                                            {{ Str::headline($specKey) }}
                                        </span>
                                    </td>
                                    @foreach($values as $value)
                                        <td class="p-6 border-b border-zinc-900/50 text-center">
                                            <span class="text-sm font-bold text-zinc-300">
                                                {{ is_array($value) ? implode(', ', $value) : $value }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>

                        <!-- Bottom Actions -->
                        <tfoot>
                            <tr>
                                <td class="p-8 border-t border-zinc-900"></td>
                                @foreach($products as $product)
                                    <td class="p-8 border-t border-zinc-900 text-center">
                                        <div class="space-y-4">
                                            <a href="{{ route('products.show', $product->slug) }}" 
                                                class="block w-full border border-white text-white text-[10px] font-black uppercase tracking-widest py-4 hover:bg-white hover:text-black transition-all">
                                                Xem chi tiết
                                            </a>
                                            <button onclick="toggleComparison({{ $product->id }}); window.location.reload();" 
                                                class="text-[10px] font-black uppercase tracking-widest text-zinc-600 hover:text-accent">
                                                Bỏ so sánh
                                            </button>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
