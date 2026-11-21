@php
    $product = $product ?? null;
    $types = $types ?? \App\Enums\VehicleType::cases();
    $submitLabel = $submitLabel ?? 'Lưu thay đổi';
    $isEdit = $product !== null;

    $initialSpecs = old('specifications', old('specs'));
    if (! is_array($initialSpecs)) {
        $initialSpecs = [];
        $rawSpecs = $product?->specifications ?? $product?->specs;
        if (is_array($rawSpecs) && count($rawSpecs) > 0) {
            foreach ($rawSpecs as $k => $v) {
                if (is_array($v) && isset($v['key'])) {
                    $initialSpecs[] = ['key' => (string) $v['key'], 'value' => (string) ($v['value'] ?? '')];
                } else {
                    $initialSpecs[] = ['key' => (string) $k, 'value' => (string) $v];
                }
            }
        }
    } else {
        $normalizedOld = [];
        foreach ($initialSpecs as $item) {
            if (is_array($item)) {
                $normalizedOld[] = [
                    'key' => (string) ($item['key'] ?? ''),
                    'value' => (string) ($item['value'] ?? ''),
                ];
            }
        }
        $initialSpecs = $normalizedOld;
    }

    if (empty($initialSpecs)) {
        $initialSpecs = [
            ['key' => 'engine', 'value' => ''],
            ['key' => 'power', 'value' => ''],
            ['key' => 'acceleration', 'value' => ''],
        ];
    }

    $currentTypeVal = old('type', $isEdit ? ($product->type instanceof \BackedEnum ? $product->type->value : (string) $product->type) : ($types[0]->value ?? 'car'));
    $initialPrice = (int) preg_replace('/[^\d]/', '', (string) old('price', $product?->price ?? 0));
    $initialDeposit = (int) preg_replace('/[^\d]/', '', (string) old('deposit_amount', $product?->deposit_amount ?? 50000000));
    $existingImages = $isEdit ? $product->images : collect();
    $initialPrimaryId = old('primary_image_id', $existingImages->firstWhere('is_primary', true)?->id ?? $existingImages->first()?->id);

    $specDictionary = [
        'engine' => 'Động cơ',
        'power' => 'Công suất cực đại',
        'torque' => 'Mô-men xoắn cực đại',
        'acceleration' => 'Tăng tốc (0–100 km/h)',
        'top_speed' => 'Tốc độ tối đa',
        'transmission' => 'Hộp số',
        'drivetrain' => 'Hệ dẫn động',
        'fuel_consumption' => 'Tiêu hao nhiên liệu',
        'battery' => 'Dung lượng pin',
        'range' => 'Tầm hoạt động (WLTP)',
        'weight' => 'Trọng lượng không tải',
        'seat_height' => 'Chiều cao yên xe',
        'material' => 'Chất liệu chế tác',
        'compatibility' => 'Tương thích dòng xe',
        'warranty' => 'Chế độ bảo hành',
        'origin' => 'Xuất xứ chính hãng',
    ];

    $presetTemplates = [
        'car' => [
            ['key' => 'engine', 'placeholder' => '2.0L TwinPower Turbo I4'],
            ['key' => 'power', 'placeholder' => '258 mã lực @ 5.000–6.500 vòng/phút'],
            ['key' => 'torque', 'placeholder' => '400 Nm @ 1.550–4.400 vòng/phút'],
            ['key' => 'acceleration', 'placeholder' => '5,8 giây (0–100 km/h)'],
            ['key' => 'top_speed', 'placeholder' => '250 km/h'],
            ['key' => 'transmission', 'placeholder' => '8 cấp Steptronic Sport'],
            ['key' => 'drivetrain', 'placeholder' => 'Cầu sau (RWD) / xDrive AWD'],
        ],
        'motorbike' => [
            ['key' => 'engine', 'placeholder' => 'Boxer 2 xi-lanh 1.300 cc ShiftCam'],
            ['key' => 'power', 'placeholder' => '145 mã lực @ 7.750 vòng/phút'],
            ['key' => 'torque', 'placeholder' => '149 Nm @ 6.500 vòng/phút'],
            ['key' => 'seat_height', 'placeholder' => '850 mm'],
            ['key' => 'weight', 'placeholder' => '237 kg (đầy nhiên liệu)'],
            ['key' => 'top_speed', 'placeholder' => 'Trên 200 km/h'],
        ],
        'accessory' => [
            ['key' => 'material', 'placeholder' => 'Sợi Carbon M Performance / Cao su TPE cao cấp'],
            ['key' => 'compatibility', 'placeholder' => 'BMW 3 Series (G20), 5 Series (G30/G60), X5 (G05)'],
            ['key' => 'warranty', 'placeholder' => '24 tháng chính hãng BMW Vietnam'],
            ['key' => 'origin', 'placeholder' => 'Munich, CHLB Đức'],
        ],
    ];
