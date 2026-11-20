<div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_360px]"
     x-data="articleCanvasStudio(@js(old('body', $article->body ?? '')))">
    <!-- Main Content Column -->
    <div class="space-y-6">
        <!-- Article Basic Information Card -->
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 relative shadow-xl shadow-black/40">
            <div class="mb-5 border-b border-white/[0.08] pb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Thông Tin Bài Viết</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Tiêu đề, đường dẫn thân thiện và tóm tắt hiển thị ngoài website</p>
                </div>
                <div
                    x-data="{
                        aiLoading: false,
                        aiNotice: '',
                        async runEditorialAi() {
                            const bodyEl = document.getElementById('body');
                            const titleEl = document.getElementById('title');
                            const categoryEl = document.getElementById('category');
                            const bodyValue = bodyEl ? bodyEl.value : '';

                            if (!bodyValue || bodyValue.trim().length < 20) {
                                this.aiNotice = 'Vui lòng nhập nội dung bài viết (ít nhất 20 ký tự) trước khi gọi AI.';
                                return;
                            }

                            this.aiLoading = true;
                            this.aiNotice = '';

                            try {
                                const res = await fetch('{{ route('admin.articles.ai-assist') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        title: titleEl ? titleEl.value : '',
                                        category: categoryEl ? categoryEl.value : '',
                                        body: bodyValue
                                    })
                                });

                                const payload = await res.json();
                                if (!res.ok) {
                                    this.aiNotice = payload.message || 'Không thể tạo gợi ý AI lúc này.';
                                    return;
                                }

                                if (document.getElementById('excerpt') && payload.excerpt) {
                                    document.getElementById('excerpt').value = payload.excerpt;
                                }
                                if (document.getElementById('seo_title') && payload.seo_title) {
                                    document.getElementById('seo_title').value = payload.seo_title;
                                }
                                if (document.getElementById('seo_description') && payload.seo_description) {
                                    document.getElementById('seo_description').value = payload.seo_description;
                                }

                                this.aiNotice = payload.source === 'ai'
                                    ? 'Đã tạo tóm tắt & SEO bằng AI.'
                                    : 'Đã tạo tóm tắt & SEO tự động.';
                            } catch (e) {
                                this.aiNotice = 'Lỗi kết nối khi gọi trợ lý biên tập AI.';
                            } finally {
                                this.aiLoading = false;
                            }
                        }
                    }"
                    class="flex flex-col items-end gap-1.5"
                >
                    <button
                        type="button"
                        @click="runEditorialAi()"
                        :disabled="aiLoading"
                        class="inline-flex items-center gap-2 rounded-xl border border-[#1C69D4]/50 bg-[#1C69D4]/15 px-3.5 py-2 text-[10px] font-bold uppercase tracking-wider text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer"
                    >
                        <span x-show="!aiLoading">AI Tóm Tắt & Tạo Mô Tả SEO</span>
                        <span x-show="aiLoading">Đang phân tích bài viết...</span>
                    </button>
                    <span x-show="aiNotice" x-text="aiNotice" class="text-[11px] font-medium text-[#70A7FF]"></span>
                </div>
            </div>

            <div class="space-y-5">
                <x-admin.form-field name="title" label="Tiêu đề bài viết">
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title', $article->title) }}"
                        required
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-base font-bold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Ví dụ: Trải nghiệm công nghệ lái điện hoàn toàn mới trên BMW i7"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="slug" label="Đường dẫn tĩnh (Slug)" hint="Để trống để hệ thống tự tạo tự động từ tiêu đề.">
                    <input
                        id="slug"
                        type="text"
                        name="slug"
                        value="{{ old('slug', $article->slug) }}"
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-2.5 text-xs text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="trai-nghiem-cong-nghe-lai-dien-bmw-i7"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="excerpt" label="Đoạn trích tóm tắt (Lead Excerpt)" hint="Đoạn văn ngắn nổi bật xuất hiện đầu bài viết và thẻ danh sách.">
                    <textarea
                        id="excerpt"
                        name="excerpt"
                        rows="3"
                        maxlength="500"
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-3 text-xs leading-relaxed text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Nhập tóm tắt dẫn dắt bài viết..."
                    >{{ old('excerpt', $article->excerpt) }}</textarea>
                </x-admin.form-field>
            </div>
        </div>

        <!-- Canvas Studio Editor Card -->
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] overflow-hidden shadow-xl shadow-black/40">
            <!-- Studio Toolbar & Tab Navigation -->
            <div class="border-b border-white/[0.08] px-6 py-4 flex flex-wrap items-center justify-between gap-4 bg-[#070A10]/70">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#1C69D4]"></div>
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Canvas Studio Visual Editor</h3>
                        <p class="text-[11px] text-zinc-400">Trình soạn thảo khối trực quan chuẩn BMW Editorial</p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 rounded-xl border border-white/10 bg-[#070A10] p-1">
                    <button type="button" 
                            @click="switchTab('canvas')"
                            :class="activeTab === 'canvas' ? 'bg-[#1C69D4] text-white shadow-sm font-semibold' : 'text-zinc-400 hover:text-white'"
                            class="rounded-lg px-3 py-1.5 text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Khối Studio
                    </button>

                    <button type="button" 
                            @click="switchTab('markdown')"
                            :class="activeTab === 'markdown' ? 'bg-[#1C69D4] text-white shadow-sm font-semibold' : 'text-zinc-400 hover:text-white'"
                            class="rounded-lg px-3 py-1.5 text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        Mã Markdown
                    </button>

                    <button type="button" 
                            @click="switchTab('preview')"
                            :class="activeTab === 'preview' ? 'bg-[#1C69D4] text-white shadow-sm font-semibold' : 'text-zinc-400 hover:text-white'"
                            class="rounded-lg px-3 py-1.5 text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Xem Trước
                    </button>
                </div>
            </div>

            <!-- Tab 1: Visual Block Canvas -->
            <div x-show="activeTab === 'canvas'" class="p-6 space-y-5">
                <template x-for="(block, index) in blocks" :key="block.id">
                    <div class="rounded-xl border border-white/[0.08] bg-[#070A10]/85 p-4 relative group shadow-sm transition-all hover:border-white/20">
                        <!-- Block Header Controls -->
                        <div class="flex items-center justify-between mb-3 border-b border-white/[0.08] pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 border"
                                      :class="{
                                          'text-[#70A7FF] bg-blue-950/40 border-blue-500/30': block.type === 'heading',
                                          'text-emerald-400 bg-emerald-950/40 border-emerald-500/30': block.type === 'paragraph',
                                          'text-amber-400 bg-amber-950/40 border-amber-500/30': block.type === 'image',
                                          'text-purple-400 bg-purple-950/40 border-purple-500/30': block.type === 'quote'
                                      }">
                                    <span x-show="block.type === 'heading'" x-text="block.level ? block.level.toUpperCase() : 'H2'"></span>
                                    <span x-show="block.type === 'paragraph'">Đoạn văn</span>
                                    <span x-show="block.type === 'image'">Hình ảnh</span>
                                    <span x-show="block.type === 'quote'">Trích dẫn</span>
                                </span>
                                <span class="text-[11px] text-zinc-500" x-text="'Khối #' + (index + 1)"></span>
                            </div>

                            <div class="flex items-center gap-1">
                                <button type="button" 
                                        @click="moveBlock(index, -1)" 
                                        :disabled="index === 0"
                                        title="Di chuyển lên"
                                        class="p-1.5 text-zinc-400 hover:text-white disabled:opacity-30 disabled:hover:text-zinc-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                                <button type="button" 
                                        @click="moveBlock(index, 1)" 
                                        :disabled="index === blocks.length - 1"
                                        title="Di chuyển xuống"
                                        class="p-1.5 text-zinc-400 hover:text-white disabled:opacity-30 disabled:hover:text-zinc-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7" />
                                    </svg>
                                </button>
                                <button type="button" 
                                        @click="removeBlock(index)" 
                                        title="Xóa khối"
                                        class="p-1.5 text-zinc-500 hover:text-rose-400 transition-colors ml-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Heading Block Input -->
                        <div x-show="block.type === 'heading'" class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-44 shrink-0">
                                    <x-ui.custom-select
                                        compact
                                        x-model="block.level"
                                        @change="sync()"
                                        :options="[
                                            'h2' => 'Tiêu đề lớn H2',
                                            'h3' => 'Tiêu đề phụ H3',
                                        ]"
                                    />
                                </div>
                                <input type="text" 
                                       x-model="block.text" 
                                       @input="sync()"
                                       placeholder="Nhập tiêu đề phần..." 
                                       class="flex-1 rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-sm font-bold text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                            </div>
                        </div>

                        <!-- Paragraph Block Input -->
                        <div x-show="block.type === 'paragraph'">
                            <textarea x-model="block.text" 
                                      @input="sync()"
                                      rows="4" 
                                      placeholder="Nhập nội dung đoạn văn bài viết..." 
                                      class="w-full rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2.5 text-xs leading-relaxed text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"></textarea>
                        </div>

                        <!-- Image Block Input -->
                        <div x-show="block.type === 'image'" class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text" 
                                       x-model="block.url" 
                                       @input="sync()"
                                       placeholder="URL ảnh (https://... hoặc /images/cars/...)" 
                                       class="rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                                <input type="text" 
                                       x-model="block.caption" 
                                       @input="sync()"
                                       placeholder="Chú thích ảnh (caption)..." 
                                       class="rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs text-zinc-300 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                            </div>
                            <template x-if="block.url">
                                <div class="relative max-h-48 overflow-hidden rounded-xl border border-white/10 bg-[#070A10] flex items-center justify-center">
                                    <img :src="block.url" class="max-h-48 object-cover w-full" alt="Preview">
                                </div>
                            </template>
                        </div>

                        <!-- Quote Block Input -->
                        <div x-show="block.type === 'quote'" class="space-y-3">
                            <textarea x-model="block.text" 
                                      @input="sync()"
                                      rows="3" 
                                      placeholder="Nhập câu trích dẫn hoặc thông số kỹ thuật nổi bật..." 
                                      class="w-full rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2.5 text-xs italic leading-relaxed text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"></textarea>
                            <input type="text" 
                                   x-model="block.author" 
                                   @input="sync()"
                                   placeholder="Nguồn phát biểu hoặc thông số (Ví dụ: Đội ngũ thiết kế BMW M)" 
                                   class="w-full rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs text-zinc-400 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                        </div>
                    </div>
                </template>

                <!-- Add Block Actions Toolbar -->
                <div class="rounded-xl border border-dashed border-white/15 bg-[#070A10]/60 p-4 text-center">
                    <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Thêm Khối Nội Dung Mới</p>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <button type="button" 
                                @click="addBlock('heading')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs font-semibold text-white hover:border-[#1C69D4] hover:text-[#70A7FF] transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-[#1C69D4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Tiêu đề (H2/H3)
                        </button>

                        <button type="button" 
                                @click="addBlock('paragraph')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs font-semibold text-white hover:border-[#1C69D4] hover:text-[#70A7FF] transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Đoạn văn
                        </button>

                        <button type="button" 
                                @click="addBlock('image')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs font-semibold text-white hover:border-[#1C69D4] hover:text-[#70A7FF] transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            + Hình ảnh
                        </button>

                        <button type="button" 
                                @click="addBlock('quote')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-[#0D121C] px-3.5 py-2 text-xs font-semibold text-white hover:border-[#1C69D4] hover:text-[#70A7FF] transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            + Trích dẫn
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Raw Markdown Editor -->
            <div x-show="activeTab === 'markdown'" class="p-6">
                <p class="text-xs text-zinc-400 mb-2">Chỉnh sửa trực tiếp cú pháp Markdown. Mọi thay đổi sẽ tự động đồng bộ sang giao diện Khối Studio.</p>
                <textarea x-model="serializedMarkdown" 
                          @input="syncFromMarkdown()"
                          rows="18" 
                          class="w-full rounded-xl font-mono text-xs leading-relaxed border border-white/10 bg-[#070A10] p-4 text-zinc-200 placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"></textarea>
            </div>

            <!-- Tab 3: Live Public Preview -->
            <div x-show="activeTab === 'preview'" class="p-6 bg-[#070A10]/60">
                <div class="rounded-xl border border-white/[0.08] bg-[#0D121C] p-6 sm:p-8">
                    <span class="inline-block rounded-full border border-blue-500/25 bg-blue-950/40 px-3 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#70A7FF] mb-3">
                        Xem trước công khai
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-4" x-text="document.getElementById('title')?.value || 'Tiêu đề bài viết mẫu'"></h2>
                    
                    <div class="article-content" x-html="renderHtmlPreview()"></div>
                </div>
            </div>

            <!-- Hidden Synchronized Textarea for Laravel Backend Form Submission -->
            <textarea id="body" 
                      name="body" 
                      x-model="serializedMarkdown" 
                      required 
                      class="sr-only" 
                      tabindex="-1">{{ old('body', $article->body) }}</textarea>
            <x-input-error :messages="$errors->get('body')" class="px-6 pb-4" />
        </div>

        <!-- SEO Settings Card -->
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 relative shadow-xl shadow-black/40">
            <div class="mb-5 border-b border-white/[0.08] pb-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Cấu Hình SEO Tìm Kiếm</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Tùy biến thẻ tiêu đề và mô tả xuất hiện trên kết quả Google Tìm kiếm</p>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-admin.form-field name="seo_title" label="Tiêu đề SEO">
                    <input
                        id="seo_title"
                        type="text"
                        name="seo_title"
                        value="{{ old('seo_title', $article->seo_title) }}"
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-2.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Tiêu đề hiển thị trên Google..."
                    >
                </x-admin.form-field>

                <x-admin.form-field name="seo_description" label="Mô tả SEO">
                    <input
                        id="seo_description"
                        type="text"
                        name="seo_description"
                        value="{{ old('seo_description', $article->seo_description) }}"
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-4 py-2.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                        placeholder="Đoạn văn ngắn mô tả bài viết..."
                    >
                </x-admin.form-field>
            </div>
        </div>
    </div>

    <!-- Sidebar Right Column -->
    <aside class="space-y-6">
        <!-- Publication Settings Card -->
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 relative shadow-xl shadow-black/40">
            <div class="mb-5 border-b border-white/[0.08] pb-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Xuất Bản Bài Viết</h3>
            </div>

            <div class="space-y-5">
                <x-admin.form-field name="category" label="Chuyên mục bài viết">
                    <x-ui.custom-select
                        id="category"
                        name="category"
                        :value="(string) old('category', $article->category ?? array_key_first($categories))"
                        :options="$categories"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="status" label="Trạng thái">
                    <x-ui.custom-select
                        id="status"
                        name="status"
                        :value="(string) old('status', $article->status ?? array_key_first($statuses))"
                        :options="$statuses"
                    />
                </x-admin.form-field>

                <x-admin.form-field name="published_at" label="Thời gian xuất bản" hint="Để trống khi xuất bản để dùng thời gian hiện tại.">
                    <input
                        id="published_at"
                        type="datetime-local"
                        name="published_at"
                        value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 py-2.5 text-xs text-zinc-200 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    >
                </x-admin.form-field>
            </div>
        </div>

        <!-- Cover Image Card -->
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 relative shadow-xl shadow-black/40">
            <div class="mb-5 border-b border-white/[0.08] pb-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Ảnh Đại Diện</h3>
            </div>

            @if($article->coverImageUrl())
                <div class="mb-4 aspect-video rounded-xl border border-white/10 bg-[#070A10] overflow-hidden shadow-sm">
                    <img src="{{ $article->coverImageUrl() }}" alt="{{ $article->title }}" class="h-full w-full object-cover">
                </div>
            @endif

            <x-admin.form-field name="cover_image" label="Tải lên ảnh mới">
                <input
                    id="cover_image"
                    type="file"
                    name="cover_image"
                    accept="image/*"
                    class="w-full rounded-xl border border-white/10 bg-[#070A10] p-2.5 text-xs text-zinc-300 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-[#1C69D4] cursor-pointer"
                >
            </x-admin.form-field>
        </div>

        <!-- Submit & Actions -->
        <div class="grid grid-cols-1 gap-3">
            <button type="submit" class="w-full inline-flex items-center justify-center rounded-xl px-6 py-3.5 bg-[#1C69D4] hover:bg-[#2575e6] text-white text-xs font-semibold uppercase tracking-wider shadow-lg shadow-[#1C69D4]/25 focus:outline-none focus:ring-1 focus:ring-blue-400 transition-all cursor-pointer">
                {{ $submitLabel }}
            </button>
            <a href="{{ route('admin.articles.index') }}" class="rounded-xl border border-white/10 bg-white/[0.03] px-5 py-3 text-center text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                Hủy bỏ
            </a>
        </div>
    </aside>
