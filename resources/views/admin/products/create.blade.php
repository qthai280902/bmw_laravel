<x-admin-layout>
    <div class="mb-12">
        <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">Cấu hình <span class="font-black text-[#1C69D4]">Xe mới</span></h1>
        <p class="text-zinc-500 font-medium font-outfit">Đăng ký cấu hình xe mới vào kho lưu trữ hệ thống Showroom.</p>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Left Column: Core Info -->
            <div class="lg:col-span-2 space-y-8">
                <x-admin.card class="!p-10">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-10 pb-4 border-b border-zinc-800">Cấu hình cơ bản</h3>
                    
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Tên thương mại của sản phẩm</label>
                            <input type="text" name="name" value="{{ old('name') }}" required 
                                   class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all placeholder-zinc-800"
                                   placeholder="Ví dụ: BMW M4 Competition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Dòng xe (BMW Line)</label>
                                <select name="category_id" required 
                                        class="w-full bg-zinc-950 border-zinc-800 text-white text-sm px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all">
                                    <option value="">Lựa chọn dòng xe...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Phân loại phương tiện</label>
                                <select name="type" required 
                                        class="w-full bg-zinc-950 border-zinc-800 text-white text-sm px-6 py-4 focus:border-zinc-500 focus:ring-0 transition-all">
                                    <option value="car" {{ old('type') == 'car' ? 'selected' : '' }}>Ô tô (Automobile)</option>
                                    <option value="motorbike" {{ old('type') == 'motorbike' ? 'selected' : '' }}>Xe máy (Motorrad)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest text-emerald-500/80">Giá niêm yết (VNĐ)</label>
                                <input type="number" name="price" value="{{ old('price', 0) }}" required 
                                       class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-4 focus:border-emerald-500 focus:ring-0 transition-all tabular-nums">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest text-[#1C69D4]/80">Tiền đặt cọc (VNĐ)</label>
                                <input type="number" name="deposit_amount" value="{{ old('deposit_amount', 0) }}" required 
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

                    <div class="space-y-6" x-data="{ specs: [{key: '', value: ''}] }">
                        <template x-for="(spec, index) in specs" :key="index">
                            <div class="grid grid-cols-12 gap-4 items-center group">
                                <div class="col-span-1 border border-zinc-800 bg-zinc-950 h-full flex items-center justify-center">
                                    <span class="text-[10px] font-black text-zinc-800" x-text="index + 1"></span>
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][key]'" x-model="spec.key" 
                                           placeholder="Thuộc tính (vd: Engine)" 
                                           class="w-full bg-zinc-950 border-zinc-800 text-white text-xs px-4 py-3 focus:border-zinc-500 focus:ring-0 transition-all">
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][value]'" x-model="spec.value" 
                                           placeholder="Giá trị (vd: 4.4L V8 M TwinPower)" 
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

            <!-- Right Column: Media & Publish -->
            <div class="space-y-8">
                <x-admin.card class="!p-8">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-8 pb-4 border-b border-zinc-800">Media Assets</h3>
                    <div class="space-y-6">
                        <div>
                            <div class="relative group cursor-pointer border-2 border-dashed border-zinc-800 bg-zinc-950 p-10 text-center hover:border-zinc-500 transition-all duration-500 overflow-hidden">
                                <input type="file" name="new_images[]" multiple 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="relative z-0">
                                    <svg class="w-8 h-8 text-zinc-700 group-hover:text-[#1C69D4] mx-auto mb-4 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <p class="text-[10px] font-black uppercase text-zinc-500 tracking-widest group-hover:text-zinc-300">Drop files or Browse</p>
                                    <p class="text-[8px] text-zinc-800 mt-1 uppercase font-bold">BMW Visual Library</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-zinc-950/50 border border-zinc-900">
                            <p class="text-[9px] font-bold text-zinc-700 uppercase leading-relaxed ">
                                * Ảnh đầu tiên trong danh sách tải lên sẽ được thiết lập làm Primary Visual cho xe.
                            </p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card class="!p-8">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500 mb-8 pb-4 border-b border-zinc-800">Visibility & Stock</h3>
                    <div class="space-y-8">
                        <label class="flex items-center justify-between group cursor-pointer">
                            <span class="text-xs font-black uppercase tracking-wider text-zinc-400 group-hover:text-white transition-colors ">Dòng xe tiêu biểu</span>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-800 border border-zinc-700 peer-focus:outline-none rounded-none peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-zinc-300 after:rounded-none after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1C69D4] peer-checked:border-[#1C69D4]"></div>
                            </div>
                        </label>
                        
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-600 mb-2 tracking-widest">Số lượng khả dụng</label>
                            <input type="number" name="stock" value="{{ old('stock', 0) }}" 
                                   class="w-full bg-zinc-950 border-zinc-800 text-white text-base font-black px-6 py-3 focus:border-zinc-500 focus:ring-0 transition-all tabular-nums">
                        </div>
                    </div>
                </x-admin.card>

                <div class="grid grid-cols-1 gap-4">
                    <button type="submit" class="group relative py-5 bg-white text-black font-black uppercase text-sm tracking-widest shadow-2xl overflow-hidden text-center">
                        <span class="relative z-10">Lưu cấu hình xe</span>
                        <div class="absolute inset-0 bg-zinc-200 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="py-5 bg-zinc-900 border border-zinc-800 text-zinc-500 font-black uppercase text-sm tracking-widest text-center hover:text-white hover:bg-zinc-800 transition-all">
                        Hủy cấu hình
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
