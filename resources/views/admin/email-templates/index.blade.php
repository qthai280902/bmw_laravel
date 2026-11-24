<x-admin-layout>
    <x-admin.page-header
        eyebrow="Email Template Studio"
        title="Kho mẫu"
        accent="Email"
        description="Soạn thảo, lưu trữ và đồng bộ các mẫu Email chăm sóc khách hàng, nhắc lịch trả góp, điều chỉnh lãi suất và ưu đãi BMW."
    >
        <x-slot name="actions">
            <a
                href="{{ route('admin.email-templates.create') }}"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#1C69D4] px-5 text-xs font-bold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#1657B0]"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tạo mẫu Email mới
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="mb-6 grid grid-cols-2 gap-3.5 lg:grid-cols-4">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] px-4 py-4">
            <p class="text-[11px] font-semibold text-zinc-400">Tổng số mẫu</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-4">
            <p class="text-[11px] font-semibold text-emerald-300">Đang dùng (Active)</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-white">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-500/25 bg-amber-500/10 px-4 py-4">
            <p class="text-[11px] font-semibold text-amber-200">Bản nháp (Draft)</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-white">{{ $stats['draft'] }}</p>
        </div>
        <div class="rounded-2xl border border-sky-500/25 bg-sky-500/10 px-4 py-4">
            <p class="text-[11px] font-semibold text-sky-300">Nhóm Trả góp &amp; Lãi suất</p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-white">{{ $stats['installment'] }}</p>
        </div>
    </div>

    <x-admin.card class="mb-6 !p-5">
        <form method="GET" action="{{ route('admin.email-templates.index') }}" class="grid grid-cols-1 gap-3.5 md:grid-cols-[1.5fr_1fr_1fr_auto] md:items-end">
            <x-admin.form-field name="search" label="Tìm theo tên, mã hoặc tiêu đề">
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Ví dụ: trả góp, lãi suất, ưu đãi..."
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="category" label="Danh mục">
                <x-ui.custom-select
                    id="category"
                    name="category"
                    :value="$category"
                    :options="array_merge(['' => 'Tất cả danh mục'], $categoryOptions)"
                />
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <x-ui.custom-select
                    id="status"
                    name="status"
                    :value="$status"
                    :options="array_merge(['' => 'Tất cả trạng thái'], $statusOptions)"
                />
            </x-admin.form-field>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1657B0] cursor-pointer">
                    Lọc
                </button>
                <a href="{{ route('admin.email-templates.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-[#131A29] px-4 text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white">
                    Xóa lọc
                </a>
            </div>
        </form>
    </x-admin.card>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @forelse($templates as $template)
            <article class="flex flex-col justify-between overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-lg shadow-black/30">
                <div>
                    <div class="h-1.5 w-full" style="background-color: {{ $template->accent_color }};"></div>
                    <div class="p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $template->categoryColorClass() }}">
                                    {{ $template->categoryLabel() }}
                                </span>
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $template->statusColorClass() }}">
                                    {{ $template->statusLabel() }}
                                </span>
                            </div>
                            <span class="font-mono text-[11px] text-zinc-500">{{ $template->code }}</span>
                        </div>

                        <h3 class="mt-3 text-base font-bold text-white">{{ $template->name }}</h3>
                        <p class="mt-1.5 text-xs font-semibold text-[#70A7FF]">Tiêu đề: {{ $template->subject }}</p>
                        <p class="mt-2 line-clamp-3 text-xs leading-relaxed text-zinc-400">
                            {{ \Illuminate\Support\Str::limit($template->body_content, 210) }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/[0.06] bg-[#070A10]/60 px-5 py-3.5">
                    <p class="text-[11px] text-zinc-500">
                        Cập nhật {{ $template->updated_at?->format('d/m/Y H:i') }}
                        @if($template->updater)
                            · {{ $template->updater->name }}
                        @endif
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ route('admin.email-templates.edit', $template) }}"
                            class="inline-flex h-8 items-center rounded-lg border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-3 text-[11px] font-semibold text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white"
                        >
                            Chỉnh sửa &amp; Xem trước
                        </a>

                        <form method="POST" action="{{ route('admin.email-templates.duplicate', $template) }}">
                            @csrf
                            <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-white/10 bg-[#131A29] px-3 text-[11px] font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:text-white cursor-pointer">
                                Nhân bản
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.email-templates.status', $template) }}">
                            @csrf
                            @method('PATCH')
                            @if($template->isActive())
                                <input type="hidden" name="status" value="{{ \App\Models\EmailTemplate::STATUS_ARCHIVED }}">
                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 text-[11px] font-semibold text-amber-200 transition-colors hover:bg-amber-500/20 cursor-pointer">
                                    Lưu trữ
                                </button>
                            @else
                                <input type="hidden" name="status" value="{{ \App\Models\EmailTemplate::STATUS_ACTIVE }}">
                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 text-[11px] font-semibold text-emerald-300 transition-colors hover:bg-emerald-500/20 cursor-pointer">
                                    Kích hoạt
                                </button>
                            @endif
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="lg:col-span-2">
                <x-admin.empty-state
                    title="Chưa có mẫu Email nào phù hợp"
                    description="Hãy tạo mẫu Email mới hoặc bỏ bộ lọc để xem toàn bộ kho mẫu."
                />
            </div>
        @endforelse
    </div>

    @if($templates->hasPages())
        <div class="mt-8">
            {{ $templates->links() }}
        </div>
    @endif
</x-admin-layout>