</div>

<script>
function articleCanvasStudio(initialMarkdown) {
    return {
        activeTab: 'canvas',
        serializedMarkdown: initialMarkdown || '',
        blocks: [],

        init() {
            this.parseMarkdown(this.serializedMarkdown);
            if (this.blocks.length === 0) {
                this.blocks.push({ id: Date.now(), type: 'heading', level: 'h2', text: '' });
                this.blocks.push({ id: Date.now() + 1, type: 'paragraph', text: '' });
            }
            this.sync();
        },

        switchTab(tab) {
            if (this.activeTab === 'markdown' && tab !== 'markdown') {
                this.parseMarkdown(this.serializedMarkdown);
            } else if (tab === 'markdown' || tab === 'preview') {
                this.sync();
            }
            this.activeTab = tab;
        },

        addBlock(type) {
            const id = Date.now() + Math.random();
            if (type === 'heading') {
                this.blocks.push({ id, type: 'heading', level: 'h2', text: '' });
            } else if (type === 'paragraph') {
                this.blocks.push({ id, type: 'paragraph', text: '' });
            } else if (type === 'image') {
                this.blocks.push({ id, type: 'image', url: '', caption: '' });
            } else if (type === 'quote') {
                this.blocks.push({ id, type: 'quote', text: '', author: '' });
            }
            this.sync();
        },

        removeBlock(index) {
            if (this.blocks.length <= 1) {
                this.blocks = [{ id: Date.now(), type: 'paragraph', text: '' }];
            } else {
                this.blocks.splice(index, 1);
            }
            this.sync();
        },

        moveBlock(index, direction) {
            const targetIndex = index + direction;
            if (targetIndex < 0 || targetIndex >= this.blocks.length) return;
            const temp = this.blocks[index];
            this.blocks[index] = this.blocks[targetIndex];
            this.blocks[targetIndex] = temp;
            this.sync();
        },

        sync() {
            const lines = [];
            for (const b of this.blocks) {
                if (b.type === 'heading') {
                    const prefix = b.level === 'h3' ? '### ' : '## ';
                    if (b.text && b.text.trim()) {
                        lines.push(prefix + b.text.trim());
                    }
                } else if (b.type === 'paragraph') {
                    if (b.text && b.text.trim()) {
                        lines.push(b.text.trim());
                    }
                } else if (b.type === 'image') {
                    if (b.url && b.url.trim()) {
                        lines.push(`![${b.caption ? b.caption.trim() : ''}](${b.url.trim()})`);
                    }
                } else if (b.type === 'quote') {
                    if (b.text && b.text.trim()) {
                        let quoteStr = '> ' + b.text.trim().split('\n').join('\n> ');
                        if (b.author && b.author.trim()) {
                            quoteStr += '\n> — *' + b.author.trim() + '*';
                        }
                        lines.push(quoteStr);
                    }
                }
            }
            this.serializedMarkdown = lines.join('\n\n');
        },

        syncFromMarkdown() {
            // Keep serializedMarkdown intact while typing in markdown tab
        },

        parseMarkdown(md) {
            if (!md || !md.trim()) {
                this.blocks = [];
                return;
            }

            const chunks = md.split(/\n{2,}/);
            const parsed = [];

            for (const chunk of chunks) {
                const trimmed = chunk.trim();
                if (!trimmed) continue;

                if (trimmed.startsWith('### ')) {
                    parsed.push({
                        id: Math.random(),
                        type: 'heading',
                        level: 'h3',
                        text: trimmed.replace(/^###\s+/, '')
                    });
                } else if (trimmed.startsWith('## ')) {
                    parsed.push({
                        id: Math.random(),
                        type: 'heading',
                        level: 'h2',
                        text: trimmed.replace(/^##\s+/, '')
                    });
                } else if (trimmed.startsWith('> ')) {
                    const lines = trimmed.split('\n');
                    let author = '';
                    const quoteLines = [];
                    for (const line of lines) {
                        const clean = line.replace(/^>\s*/, '');
                        if (clean.startsWith('— *') && clean.endsWith('*')) {
                            author = clean.substring(3, clean.length - 1);
                        } else {
                            quoteLines.push(clean);
                        }
                    }
                    parsed.push({
                        id: Math.random(),
                        type: 'quote',
                        text: quoteLines.join('\n'),
                        author: author
                    });
                } else {
                    const imgMatch = trimmed.match(/^!\[(.*?)\]\((.*?)\)$/);
                    if (imgMatch) {
                        parsed.push({
                            id: Math.random(),
                            type: 'image',
                            caption: imgMatch[1],
                            url: imgMatch[2]
                        });
                    } else {
                        parsed.push({
                            id: Math.random(),
                            type: 'paragraph',
                            text: trimmed
                        });
                    }
                }
            }

            this.blocks = parsed;
        },

        renderHtmlPreview() {
            let html = '';
            for (const b of this.blocks) {
                if (b.type === 'heading') {
                    if (b.level === 'h3') {
                        html += `<h3>${this.escapeHtml(b.text || '')}</h3>`;
                    } else {
                        html += `<h2>${this.escapeHtml(b.text || '')}</h2>`;
                    }
                } else if (b.type === 'paragraph') {
                    html += `<p>${this.escapeHtml(b.text || '').replace(/\n/g, '<br>')}</p>`;
                } else if (b.type === 'image') {
                    if (b.url) {
                        html += `<figure><img src="${this.escapeHtml(b.url)}" alt="${this.escapeHtml(b.caption || '')}">${b.caption ? `<figcaption>${this.escapeHtml(b.caption)}</figcaption>` : ''}</figure>`;
                    }
                } else if (b.type === 'quote') {
                    html += `<blockquote><p>${this.escapeHtml(b.text || '')}</p>${b.author ? `<figcaption class="text-right text-xs text-zinc-400 mt-2">— ${this.escapeHtml(b.author)}</figcaption>` : ''}</blockquote>`;
                }
            }
            return html || '<p class="text-zinc-500 italic">Nội dung bài viết đang trống...</p>';
        },

        escapeHtml(string) {
            const entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return String(string).replace(/[&<>"']/g, s => entityMap[s]);
        }
    };
}
</script>
