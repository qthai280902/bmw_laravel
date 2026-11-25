@props([
    'action' => null,
    'referenceCode' => '',
    'entityLabel' => 'hồ sơ CRM',
    'closedCount' => 0,
])

@if($action !== null)
    @php
        $isUnarchive = $action === 'unarchive';
        $isBulk = $action === 'archive_all_closed';

        $modalTitle = match (true) {
            $isUnarchive => 'Khôi phục hồ sơ từ Kho lưu trữ CRM',
            $isBulk => 'Lưu trữ hàng loạt hồ sơ đã đóng',
            default => 'Chuyển hồ sơ vào Kho lưu trữ CRM',
        };

        $modalSubtitle = match (true) {
            $isUnarchive => "Bạn đang chuẩn bị khôi phục {$entityLabel} " . ($referenceCode !== '' ? $referenceCode : '') . ' quay lại danh sách điều phối CRM đang hoạt động.',
            $isBulk => "Hệ thống sẽ chuyển toàn bộ {$closedCount} {$entityLabel} đã Hoàn thành hoặc Đã hủy sang tab Đã lưu trữ để làm gọn bảng điều phối.",
            default => "Bạn có chắc muốn chuyển {$entityLabel} " . ($referenceCode !== '' ? $referenceCode : '') . ' vào Kho lưu trữ CRM?',
        };

        $confirmLabel = match (true) {
            $isUnarchive => 'Xác nhận khôi phục',
            $isBulk => 'Xác nhận lưu trữ nhanh',
            default => 'Xác nhận lưu trữ',
        };
    @endphp

    <div
        wire:key="confirm-archive-modal-wrap-{{ $action }}-{{ md5((string) $referenceCode) }}"
        x-data
        @keydown.escape.window="$wire.cancelArchiveConfirm()"
    >
        <template x-teleport="body">
            <div
                data-confirm-archive-modal="{{ $action }}"
                class="fixed inset-0 z-[120] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-archive-modal-title"
            >
                <div
                    wire:click="cancelArchiveConfirm"
                    class="absolute inset-0 z-0 bg-black/80 backdrop-blur-sm transition-opacity"
                ></div>

                <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-white/15 bg-[#0B0F17] p-6 text-white shadow-2xl">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $isUnarchive ? 'border-emerald-500/35 bg-emerald-500/15 text-emerald-300' : 'border-amber-500/35 bg-amber-500/15 text-amber-300' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] {{ $isUnarchive ? 'text-emerald-400' : 'text-amber-400' }}">
                                Kho lưu trữ Super Admin
                            </p>
                            <h3 id="confirm-archive-modal-title" class="mt-1 text-base font-bold text-white">
                                {{ $modalTitle }}
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-zinc-300">
                                {{ $modalSubtitle }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-3.5 py-2.5 text-xs font-semibold text-emerald-200">
                        Dữ liệu Kế toán &amp; Sổ sách vẫn được bảo toàn 100%.
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-white/10 pt-4">
                        <button
                            type="button"
                            data-archive-modal-cancel
                            wire:click="cancelArchiveConfirm"
                            wire:loading.attr="disabled"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white cursor-pointer"
                        >
                            Hủy bỏ
                        </button>

                        <button
                            type="button"
                            data-archive-modal-confirm
                            wire:click="confirmPendingArchiveAction"
                            wire:loading.attr="disabled"
                            wire:target="confirmPendingArchiveAction"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border px-4 text-xs font-bold transition-colors cursor-pointer {{ $isUnarchive ? 'border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500' : 'border-amber-500/50 bg-amber-500 text-slate-950 hover:bg-amber-400' }}"
                        >
                            <span wire:loading.remove wire:target="confirmPendingArchiveAction">{{ $confirmLabel }}</span>
                            <span wire:loading wire:target="confirmPendingArchiveAction">Đang xử lý...</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
