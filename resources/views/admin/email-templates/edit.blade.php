<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.email-templates.index')"
        back-label="Quay lại kho mẫu"
        eyebrow="Email Template Studio · {{ $template->code }}"
        title="Chỉnh sửa"
        accent="Mẫu Email"
        :description="$template->name"
    >
        <x-slot name="actions">
            <form method="POST" action="{{ route('admin.email-templates.duplicate', $template) }}">
                @csrf
                <button type="submit" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-[#131A29] px-4 text-xs font-semibold text-zinc-200 transition-colors hover:border-[#1C69D4] hover:text-white cursor-pointer">
                    Nhân bản mẫu này
                </button>
            </form>
        </x-slot>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.email-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.email-templates._form', ['submitLabel' => 'Cập nhật mẫu Email'])
    </form>
</x-admin-layout>
