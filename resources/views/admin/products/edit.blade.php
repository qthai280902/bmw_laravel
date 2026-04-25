<x-admin-layout>
    <div class="mb-12">
        <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">Cập nhật <span class="font-black text-[#1C69D4]">Cấu hình</span></h1>
        <p class="text-zinc-500 font-medium font-outfit">Hiệu chỉnh cấu hình xe: <span class="text-white font-black">{{ $product->name }}</span></p>
    </div>

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Left Column: Core Info -->
            <div class="lg:col-span-2 space-y-8">
                <x-admin.card class="!p-10">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-10 pb-4 border-b border-zinc-800">Thông tin hiệu chỉnh</h3>
                    
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Tên thương mại của sản phẩm</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required 
                                   class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Dòng xe (BMW Line)</label>
                                <select name="category_id" required 
                                        class="w-full bg-zinc-950 border-zinc-800 text-white text-sm px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Phân loại phương tiện</label>
                                <select name="type" required 
                                        class="w-full bg-zinc-950 border-zinc-800 text-white text-sm px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all">
                                    <option value="car" {{ old('type', $product->type->value) == 'car' ? 'selected' : '' }}>Ô tô (Automobile)</option>
                                    <option value="motorbike" {{ old('type', $product->type->value) == 'motorbike' ? 'selected' : '' }}>Xe máy (Motorrad)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest text-emerald-500/80">Giá niêm yết (VNĐ)</label>
                                <input type="number" name="price" value="{{ old('price', $product->price) }}" required 
                                       class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-4 focus:border-emerald-500 focus:ring-0 transition-all tabular-nums">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest text-[#1C69D4]/80">Tiền đặt cọc (VNĐ)</label>
                                <input type="number" name="deposit_amount" value="{{ old('deposit_amount', $product->deposit_amount) }}" required 
                                       class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-4 focus:border-[#1C69D4] focus:ring-0 transition-all tabular-nums">
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card class="!p-10">
                    <div class="flex justify-between items-center mb-10 pb-4 border-b border-zinc-800">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Thông số kỹ thuật nâng cao</h3>
                        <p class="text-[9px] font-bold text-zinc-700 uppercase ">Dữ liệu định dạng Key-Value</p>
                    </div>

                    @php
                        $specs = collect($product->specifications)->map(fn($v, $k) => ['key' => $k, 'value' => $v])->values()->toArray();
                    @endphp
                    <div class="space-y-6" x-data="{ specs: {{ json_encode(!empty($specs) ? $specs : [['key' => '', 'value' => '']]) }} }">
                        <template x-for="(spec, index) in specs" :key="index">
                            <div class="grid grid-cols-12 gap-4 items-center group">
                                <div class="col-span-1 border border-zinc-800 bg-zinc-950 h-full flex items-center justify-center">
                                    <span class="text-[10px] font-black text-zinc-800" x-text="index + 1"></span>
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][key]'" x-model="spec.key" 
                                           placeholder="Thuộc tính" 
                                           class="w-full bg-zinc-950 border-zinc-800 text-white text-xs px-4 py-3 focus:border-zinc-500 focus:ring-0 transition-all">
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][value]'" x-model="spec.value" 
                                           placeholder="Giá trị" 
                                           class="w-full bg-zinc-950 border-zinc-800 text-white text-xs px-4 py-3 focus:border-zinc-500 focus:ring-0 transition-all">
                                </div>
                                <div class="col-span-1 flex justify-center">
                                    <button type="button" @click="specs.splice(index, 1)" 
                                            class="p-2 text-zinc-800 hover:text-rose-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        
                        <div class="pt-4">
                            <button type="button" @click="specs.push({key: '', value: ''})" 
                                    class="inline-flex items-center gap-2 text-[10px] font-black uppercase text-zinc-500 hover:text-white transition-all bg-zinc-900 px-4 py-2 border border-zinc-800 hover:border-zinc-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Thêm trường thông số
                            </button>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <!-- Right Column: Media -->
            <div class="space-y-8">
                <x-admin.card class="!p-8">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-8 pb-4 border-b border-zinc-800">Media Library</h3>
                    
                    <div class="grid grid-cols-2 gap-3 mb-8">
                        @foreach($product->images as $image)
                            <div class="relative group aspect-video bg-black border border-zinc-800 overflow-hidden">
                                <img src="{{ $image ? (Str::startsWith($image->path, 'http') ? $image->path : Storage::url($image->path)) : 'https://placehold.co/800x600/111111/ffffff?text=BMW' }}" 
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 {{ $image->is_primary ? '' : 'grayscale opacity-30 hover:opacity-100 hover:grayscale-0' }}">
                                @if($image->is_primary)
                                    <span class="absolute top-0 left-0 px-2 py-0.5 bg-[#1C69D4] text-[8px] font-black uppercase tracking-widest text-white ">Primary Visual</span>
                                @endif
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-[8px] font-black uppercase tracking-tighter text-white">View Asset</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Bổ sung hình ảnh mới</label>
                        <input type="file" name="new_images[]" multiple 
                               class="w-full text-[10px] text-zinc-500 file:mr-4 file:py-2 file:px-4 file:border file:border-zinc-800 file:text-[10px] file:font-black file:uppercase file:bg-zinc-950 file:text-zinc-400 hover:file:bg-zinc-800 hover:file:text-white transition-all">
                    </div>
                </x-admin.card>

                <x-admin.card class="!p-8">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-8 pb-4 border-b border-zinc-800">Inventory Status</h3>
                    <div class="space-y-8">
                        <label class="flex items-center justify-between group cursor-pointer">
                            <span class="text-xs font-black uppercase tracking-wider text-zinc-400 group-hover:text-white transition-colors ">Sản phẩm tiêu biểu</span>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" {{ $product->is_featured ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-800 border border-zinc-700 peer-focus:outline-none rounded-none peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-zinc-300 after:rounded-none after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1C69D4] peer-checked:border-[#1C69D4]"></div>
                            </div>
                        </label>
                        
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Số lượng tồn kho (Stock)</label>
                            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" 
                                   class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-3 focus:border-zinc-500 focus:ring-0 transition-all tabular-nums">
                        </div>
                    </div>
                </x-admin.card>

                <div class="grid grid-cols-1 gap-4">
                    <button type="submit" class="group relative py-5 bg-white text-black font-black uppercase text-sm tracking-widest shadow-2xl overflow-hidden text-center">
                        <span class="relative z-10">Cập nhật cấu hình</span>
                        <div class="absolute inset-0 bg-zinc-200 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="py-5 bg-zinc-900 border border-zinc-800 text-zinc-500 font-black uppercase text-sm tracking-widest text-center hover:text-white hover:bg-zinc-800 transition-all">
                        Quay lại kho lưu trữ
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
