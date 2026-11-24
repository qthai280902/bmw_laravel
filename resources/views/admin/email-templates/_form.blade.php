@php
    $initialPayload = [
        'name' => old('name', $template->name ?? ''),
        'code' => old('code', $template->code ?? ''),
        'category' => old('category', $template->category ?? \App\Models\EmailTemplate::CATEGORY_PROMOTION),
        'status' => old('status', $template->status ?? \App\Models\EmailTemplate::STATUS_ACTIVE),
        'subject' => old('subject', $template->subject ?? ''),
        'eyebrow' => old('eyebrow', $template->eyebrow ?? 'BMW Client Relations'),
        'heading' => old('heading', $template->heading ?? ''),
        'body_content' => old('body_content', $template->body_content ?? ''),
        'highlight_title' => old('highlight_title', $template->highlight_title ?? ''),
        'highlight_content' => old('highlight_content', $template->highlight_content ?? ''),
        'cta_label' => old('cta_label', $template->cta_label ?? ''),
        'cta_url' => old('cta_url', $template->cta_url ?? ''),
        'accent_color' => old('accent_color', $template->accent_color ?? '#1C69D4'),
        'samples' => $sampleVariables,
        'categories' => $categoryOptions,
    ];
@endphp

<script id="bmw-email-template-form-config" type="application/json">{!! json_encode($initialPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>

<div
    x-data="bmwEmailTemplateForm('bmw-email-template-form-config')"
    class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr_0.85fr] xl:items-start"
>
    <x-admin.card class="!p-6 space-y-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-admin.form-field name="name" label="Tên mẫu Email *">
                <input
                    id="name"
                    type="text"
                    name="name"
                    x-model="form.name"
                    required
                    placeholder="Ví dụ: Nhắc lịch thanh toán trả góp định kỳ"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="code" label="Mã mẫu (slug không dấu, để trống sẽ tự sinh)">
                <input
                    id="code"
                    type="text"
                    name="code"
                    x-model="form.code"
                    placeholder="installment-due-reminder"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 font-mono text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="category" label="Danh mục *">
                <x-ui.custom-select
                    id="category"
                    name="category"
                    :value="old('category', $template->category ?? \App\Models\EmailTemplate::CATEGORY_PROMOTION)"
                    :options="$categoryOptions"
                    x-model="form.category"
                />
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái phát hành *">
                <x-ui.custom-select
                    id="status"
                    name="status"
                    :value="old('status', $template->status ?? \App\Models\EmailTemplate::STATUS_ACTIVE)"
                    :options="$statusOptions"
                    x-model="form.status"
                />
            </x-admin.form-field>
        </div>

        <div class="rounded-xl border border-[#1C69D4]/25 bg-[#1C69D4]/10 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] font-bold text-[#70A7FF]">Biến động tự động điền (bấm để chèn vào ô đang chọn)</p>
                <span class="text-[10px] font-medium text-zinc-400">
                    Đang chèn vào: <strong class="text-white" x-text="activeTarget"></strong>
                </span>
            </div>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach($variableCatalog as $varKey => $varMeta)
                    <button
                        type="button"
                        @click="insertVariable('{{ $varKey }}')"
                        title="{{ $varMeta['label'] }} (Ví dụ: {{ $varMeta['sample'] }})"
                        class="rounded-lg border border-white/15 bg-[#0D121C] px-2.5 py-1 font-mono text-[11px] font-semibold text-sky-200 transition-colors hover:border-[#1C69D4] hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                    >
                        &#123;&#123;{{ $varKey }}&#125;&#125;
                    </button>
                @endforeach
            </div>
        </div>

        <x-admin.form-field name="subject" label="Tiêu đề Email (Subject) *">
            <input
                id="subject"
                type="text"
                name="subject"
                x-model="form.subject"
                @focus="activeTarget = 'subject'"
                required
                placeholder="[BMW Financial] Nhắc lịch thanh toán kỳ trả góp @{{vehicle_name}}"
                class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
            >
        </x-admin.form-field>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-admin.form-field name="eyebrow" label="Nhãn nhỏ Header (Eyebrow)">
                <input
                    id="eyebrow"
                    type="text"
                    name="eyebrow"
                    x-model="form.eyebrow"
                    @focus="activeTarget = 'eyebrow'"
                    placeholder="BMW Financial Services"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="accent_color" label="Màu nhấn thương hiệu (HEX) *">
                <div class="flex items-center gap-2">
                    <input
                        type="color"
                        x-model="form.accent_color"
                        class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-white/15 bg-[#070A10] p-1"
                    >
                    <input
                        id="accent_color"
                        type="text"
                        name="accent_color"
                        x-model="form.accent_color"
                        required
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 font-mono text-xs text-white focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                    >
                </div>
            </x-admin.form-field>
        </div>

        <x-admin.form-field name="heading" label="Tiêu đề chính trong thư (Heading) *">
            <input
                id="heading"
                type="text"
                name="heading"
                x-model="form.heading"
                @focus="activeTarget = 'heading'"
                required
                placeholder="Nhắc lịch thanh toán kỳ trả góp định kỳ"
                class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
            >
        </x-admin.form-field>

        <x-admin.form-field name="body_content" label="Nội dung Email * (phân đoạn bằng 1 dòng trống)">
            <textarea
                id="body_content"
                name="body_content"
                rows="8"
                x-model="form.body_content"
                @focus="activeTarget = 'body_content'"
                required
                class="w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 py-3 text-xs leading-relaxed text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
            ></textarea>
        </x-admin.form-field>

        <div class="grid grid-cols-1 gap-4 rounded-xl border border-white/[0.07] bg-[#070A10]/60 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Khối nổi bật (Highlight Box — Tùy chọn)</p>
            <x-admin.form-field name="highlight_title" label="Tiêu đề khung nổi bật">
                <input
                    id="highlight_title"
                    type="text"
                    name="highlight_title"
                    x-model="form.highlight_title"
                    @focus="activeTarget = 'highlight_title'"
                    placeholder="Thông tin kỳ thanh toán sắp tới"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="highlight_content" label="Nội dung khung nổi bật">
                <textarea
                    id="highlight_content"
                    name="highlight_content"
                    rows="4"
                    x-model="form.highlight_content"
                    @focus="activeTarget = 'highlight_content'"
                    class="w-full rounded-xl border border-white/10 bg-[#0D121C] px-3.5 py-2.5 text-xs leading-relaxed text-white focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                ></textarea>
            </x-admin.form-field>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-admin.form-field name="cta_label" label="Nhãn nút CTA (Tùy chọn)">
                <input
                    id="cta_label"
                    type="text"
                    name="cta_label"
                    x-model="form.cta_label"
                    @focus="activeTarget = 'cta_label'"
                    placeholder="LIÊN HỆ CHUYÊN VIÊN TÀI CHÍNH"
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="cta_url" label="Đường dẫn nút CTA">
                <input
                    id="cta_url"
                    type="url"
                    name="cta_url"
                    x-model="form.cta_url"
                    placeholder="https://..."
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-white/[0.08] pt-4">
            <a href="{{ route('admin.email-templates.index') }}" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-[#131A29] px-5 text-xs font-semibold text-zinc-300 hover:border-white/25 hover:text-white">
                Quay lại kho mẫu
            </a>
            <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-[#1C69D4] px-6 text-xs font-bold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#1657B0] cursor-pointer">
                {{ $submitLabel ?? 'Lưu mẫu Email' }}
            </button>
        </div>
    </x-admin.card>

    <!-- Live Email Preview (BMW Concierge Light Luxury Shell) -->
    <aside class="sticky top-24 space-y-3">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-4">
            <div class="flex items-center justify-between gap-2 pb-3">
                <span class="text-xs font-bold text-[#38BDF8]">Live Preview · Giao diện Email khách hàng nhận</span>
                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-400">
                    Dữ liệu mẫu thật
                </span>
            </div>

            <div class="mb-3 rounded-xl border border-white/[0.06] bg-[#070A10] px-3.5 py-2.5 text-xs">
                <span class="text-zinc-500">Subject:</span>
                <span class="ml-1 font-semibold text-white" x-text="interpolate(form.subject) || '(Chưa nhập tiêu đề)'"></span>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-xl">
                <div class="h-1.5 w-full" :style="'background-color:' + (form.accent_color || '#1C69D4')"></div>

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-900">BMW SHOWROOM</p>
                        <p class="mt-0.5 text-[11px] font-medium text-slate-500" x-text="interpolate(form.eyebrow) || 'Concierge & Client Relations'"></p>
                    </div>
                    <span
                        class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[10px] font-bold"
                        :style="'color:' + (form.accent_color || '#1C69D4')"
                        x-text="form.categories[form.category] || 'BMW Showroom'"
                    ></span>
                </div>

                <div class="space-y-4 px-6 py-6 text-sm leading-relaxed text-slate-600">
                    <h4 class="text-lg font-bold leading-snug text-slate-900" x-text="interpolate(form.heading) || 'Tiêu đề chính của Email'"></h4>

                    <template x-for="(paragraph, idx) in previewParagraphs()" :key="idx">
                        <p class="whitespace-pre-line text-[13px] leading-relaxed text-slate-700" x-text="paragraph"></p>
                    </template>

                    <div
                        x-show="form.highlight_title || form.highlight_content"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                        :style="'border-left: 4px solid ' + (form.accent_color || '#1C69D4')"
                    >
                        <p
                            x-show="form.highlight_title"
                            class="mb-1 text-[11px] font-bold uppercase tracking-wider"
                            :style="'color:' + (form.accent_color || '#1C69D4')"
                            x-text="interpolate(form.highlight_title)"
                        ></p>
                        <p
                            x-show="form.highlight_content"
                            class="whitespace-pre-line text-xs leading-relaxed text-slate-800"
                            x-text="interpolate(form.highlight_content)"
                        ></p>
                    </div>

                    <div x-show="form.cta_label" class="pt-1">
                        <span
                            class="inline-block rounded-lg px-5 py-2.5 text-xs font-bold tracking-wide text-white"
                            :style="'background-color:' + (form.accent_color || '#1C69D4')"
                            x-text="interpolate(form.cta_label)"
                        ></span>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 text-[11px] text-slate-500">
                    <p class="font-semibold text-slate-800">BMW Showroom Flagship — 123 Nguyễn Văn Linh, Q.7, TP.HCM</p>
                    <p class="mt-1">Hotline: 1900 123 456 · Quý khách có thể bấm Trả lời (Reply) trực tiếp email này.</p>
                </div>
            </div>
        </div>
    </aside>
</div>
