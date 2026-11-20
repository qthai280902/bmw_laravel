@props([
    'contextKey',
    'recordId',
    'statusValue',
    'statusLabel',
    'confirmLabel' => 'Duyệt & Xác nhận',
    'confirmHint' => 'Xác nhận hồ sơ & gửi email thông báo',
    'completeLabel' => 'Hoàn tất',
    'completeHint' => 'Đánh dấu hoàn tất quy trình xử lý',
    'cancelLabel' => 'Hủy hồ sơ',
    'completedMessage' => 'Hồ sơ đã hoàn tất — Quy trình xử lý đã đóng và khóa chuyển trạng thái.',
    'cancelledMessage' => 'Hồ sơ đã bị hủy — Không thể khôi phục hoặc duyệt lại.',
    'errorMessage' => null,
])

@php
    $isPending = $statusValue === 'pending';
    $isConfirmed = $statusValue === 'confirmed';
    $isCompleted = $statusValue === 'completed';
    $isCancelled = $statusValue === 'cancelled';
@endphp

<section
    wire:key="crm-action-hub-{{ $contextKey }}-{{ $recordId }}-{{ $statusValue }}"
    class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3"
>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold text-[#38BDF8]">4. Thao tác xử lý hồ sơ</p>
        <span class="rounded-full border border-white/10 bg-[#0D121C] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
            {{ $statusLabel }}
        </span>
    </div>

    @if(filled($errorMessage))
        <div class="rounded-xl border border-rose-500/35 bg-rose-500/15 px-3.5 py-2.5 text-xs font-medium text-rose-200">
            {{ $errorMessage }}
        </div>
    @endif

    @if($isPending)
        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <button
                type="button"
                wire:click="updateStatus({{ $recordId }}, 'confirmed')"
                wire:loading.attr="disabled"
                data-testid="action-confirm-{{ $contextKey }}-{{ $recordId }}"
                class="flex flex-col items-center justify-center rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-2.5 text-center transition-colors hover:bg-emerald-600 hover:text-white cursor-pointer disabled:opacity-60"
            >
                <span class="text-xs font-bold text-emerald-200">{{ $confirmLabel }}</span>
                <span class="mt-0.5 text-[10px] text-emerald-300/80">{{ $confirmHint }}</span>
            </button>

            <button
                type="button"
                wire:click="updateStatus({{ $recordId }}, 'cancelled')"
                wire:loading.attr="disabled"
                data-testid="action-cancel-{{ $contextKey }}-{{ $recordId }}"
                class="flex flex-col items-center justify-center rounded-xl border border-rose-500/35 bg-rose-500/10 px-4 py-2.5 text-center transition-colors hover:bg-rose-600 hover:text-white cursor-pointer disabled:opacity-60"
            >
                <span class="text-xs font-bold text-rose-200">{{ $cancelLabel }}</span>
                <span class="mt-0.5 text-[10px] text-rose-300/80">Đóng hồ sơ theo yêu cầu</span>
            </button>
        </div>
    @elseif($isConfirmed)
        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <button
                type="button"
                wire:click="updateStatus({{ $recordId }}, 'completed')"
                wire:loading.attr="disabled"
                data-testid="action-complete-{{ $contextKey }}-{{ $recordId }}"
                class="flex flex-col items-center justify-center rounded-xl border border-[#1C69D4]/50 bg-[#1C69D4]/20 px-4 py-2.5 text-center transition-colors hover:bg-[#1C69D4] hover:text-white cursor-pointer disabled:opacity-60"
            >
                <span class="text-xs font-bold text-[#70A7FF]">{{ $completeLabel }}</span>
                <span class="mt-0.5 text-[10px] text-zinc-300">{{ $completeHint }}</span>
            </button>

            <button
                type="button"
                wire:click="updateStatus({{ $recordId }}, 'cancelled')"
                wire:loading.attr="disabled"
                data-testid="action-cancel-{{ $contextKey }}-{{ $recordId }}"
                class="flex flex-col items-center justify-center rounded-xl border border-rose-500/35 bg-rose-500/10 px-4 py-2.5 text-center transition-colors hover:bg-rose-600 hover:text-white cursor-pointer disabled:opacity-60"
            >
                <span class="text-xs font-bold text-rose-200">{{ $cancelLabel }}</span>
                <span class="mt-0.5 text-[10px] text-rose-300/80">Hủy hồ sơ đang xác nhận</span>
            </button>
        </div>
    @elseif($isCompleted)
        <div
            data-testid="terminal-status-lock-{{ $contextKey }}-{{ $recordId }}"
            class="flex items-start gap-3 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-200"
        >
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-bold text-emerald-300">Đã hoàn tất</p>
                <p class="mt-0.5 text-[11px] leading-relaxed text-emerald-100/80">{{ $completedMessage }}</p>
            </div>
        </div>
    @elseif($isCancelled)
        <div
            data-testid="terminal-status-lock-{{ $contextKey }}-{{ $recordId }}"
            class="flex items-start gap-3 rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-3 text-xs text-rose-200"
        >
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <div>
                <p class="font-bold text-rose-300">Đã hủy</p>
                <p class="mt-0.5 text-[11px] leading-relaxed text-rose-100/80">{{ $cancelledMessage }}</p>
            </div>
        </div>
    @endif

    @if(isset($extraLinks) && $extraLinks->isNotEmpty())
        <div class="pt-1 space-y-2">
            {{ $extraLinks }}
        </div>
    @endif
</section>
