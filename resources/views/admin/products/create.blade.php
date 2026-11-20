<x-admin-layout>
    <x-admin.page-header
        eyebrow="Inventory control"
        title="Thêm"
        accent="Sản phẩm"
        description="Đăng ký cấu hình xe, Motorrad hoặc phụ kiện mới vào kho showroom."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Core Info -->
            <div class="lg:col-span-2 space-y-8">
                <x-admin.card class="!p-8">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400 mb-8 pb-4 border-b border-white/[0.08]">Cấu hình cơ bản</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Tên thương mại của sản phẩm</label>
                            <input type="text" name="name" value="{{ old('name') }}" required 
                                   class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3.5 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all placeholder-zinc-600"
                                   placeholder="Ví dụ: BMW M4 Competition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Dòng xe (BMW Line)</label>
                                <x-ui.custom-select
                                    name="category_id"
                                    :value="(string) old('category_id', '')"
                                    placeholder="Lựa chọn dòng xe..."
                                    :options="$categories->pluck('name', 'id')->toArray()"
                                    :show-error="true"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Phân loại phương tiện</label>
                                <x-ui.custom-select
                                    name="type"
                                    :value="(string) old('type', 'car')"
                                    :options="[
                                        'car' => 'Ô tô (Automobile)',
                                        'motorbike' => 'Xe máy (Motorrad)',
                                        'accessory' => 'Phụ kiện chính hãng',
                                    ]"
                                    :show-error="true"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold uppercase mb-2 tracking-widest text-emerald-400">Giá niêm yết (VNĐ)</label>
                                <input type="number" name="price" value="{{ old('price', 0) }}" required 
                                       class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all tabular-nums">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase mb-2 tracking-widest text-[#70A7FF]">Tiền đặt cọc (VNĐ)</label>
                                <input type="number" name="deposit_amount" value="{{ old('deposit_amount', 0) }}" required 
                                       class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3.5 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all tabular-nums">
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card class="!p-8">
                    <div class="flex justify-between items-center mb-8 pb-4 border-b border-white/[0.08]">
                        <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400">Thông số kỹ thuật nâng cao</h3>
                        <p class="text-[9px] font-bold text-zinc-500 uppercase">Dữ liệu định dạng Key-Value</p>
                    </div>

                    <div class="space-y-4" x-data="{ specs: [{key: '', value: ''}] }">
                        <template x-for="(spec, index) in specs" :key="index">
                            <div class="grid grid-cols-12 gap-3 items-center group">
                                <div class="col-span-1 rounded-lg border border-white/10 bg-[#070A10] h-full flex items-center justify-center">
                                    <span class="text-[10px] font-bold text-zinc-500" x-text="index + 1"></span>
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][key]'" x-model="spec.key" 
                                           placeholder="Thuộc tính (vd: Engine)" 
                                           class="w-full rounded-lg border border-white/10 bg-[#070A10] text-white text-xs px-4 py-3 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all">
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][value]'" x-model="spec.value" 
                                           placeholder="Giá trị (vd: 4.4L V8 M TwinPower)" 
                                           class="w-full rounded-lg border border-white/10 bg-[#070A10] text-white text-xs px-4 py-3 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all">
                                </div>
                                <div class="col-span-1 flex justify-center">
                                    <button type="button" @click="specs.splice(index, 1)" 
                                            class="rounded-lg p-2 text-zinc-500 hover:bg-rose-500/10 hover:text-rose-400 transition-colors cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        
                        <div class="pt-4">
                            <button type="button" @click="specs.push({key: '', value: ''})" 
                                    class="inline-flex items-center gap-2 rounded-xl text-[10px] font-semibold uppercase text-zinc-300 hover:text-white transition-all bg-[#070A10] px-4 py-2.5 border border-white/10 hover:border-[#1C69D4] cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Thêm trường thông số
                            </button>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <!-- Right Column: Media & Publish -->
            <div class="space-y-8">
                <x-admin.card class="!p-7">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400 mb-6 pb-4 border-b border-white/[0.08]">Media Assets</h3>
                    <div class="space-y-5">
                        <div>
                            <div class="relative group cursor-pointer rounded-2xl border-2 border-dashed border-white/15 bg-[#070A10] p-8 text-center hover:border-[#1C69D4]/60 transition-all duration-300 overflow-hidden">
                                <input type="file" name="new_images[]" multiple 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="relative z-0">
                                    <svg class="w-8 h-8 text-zinc-500 group-hover:text-[#70A7FF] mx-auto mb-3 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <p class="text-[10px] font-bold uppercase text-zinc-400 tracking-widest group-hover:text-white">Drop files or Browse</p>
                                    <p class="text-[9px] text-zinc-500 mt-1 uppercase font-semibold">BMW Visual Library</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl p-4 bg-[#070A10]/70 border border-white/[0.06]">
                            <p class="text-[10px] font-medium text-zinc-400 leading-relaxed">
                                * Ảnh đầu tiên trong danh sách tải lên sẽ được thiết lập làm Primary Visual cho xe.
                            </p>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card class="!p-7">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400 mb-6 pb-4 border-b border-white/[0.08]">Visibility & Stock</h3>
                    <div class="space-y-6">
                        <label class="flex items-center justify-between group cursor-pointer">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-300 group-hover:text-white transition-colors">Dòng xe tiêu biểu</span>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-800 border border-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-zinc-200 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1C69D4] peer-checked:border-[#1C69D4]"></div>
                            </div>
                        </label>
                        
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Số lượng khả dụng</label>
                            <input type="number" name="stock" value="{{ old('stock', 0) }}" 
                                   class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all tabular-nums">
                        </div>
                    </div>
                </x-admin.card>

                <div class="grid grid-cols-1 gap-3">
                    <button type="submit" class="rounded-xl py-4 bg-[#1C69D4] text-white font-semibold uppercase text-xs tracking-widest shadow-lg shadow-[#1C69D4]/25 hover:bg-[#2575e6] transition-colors text-center cursor-pointer">
                        Lưu cấu hình xe
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="rounded-xl py-3.5 bg-white/[0.03] border border-white/10 text-zinc-300 font-semibold uppercase text-xs tracking-widest text-center hover:text-white hover:border-white/25 hover:bg-white/[0.08] transition-all">
                        Hủy cấu hình
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