@endphp

<div
    x-data="{
        name: @js(old('name', $product?->name ?? '')),
        slug: @js(old('slug', $product?->slug ?? '')),
        selectedType: @js($currentTypeVal),
        priceRaw: @js($initialPrice),
        priceFormatted: '',
        depositRaw: @js($initialDeposit),
        depositFormatted: '',
        specs: @js($initialSpecs),
        specDictionary: @js($specDictionary),
        presetTemplates: @js($presetTemplates),
        primaryImageId: @js($initialPrimaryId ? (int) $initialPrimaryId : null),
        deletedImageIds: [],
        newImagePreviews: [],
        isDragging: false,
        isDirty: false,

        init() {
            this.priceFormatted = this.formatDots(this.priceRaw);
            this.depositFormatted = this.formatDots(this.depositRaw);
        },

        markDirty() {
            this.isDirty = true;
        },

        generateSlug() {
            this.markDirty();
            this.slug = this.name
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        },

        formatDots(val) {
            const digits = String(val ?? '').replace(/[^\d]/g, '');
            if (digits === '') {
                return '0';
            }
            const num = parseInt(digits, 10) || 0;
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        onPriceInput(event) {
            this.markDirty();
            const digits = String(event.target.value || '').replace(/[^\d]/g, '');
            this.priceRaw = digits === '' ? 0 : parseInt(digits, 10);
            this.priceFormatted = this.formatDots(this.priceRaw);
        },

        onDepositInput(event) {
            this.markDirty();
            const digits = String(event.target.value || '').replace(/[^\d]/g, '');
            this.depositRaw = digits === '' ? 0 : parseInt(digits, 10);
            this.depositFormatted = this.formatDots(this.depositRaw);
        },

        approxVndText(amount) {
            const n = Number(amount) || 0;
            if (n <= 0) {
                return '0 VNĐ';
            }
            if (n >= 1000000000) {
                const billions = (n / 1000000000).toFixed(2).replace(/\.?0+$/, '').replace('.', ',');
                return `≈ ${billions} tỷ VNĐ`;
            }
            if (n >= 1000000) {
                const millions = (n / 1000000).toFixed(1).replace(/\.0$/, '').replace('.', ',');
                return `≈ ${millions} triệu VNĐ`;
            }
            return `≈ ${this.formatDots(n)} VNĐ`;
        },

        specLabel(key) {
            const k = String(key || '').trim().toLowerCase();
            return this.specDictionary[k] || (key ? 'Thông số tùy chỉnh' : 'Chọn hoặc nhập mã thông số');
        },

        addSpec(key = '', value = '') {
            this.markDirty();
            this.specs.push({ key, value });
        },

        removeSpec(index) {
            this.markDirty();
            this.specs.splice(index, 1);
            if (this.specs.length === 0) {
                this.specs.push({ key: '', value: '' });
            }
        },

        applyPresetSpecs() {
            this.markDirty();
            const template = this.presetTemplates[this.selectedType] || this.presetTemplates.car;
            const existingKeys = new Set(this.specs.map((s) => String(s.key || '').trim().toLowerCase()).filter(Boolean));
            this.specs = this.specs.filter((s) => String(s.key || '').trim() !== '' || String(s.value || '').trim() !== '');

            template.forEach((item) => {
                if (!existingKeys.has(item.key)) {
                    this.specs.push({ key: item.key, value: '' });
                }
            });

            if (this.specs.length === 0) {
                this.specs.push({ key: 'engine', value: '' });
            }
        },

        setPrimaryImage(imageId) {
            if (this.isImageDeleted(imageId)) {
                return;
            }
            this.markDirty();
            this.primaryImageId = imageId;
        },

        isImageDeleted(imageId) {
            return this.deletedImageIds.includes(imageId);
        },

        toggleDeleteImage(imageId) {
            this.markDirty();
            if (this.isImageDeleted(imageId)) {
                this.deletedImageIds = this.deletedImageIds.filter((id) => id !== imageId);
            } else {
                this.deletedImageIds.push(imageId);
            }
        },

        handleFilesSelected(event) {
            this.markDirty();
            const files = Array.from(event.target.files || []);
            this.buildPreviews(files);
        },

        handleDrop(event) {
            this.isDragging = false;
            this.markDirty();
            const droppedFiles = Array.from(event.dataTransfer?.files || []).filter((f) => f.type.startsWith('image/'));
            if (droppedFiles.length === 0 || !this.$refs.fileInput) {
                return;
            }
            const dt = new DataTransfer();
            droppedFiles.forEach((file) => dt.items.add(file));
            this.$refs.fileInput.files = dt.files;
            this.buildPreviews(droppedFiles);
        },

        buildPreviews(files) {
            this.newImagePreviews.forEach((p) => {
                if (p.url) {
                    URL.revokeObjectURL(p.url);
                }
            });
            this.newImagePreviews = files.map((f) => ({
                name: f.name,
                sizeKb: Math.max(1, Math.round(f.size / 1024)),
                url: URL.createObjectURL(f),
            }));
        },

        clearNewFiles() {
            this.newImagePreviews.forEach((p) => {
                if (p.url) {
                    URL.revokeObjectURL(p.url);
                }
            });
            this.newImagePreviews = [];
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    }"
    @input="markDirty()"
    class="space-y-8"
>
    <datalist id="bmw-spec-keys">
        @foreach($specDictionary as $specKey => $specName)
            <option value="{{ $specKey }}">{{ $specName }}</option>
        @endforeach
    </datalist>

    {{-- Khối 1: Thông tin định danh & Giá niêm yết --}}
    <x-admin.card>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-2 border-b border-white/[0.07] pb-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#70A7FF]">1. Thông tin cơ bản &amp; Định giá</p>
                <h2 class="mt-1 text-base font-bold text-white">Định danh sản phẩm, phân loại &amp; cấu hình tài chính</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Tên sản phẩm (Xe / Phụ kiện) *</label>
                <input
                    type="text"
                    name="name"
                    x-model="name"
                    @input="generateSlug()"
                    required
                    placeholder="Ví dụ: BMW 530i M Sport LCI"
                    class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm font-bold text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </div>

            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Slug (Đường dẫn tĩnh URL) *</label>
                <input
                    type="text"
                    name="slug"
                    x-model="slug"
                    required
                    class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm font-medium text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </div>

            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Dòng sản phẩm (Category) *</label>
                <select
                    name="category_id"
                    required
                    class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm font-bold text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
                    <option value="">-- Chọn Dòng Sản Phẩm --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $product?->category_id) === (int) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Phân loại sản phẩm *</label>
                <select
                    name="type"
                    x-model="selectedType"
                    required
                    class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm font-bold text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
                    @foreach($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Giá bán niêm yết có dấu chấm phân tách + đọc chữ --}}
            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Giá bán niêm yết (VNĐ) *</label>
                <input type="hidden" name="price" :value="priceRaw">
                <div class="relative">
                    <input
                        type="text"
                        inputmode="numeric"
                        data-currency-display="price"
                        :value="priceFormatted"
                        @input="onPriceInput($event)"
                        required
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] py-3 pl-4 pr-14 text-sm font-bold tabular-nums text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    >
                    <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-zinc-500">VNĐ</span>
                </div>
                <p class="mt-1.5 text-xs font-semibold text-[#70A7FF]" x-text="approxVndText(priceRaw)"></p>
            </div>

            {{-- Tiền đặt cọc có dấu chấm phân tách + đọc chữ --}}
            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Tiền đặt cọc tiêu chuẩn (VNĐ)</label>
                <input type="hidden" name="deposit_amount" :value="depositRaw">
                <div class="relative">
                    <input
                        type="text"
                        inputmode="numeric"
                        data-currency-display="deposit_amount"
                        :value="depositFormatted"
                        @input="onDepositInput($event)"
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] py-3 pl-4 pr-14 text-sm font-bold tabular-nums text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    >
                    <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-zinc-500">VNĐ</span>
                </div>
                <p class="mt-1.5 text-xs font-semibold text-emerald-400" x-text="approxVndText(depositRaw)"></p>
            </div>

            <div>
                <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Số lượng tồn kho *</label>
                <input
                    type="number"
                    name="stock"
                    value="{{ old('stock', $product?->stock ?? 1) }}"
                    min="0"
                    required
                    class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm font-bold tabular-nums text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </div>

            <div class="flex items-center pt-6">
                <label class="inline-flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-[#070A10] px-4 py-3">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $product ? $product->is_active : true))
                        class="h-5 w-5 rounded border-white/20 bg-[#0D121C] text-[#1C69D4] focus:ring-[#1C69D4]"
                    >
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-200">Hiển thị công khai trên Showroom</span>
                </label>
            </div>
        </div>

        <div class="mt-6">
            <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Mô tả chi tiết sản phẩm</label>
            <textarea
                name="description"
                rows="4"
                placeholder="Giới thiệu thiết kế, công nghệ nổi bật và đặc quyền dành cho khách hàng..."
                class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
            >{{ old('description', $product?->description) }}</textarea>
        </div>
    </x-admin.card>

    {{-- Khối 2: Bảng Thông Số Kỹ Thuật Tiếng Việt Trực Quan --}}
    <x-admin.card>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.07] pb-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#70A7FF]">2. Bảng Thông Số Kỹ Thuật</p>
                <h2 class="mt-1 text-base font-bold text-white">Cấu hình thông số hiển thị trên trang Chi tiết &amp; So sánh xe</h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    data-testid="apply-bmw-preset-specs"
                    @click="applyPresetSpecs()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-3.5 text-xs font-semibold text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                >
                    <span>+ Thêm nhanh thông số chuẩn BMW</span>
                </button>
                <button
                    type="button"
                    @click="addSpec()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/10 bg-[#131A29] px-3.5 text-xs font-semibold text-zinc-200 transition-colors hover:border-white/25 hover:text-white cursor-pointer"
                >
                    <span>+ Thêm dòng</span>
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <template x-for="(spec, index) in specs" :key="index">
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-white/[0.06] bg-[#070A10]/70 p-3.5 md:grid-cols-[240px_1fr_auto] md:items-center">
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-[11px] font-bold text-[#70A7FF]" x-text="specLabel(spec.key)"></span>
                        </div>
                        <input
                            type="text"
                            list="bmw-spec-keys"
                            :name="`specifications[${index}][key]`"
                            x-model="spec.key"
                            placeholder="Mã thông số (vd: engine, power)"
                            class="w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 py-2.5 text-xs font-semibold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                    </div>

                    <div>
                        <div class="mb-1">
                            <span class="text-[11px] font-medium text-zinc-400">Giá trị hiển thị tiếng Việt</span>
                        </div>
                        <input
                            type="text"
                            :name="`specifications[${index}][value]`"
                            x-model="spec.value"
                            placeholder="Nhập thông số (ví dụ: 258 mã lực, 5,8 giây, 8 cấp Steptronic...)"
                            class="w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 py-2.5 text-xs font-bold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        >
                    </div>

                    <div class="flex items-end justify-end pt-4 md:pt-5">
                        <button
                            type="button"
                            @click="removeSpec(index)"
                            class="inline-flex h-9 items-center justify-center rounded-xl border border-rose-500/25 bg-rose-500/10 px-3 text-xs font-semibold text-rose-300 transition-colors hover:bg-rose-500/20 cursor-pointer"
                        >
                            Xóa
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-admin.card>

    {{-- Khối 3: Quản Lý Thư Viện Ảnh (Media Library & Drag-Drop Upload) --}}
    <x-admin.card>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-2 border-b border-white/[0.07] pb-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#70A7FF]">3. Thư Viện Hình Ảnh (Media Library)</p>
                <h2 class="mt-1 text-base font-bold text-white">Chọn ảnh đại diện chính, xóa ảnh cũ &amp; kéo thả tải ảnh mới</h2>
            </div>
        </div>

        @if($isEdit && $existingImages->count() > 0)
            <input type="hidden" name="primary_image_id" :value="primaryImageId">

            <template x-for="delId in deletedImageIds" :key="'del-' + delId">
                <input type="hidden" name="delete_image_ids[]" :value="delId">
            </template>

            <div class="mb-6">
                <p class="mb-3 text-xs font-semibold text-zinc-300">
                    Ảnh hiện có ({{ $existingImages->count() }} ảnh) — Bấm <strong>"Đặt làm ảnh chính"</strong> để đổi ảnh đại diện hoặc <strong>"Xóa ảnh"</strong> để gỡ khi lưu:
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($existingImages as $image)
                        <div
                            data-existing-image-card="{{ $image->id }}"
                            :class="isImageDeleted({{ $image->id }})
                                ? 'border-rose-500/50 bg-rose-950/20 opacity-50'
                                : (primaryImageId === {{ $image->id }} ? 'border-[#1C69D4] ring-1 ring-[#1C69D4] bg-[#070A10]' : 'border-white/10 bg-[#070A10]')"
                            class="relative flex flex-col justify-between overflow-hidden rounded-2xl border p-3 transition-all"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-black">
                                <img src="{{ $image->url() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                <span
                                    x-show="primaryImageId === {{ $image->id }} && !isImageDeleted({{ $image->id }})"
                                    class="absolute left-2.5 top-2.5 rounded-full bg-[#1C69D4] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow"
                                >
                                    Ảnh chính
                                </span>
                                <span
                                    x-show="isImageDeleted({{ $image->id }})"
                                    x-cloak
                                    class="absolute inset-0 flex items-center justify-center bg-rose-950/80 text-xs font-bold uppercase tracking-wider text-rose-200"
                                >
                                    Sẽ xóa khi lưu
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2">
                                <button
                                    type="button"
                                    @click="setPrimaryImage({{ $image->id }})"
                                    :disabled="isImageDeleted({{ $image->id }})"
                                    :class="primaryImageId === {{ $image->id }}
                                        ? 'border-[#1C69D4]/40 bg-[#1C69D4]/20 text-[#70A7FF]'
                                        : 'border-white/10 bg-[#131A29] text-zinc-200 hover:border-[#1C69D4] hover:text-white'"
                                    class="flex-1 rounded-xl border px-2.5 py-2 text-[11px] font-semibold transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                    x-text="primaryImageId === {{ $image->id }} ? 'Đang là ảnh chính' : 'Đặt làm ảnh chính'"
                                ></button>

                                <button
                                    type="button"
                                    @click="toggleDeleteImage({{ $image->id }})"
                                    :class="isImageDeleted({{ $image->id }})
                                        ? 'border-amber-500/40 bg-amber-500/20 text-amber-300'
                                        : 'border-rose-500/30 bg-rose-500/10 text-rose-300 hover:bg-rose-500/25'"
                                    class="rounded-xl border px-3 py-2 text-[11px] font-semibold transition-colors cursor-pointer"
                                    x-text="isImageDeleted({{ $image->id }}) ? 'Hoàn tác' : 'Xóa ảnh'"
                                ></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Vùng Kéo Thả (Drag & Drop Upload Zone) --}}
        <div
            data-testid="product-image-dropzone"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
            :class="isDragging ? 'border-[#1C69D4] bg-[#1C69D4]/10' : 'border-white/15 bg-[#070A10]/70 hover:border-[#1C69D4]/60'"
            class="relative rounded-2xl border-2 border-dashed p-6 text-center transition-colors"
        >
            <input
                x-ref="fileInput"
                type="file"
                name="images[]"
                multiple
                accept="image/jpeg,image/png,image/webp,image/avif"
                @change="handleFilesSelected($event)"
                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
            >
            <div class="mx-auto max-w-md space-y-2">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl border border-[#1C69D4]/30 bg-[#1C69D4]/15 text-[#70A7FF]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-white">Kéo thả hình ảnh vào đây hoặc nhấp để chọn tệp từ máy tính</p>
                <p class="text-xs text-zinc-400">Hỗ trợ JPG, PNG, WebP, AVIF (Tối đa 5MB/ảnh · Hệ thống tự động chuẩn hóa WebP).</p>
            </div>
        </div>

        {{-- Xem trước ảnh mới chọn trước khi bấm Lưu --}}
        <div x-show="newImagePreviews.length > 0" x-cloak class="mt-5 space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-emerald-400" x-text="`Đã chọn ${newImagePreviews.length} ảnh mới sẵn sàng tải lên:`"></p>
                <button
                    type="button"
                    @click="clearNewFiles()"
                    class="rounded-xl border border-white/10 bg-[#131A29] px-3 py-1.5 text-xs font-semibold text-zinc-300 hover:border-rose-500/40 hover:text-rose-300 cursor-pointer"
                >
                    Hủy chọn ảnh mới
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <template x-for="(preview, pIdx) in newImagePreviews" :key="pIdx">
                    <div class="overflow-hidden rounded-xl border border-emerald-500/30 bg-[#070A10] p-2">
                        <div class="aspect-[4/3] overflow-hidden rounded-lg bg-black">
                            <img :src="preview.url" :alt="preview.name" class="h-full w-full object-cover">
                        </div>
                        <p class="mt-2 truncate text-[11px] font-semibold text-white" x-text="preview.name"></p>
                        <p class="text-[10px] tabular-nums text-zinc-400" x-text="`${preview.sizeKb} KB`"></p>
                    </div>
                </template>
            </div>
        </div>
    </x-admin.card>

    {{-- Khối 4: Thanh Lưu Cố Định (Sticky Action Bar) --}}
    <div class="sticky bottom-4 z-30 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/15 bg-[#0D121C]/95 px-6 py-4 shadow-2xl backdrop-blur-md">
        <div class="flex items-center gap-2.5 text-xs">
            <span
                :class="isDirty ? 'bg-amber-400' : 'bg-emerald-400'"
                class="h-2 w-2 rounded-full"
            ></span>
            <span
                :class="isDirty ? 'font-bold text-amber-300' : 'font-medium text-zinc-400'"
                x-text="isDirty ? 'Có thay đổi chưa lưu — Vui lòng bấm lưu để cập nhật' : 'Biểu mẫu đã sẵn sàng'"
            ></span>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.products.index') }}"
                class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] px-5 text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white"
            >
                Hủy
            </a>
            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center rounded-xl bg-[#1C69D4] px-7 text-xs font-bold uppercase tracking-wider text-white shadow-lg transition-colors hover:bg-[#1557B0] cursor-pointer"
            >
                {{ $submitLabel }}
            </button>
        </div>
    </div>
</div>
