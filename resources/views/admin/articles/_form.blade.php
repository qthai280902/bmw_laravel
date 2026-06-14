<div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="space-y-6">
        <x-admin.card class="!p-6">
            <div class="mb-6 border-b border-zinc-900 pb-4">
                <p class="text-[10px] font-black uppercase tracking-[0.26em] text-zinc-600">Nội dung chính</p>
            </div>

            <div class="space-y-6">
                <x-admin.form-field name="title" label="Tiêu đề">
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title', $article->title) }}"
                        required
                        class="w-full border-zinc-800 bg-black px-5 py-4 text-lg font-black text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Ví dụ: Trải nghiệm lái thử BMW cuối tuần"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="slug" label="Slug" hint="Để trống để hệ thống tự tạo từ tiêu đề.">
                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old('slug', $article->slug) }}"
                        class="w-full border-zinc-800 bg-black px-5 py-3 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="trai-nghiem-lai-thu-bmw"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="excerpt" label="Tóm tắt ngắn">
                    <textarea
                        id="excerpt"
                        name="excerpt"
                        rows="3"
                        maxlength="500"
                        class="w-full border-zinc-800 bg-black px-5 py-4 text-sm leading-6 text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Tóm tắt hiển thị ở card public..."
                    >{{ old('excerpt', $article->excerpt) }}</textarea>
                </x-admin.form-field>

                <x-admin.form-field name="body" label="Nội dung bài viết">
                    <textarea
                        id="body"
                        name="body"
                        rows="16"
                        required
                        class="w-full border-zinc-800 bg-black px-5 py-4 text-sm leading-7 text-zinc-100 placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Nhập nội dung bài viết..."
                    >{{ old('body', $article->body) }}</textarea>
                </x-admin.form-field>
            </div>
        </x-admin.card>

        <x-admin.card class="!p-6">
            <div class="mb-6 border-b border-zinc-900 pb-4">
                <p class="text-[10px] font-black uppercase tracking-[0.26em] text-zinc-600">SEO tùy chọn</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-admin.form-field name="seo_title" label="SEO title">
                    <input
                        id="seo_title"
                        type="text"
                        name="seo_title"
                        value="{{ old('seo_title', $article->seo_title) }}"
                        class="w-full border-zinc-800 bg-black px-5 py-3 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="seo_description" label="SEO description">
                    <input
                        id="seo_description"
                        type="text"
                        name="seo_description"
                        value="{{ old('seo_description', $article->seo_description) }}"
                        class="w-full border-zinc-800 bg-black px-5 py-3 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    >
                </x-admin.form-field>
            </div>
        </x-admin.card>
    </div>

    <aside class="space-y-6">
        <x-admin.card class="!p-6">
            <div class="mb-6 border-b border-zinc-900 pb-4">
                <p class="text-[10px] font-black uppercase tracking-[0.26em] text-zinc-600">Xuất bản</p>
            </div>

            <div class="space-y-6">
                <x-admin.form-field name="category" label="Chuyên mục">
                    <select id="category" name="category" required class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" {{ old('category', $article->category) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-admin.form-field>

                <x-admin.form-field name="status" label="Trạng thái">
                    <select id="status" name="status" required class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $article->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-admin.form-field>

                <x-admin.form-field name="published_at" label="Ngày xuất bản" hint="Nếu để trống khi chọn xuất bản, hệ thống dùng thời điểm hiện tại.">
                    <input
                        id="published_at"
                        type="datetime-local"
                        name="published_at"
                        value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full border-zinc-800 bg-black px-4 py-3 text-sm text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    >
                </x-admin.form-field>
            </div>
        </x-admin.card>

        <x-admin.card class="!p-6">
            <div class="mb-6 border-b border-zinc-900 pb-4">
                <p class="text-[10px] font-black uppercase tracking-[0.26em] text-zinc-600">Ảnh đại diện</p>
            </div>

            @if($article->coverImageUrl())
                <div class="mb-5 aspect-video border border-zinc-800 bg-black">
                    <img src="{{ $article->coverImageUrl() }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
                </div>
            @endif

            <x-admin.form-field name="cover_image" label="Upload ảnh">
                <input
                    id="cover_image"
                    type="file"
                    name="cover_image"
                    accept="image/*"
                    class="w-full border border-zinc-800 bg-black p-3 text-xs text-zinc-500 file:mr-4 file:border-0 file:bg-zinc-900 file:px-4 file:py-2 file:text-[10px] file:font-black file:uppercase file:tracking-[0.2em] file:text-zinc-300 hover:file:bg-zinc-800"
                >
            </x-admin.form-field>
        </x-admin.card>

        <div class="grid grid-cols-1 gap-3">
            <button type="submit" class="border border-white bg-white px-6 py-4 text-[10px] font-black uppercase tracking-[0.24em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                {{ $submitLabel }}
            </button>
            <a href="{{ route('admin.articles.index') }}" class="border border-zinc-800 px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Hủy
            </a>
        </div>
    </aside>
</div>
