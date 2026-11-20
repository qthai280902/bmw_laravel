<x-admin-layout>
    <x-admin.page-header
        eyebrow="Inventory control"
        title="Cập nhật"
        accent="Sản phẩm"
        :description="$product->name"
    >
        <x-slot name="actions">
            <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-[#1C69D4]/40 bg-[#1C69D4]/10 px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white">
                Xem trang public
            </a>
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.03] px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Quay lại
            </a>
        </x-slot>
    </x-admin.page-header>

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Core Info -->
            <div class="lg:col-span-2 space-y-8">
                <x-admin.card class="!p-8">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400 mb-8 pb-4 border-b border-white/[0.08]">Thông tin hiệu chỉnh</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Tên thương mại của sản phẩm</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required 
                                   class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3.5 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Dòng xe (BMW Line)</label>
                                <x-ui.custom-select
                                    name="category_id"
                                    :value="(string) old('category_id', $product->category_id)"
                                    :options="$categories->pluck('name', 'id')->toArray()"
                                    :show-error="true"
                                />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Phân loại phương tiện</label>
                                <x-ui.custom-select
                                    name="type"
                                    :value="old('type', $product->type->value)"
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
                                <input type="number" name="price" value="{{ old('price', $product->price) }}" required 
                                       class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all tabular-nums">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase mb-2 tracking-widest text-[#70A7FF]">Tiền đặt cọc (VNĐ)</label>
                                <input type="number" name="deposit_amount" value="{{ old('deposit_amount', $product->deposit_amount) }}" required 
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

                    @php
                        $specs = collect($product->specifications)->map(fn($v, $k) => ['key' => $k, 'value' => $v])->values()->toArray();
                    @endphp
                    <div class="space-y-4" x-data="{ specs: {{ json_encode(!empty($specs) ? $specs : [['key' => '', 'value' => '']]) }} }">
                        <template x-for="(spec, index) in specs" :key="index">
                            <div class="grid grid-cols-12 gap-3 items-center group">
                                <div class="col-span-1 rounded-lg border border-white/10 bg-[#070A10] h-full flex items-center justify-center">
                                    <span class="text-[10px] font-bold text-zinc-500" x-text="index + 1"></span>
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][key]'" x-model="spec.key" 
                                           placeholder="Thuộc tính" 
                                           class="w-full rounded-lg border border-white/10 bg-[#070A10] text-white text-xs px-4 py-3 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all">
                                </div>
                                <div class="col-span-5">
                                    <input type="text" :name="'specifications[' + index + '][value]'" x-model="spec.value" 
                                           placeholder="Giá trị" 
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

            <!-- Right Column: Media -->
            <div class="space-y-8">
                <x-admin.card class="!p-7">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400 mb-6 pb-4 border-b border-white/[0.08]">Media Library</h3>
                    
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        @foreach($product->images as $image)
                            <div class="relative group aspect-video rounded-xl bg-[#070A10] border border-white/10 overflow-hidden">
                                <img src="{{ $product->displayImageUrl($image) }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 {{ $image->is_primary ? '' : 'grayscale opacity-40 hover:opacity-100 hover:grayscale-0' }}">
                                @if($image->is_primary)
                                    <span class="absolute top-1.5 left-1.5 rounded-full px-2.5 py-0.5 bg-[#1C69D4] text-[8px] font-bold uppercase tracking-widest text-white">Primary Visual</span>
                                @endif
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-white">View Asset</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Bổ sung hình ảnh mới</label>
                        <input type="file" name="new_images[]" multiple 
                               class="w-full rounded-xl border border-white/10 bg-[#070A10] p-2.5 text-[11px] text-zinc-300 file:mr-4 file:rounded-lg file:py-2 file:px-4 file:border-0 file:text-[10px] file:font-bold file:uppercase file:bg-white file:text-black hover:file:bg-[#1C69D4] hover:file:text-white transition-all cursor-pointer">
                    </div>
                </x-admin.card>

                <x-admin.card class="!p-7">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-zinc-400 mb-6 pb-4 border-b border-white/[0.08]">Inventory Status</h3>
                    <div class="space-y-6">
                        <label class="flex items-center justify-between group cursor-pointer">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-300 group-hover:text-white transition-colors">Sản phẩm tiêu biểu</span>
                            <div class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" {{ $product->is_featured ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-800 border border-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-zinc-200 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1C69D4] peer-checked:border-[#1C69D4]"></div>
                            </div>
                        </label>
                        
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-2 tracking-widest">Số lượng tồn kho (Stock)</label>
                            <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" 
                                   class="w-full rounded-xl border border-white/10 bg-[#070A10] text-white text-base font-bold px-5 py-3 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] transition-all tabular-nums">
                        </div>
                    </div>
                </x-admin.card>

                <div class="grid grid-cols-1 gap-3">
                    <button type="submit" class="rounded-xl py-4 bg-[#1C69D4] text-white font-semibold uppercase text-xs tracking-widest shadow-lg shadow-[#1C69D4]/25 hover:bg-[#2575e6] transition-colors text-center cursor-pointer">
                        Cập nhật cấu hình
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="rounded-xl py-3.5 bg-white/[0.03] border border-white/10 text-zinc-300 font-semibold uppercase text-xs tracking-widest text-center hover:text-white hover:border-white/25 hover:bg-white/[0.08] transition-all">
                        Quay lại kho lưu trữ
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
