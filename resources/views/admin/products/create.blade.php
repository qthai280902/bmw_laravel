<x-admin-layout>
    <div class="mb-12">
        <h1 class="text-6xl font-light uppercase tracking-tighter mb-2">New <span class="font-black text-[#1C69D4]">Vehicle</span></h1>
        <p class="text-zinc-500 font-medium font-outfit">Đăng ký cấu hình xe mới vào Showroom.</p>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Left Column: Core Info -->
            <div class="lg:col-span-2 space-y-12">
                <x-admin.card>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#1C69D4] mb-8">Thông tin cơ bản</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Tên xe</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Hãng xe</label>
                                <select name="brand_id" required class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                                    <option value="">Chọn hãng...</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Loại xe</label>
                                <select name="type" required class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                                    <option value="car" {{ old('type') == 'car' ? 'selected' : '' }}>Ô tô</option>
                                    <option value="motorbike" {{ old('type') == 'motorbike' ? 'selected' : '' }}>Xe máy</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Giá niêm yết (VNĐ)</label>
                                <input type="number" name="price" value="{{ old('price', 0) }}" required class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Tiền đặt cọc (VNĐ)</label>
                                <input type="number" name="deposit_amount" value="{{ old('deposit_amount', 0) }}" required class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#1C69D4] mb-8">Thông số kỹ thuật (JSON)</h3>
                    <div class="space-y-4">
                        <p class="text-[10px] font-bold text-zinc-500 uppercase">Gợi ý: Công suất, Mô-men xoắn, Động cơ, Gia tốc...</p>
                        <!-- Dynamic specification inputs (Simplified for now as key-value) -->
                        <div x-data="{ specs: [{key: '', value: ''}] }">
                            <template x-for="(spec, index) in specs" :key="index">
                                <div class="flex gap-4 mb-4">
                                    <input type="text" :name="'specifications[' + index + '][key]'" x-model="spec.key" placeholder="Thuộc tính (vd: Engine)" class="flex-1 bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                                    <input type="text" :name="'specifications[' + index + '][value]'" x-model="spec.value" placeholder="Giá trị (vd: 4.4L V8)" class="flex-1 bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                                    <button type="button" @click="specs.splice(index, 1)" class="text-zinc-600 hover:text-rose-500 font-bold uppercase text-[10px]">Xóa</button>
                                </div>
                            </template>
                            <button type="button" @click="specs.push({key: '', value: ''})" class="text-[10px] font-black uppercase text-[#1C69D4] hover:text-white transition-colors">+ Thêm thông số</button>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <!-- Right Column: Media & Publish -->
            <div class="space-y-12">
                <x-admin.card>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#1C69D4] mb-8">Hình ảnh</h3>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Tải ảnh lên</label>
                            <input type="file" name="new_images[]" multiple class="w-full text-xs text-zinc-500 file:mr-4 file:py-2 file:px-4 file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-zinc-800 file:text-white hover:file:bg-zinc-700">
                        </div>
                        <div class="p-4 bg-black border border-dashed border-zinc-800 text-center">
                            <p class="text-[10px] font-bold text-zinc-600 uppercase">Ảnh đầu tiên sẽ được chọn làm ảnh đại diện</p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#1C69D4] mb-8">Trạng thái</h3>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-bold text-zinc-400">Sản phẩm nổi bật</label>
                            <input type="checkbox" name="is_featured" value="1" class="rounded-none bg-black border-zinc-800 text-[#1C69D4] focus:ring-0">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2">Số lượng tồn kho</label>
                            <input type="number" name="stock" value="{{ old('stock', 0) }}" class="w-full bg-black border-zinc-800 text-white text-sm focus:border-[#1C69D4] focus:ring-0">
                        </div>
                    </div>
                </x-admin.card>

                <div class="space-y-4">
                    <button type="submit" class="w-full py-4 bg-[#1C69D4] text-white font-black uppercase text-sm tracking-widest hover:bg-blue-600 transition-colors shadow-2xl">
                        Lưu cấu hình xe
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="block w-full py-4 bg-zinc-900 text-zinc-500 font-black uppercase text-sm tracking-widest text-center hover:text-white transition-colors">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
