@props([
    'archiveScope' => 'active',
    'archivedCount' => 0,
])

<div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/[0.08] bg-[#0D121C] px-4 py-2.5">
    <div class="inline-flex items-center gap-1.5" role="group" aria-label="Chế độ hiển thị hồ sơ CRM">
        <button
            type="button"
            wire:click="setArchiveScope('active')"
            data-archive-tab="active"
            class="h-8 rounded-lg px-3.5 text-xs font-semibold transition-colors cursor-pointer {{ $archiveScope !== 'archived' ? 'bg-[#1C69D4] text-white shadow-sm' : 'bg-[#070A10] text-zinc-400 hover:text-white' }}"
        >
            Đang hoạt động
        </button>
        <button
            type="button"
            wire:click="setArchiveScope('archived')"
            data-archive-tab="archived"
            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-3.5 text-xs font-semibold transition-colors cursor-pointer {{ $archiveScope === 'archived' ? 'bg-amber-500/20 text-amber-200 border border-amber-500/40' : 'bg-[#070A10] text-zinc-400 hover:text-white' }}"
        >
            <span>Kho lưu trữ</span>
            <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-bold tabular-nums">{{ $archivedCount }}</span>
        </button>
    </div>

    @if(auth()->user()?->isTopSuperAdmin() && $archiveScope !== 'archived')
        <button
            type="button"
            wire:click="promptArchiveAllClosed"
            data-action="archive-all-closed"
            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-amber-500/35 bg-amber-500/15 px-3.5 text-xs font-semibold text-amber-200 transition-colors hover:bg-amber-500/25 cursor-pointer"
        >
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            <span>Lưu trữ hàng loạt hồ sơ đã đóng</span>
        </button>
    @endif
</div>

<x-admin.confirm-archive-modal
    :action="$this->pendingArchiveAction ?? null"
    :reference-code="$this->pendingArchiveReferenceCode ?? ''"
/>
