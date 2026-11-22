@props(['record'])

@php
    $isArchived = $record->isArchived();
    $canArchive = $record->canArchive();
    $isTopSuperAdmin = (bool) auth()->user()?->isTopSuperAdmin();
@endphp

@if($isArchived || ($canArchive && $isTopSuperAdmin))
    <section
        data-drawer-archive-panel="{{ $record->id }}"
        class="rounded-xl border border-white/[0.08] bg-white/[0.02] p-4 space-y-3"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-semibold text-amber-300">Kho lưu trữ CRM (Archive)</p>
            @if($isArchived)
                <span class="rounded-full border border-amber-500/35 bg-amber-500/15 px-2.5 py-0.5 text-[10px] font-semibold text-amber-200">
                    Đã lưu trữ · {{ $record->archived_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>

        @if($isArchived)
            <p class="text-xs text-zinc-400">
                Người lưu trữ:
                <strong class="text-zinc-200">{{ $record->archivedBy?->name ?? 'Hệ thống tự động (quá 30 ngày)' }}</strong>
            </p>

            @if($isTopSuperAdmin)
                <button
                    type="button"
                    wire:click="promptUnarchiveRecord({{ $record->id }}, '{{ $record->publicReferenceCode() }}')"
                    data-action="unarchive-{{ $record->id }}"
                    class="inline-flex h-9 items-center justify-center rounded-xl border border-sky-500/35 bg-sky-500/15 px-4 text-xs font-semibold text-sky-200 transition-colors hover:bg-sky-500/25 cursor-pointer"
                >
                    Khôi phục về CRM đang hoạt động
                </button>
            @endif
        @elseif($canArchive && $isTopSuperAdmin)
            <p class="text-xs text-zinc-400">
                Hồ sơ đã đóng. Có thể đưa vào Kho lưu trữ để giữ danh sách CRM gọn sạch (Kế toán vẫn hạch toán đầy đủ).
            </p>
            <button
                type="button"
                wire:click="promptArchiveRecord({{ $record->id }}, '{{ $record->publicReferenceCode() }}')"
                data-action="archive-{{ $record->id }}"
                class="inline-flex h-9 items-center justify-center rounded-xl border border-amber-500/35 bg-amber-500/15 px-4 text-xs font-semibold text-amber-200 transition-colors hover:bg-amber-500/25 cursor-pointer"
            >
                Đưa vào Kho lưu trữ
            </button>
        @endif
    </section>
@endif
