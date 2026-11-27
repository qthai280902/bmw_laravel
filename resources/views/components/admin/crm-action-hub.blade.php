@props([
    'contextKey',
    'recordId',
    'statusValue',
    'statusLabel',
    'confirmLabel' => 'Duyệt & Xác nhận',
    'confirmHint' => 'Xác nhận hồ sơ & gửi email thông báo',
    'completeLabel' => 'Hoàn tất',
    'completeHint' => 'Đánh dấu hoàn tất quy trình xử lý',
    'completeAction' => null,
    'cancelLabel' => 'Hủy hồ sơ',
    'completedMessage' => 'Hồ sơ đã hoàn tất — Quy trình xử lý đã đóng và khóa chuyển trạng thái.',
    'cancelledMessage' => 'Hồ sơ đã bị hủy — Không thể khôi phục hoặc duyệt lại.',
    'rescheduleLocked' => false,
    'errorMessage' => null,
])

@php
    $isPending = $statusValue === 'pending';
    $isConfirmed = $statusValue === 'confirmed';
    $isCompleted = $statusValue === 'completed';
    $isCancelled = $statusValue === 'cancelled';
    $isRescheduleLocked = (bool) $rescheduleLocked && ! $isCompleted && ! $isCancelled;
@endphp

<section
    wire:key="crm-action-hub-{{ $contextKey }}-{{ $recordId }}-{{ $statusValue }}-{{ $isRescheduleLocked ? 'reschedule-locked' : 'open' }}"
    data-crm-action-hub="{{ $contextKey }}-{{ $recordId }}"
    data-reschedule-locked="{{ $isRescheduleLocked ? 'true' : 'false' }}"
    class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3"
>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold text-[#38BDF8]">4. Thao tác xử lý hồ sơ</p>
        <span class="rounded-full border border-white/10 bg-[#0D121C] px-2.5 py-0.5 text-[10px] font-semibold text-zinc-300">
            {{ $statusLabel }}
        </span>
    </div>

    @if($isRescheduleLocked)
        <div
            data-testid="reschedule-action-lock-{{ $contextKey }}-{{ $recordId }}"
            class="rounded-xl border border-amber-400/40 bg-amber-500/15 px-3.5 py-2.5 text-xs font-medium leading-relaxed text-amber-200"
        >
            <strong class="font-bold text-amber-300">Tạm khóa chuyển trạng thái:</strong> Khách hàng vừa đổi lịch hẹn. Vui lòng bấm <strong>"Xác nhận đã tiếp nhận lịch mới"</strong> ở mục Cảnh báo đổi lịch phía trên trước khi thao tác tiếp.
        </div>
    @endif

    @if(filled($errorMessage))
        <div class="rounded-xl border border-rose-500/35 bg-rose-500/15 px-3.5 py-2.5 text-xs font-medium text-rose-200">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="{{ $isRescheduleLocked ? 'opacity-40 pointer-events-none select-none' : '' }} space-y-3">
        @if($isPending)
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                <button
                    type="button"
                    @if(! $isRescheduleLocked)
                        wire:click="updateStatus({{ $recordId }}, 'confirmed')"
                    @endif
                    wire:loading.attr="disabled"
                    @disabled($isRescheduleLocked)
                    data-testid="action-confirm-{{ $contextKey }}-{{ $recordId }}"
                    class="flex flex-col items-center justify-center rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-3 py-2.5 text-center transition-colors hover:bg-emerald-600 hover:text-white cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span class="text-xs font-bold text-emerald-200">{{ $confirmLabel }}</span>
                    <span class="mt-0.5 text-[10px] text-emerald-300/80">{{ $confirmHint }}</span>
                </button>

                <button
                    type="button"
                    disabled
                    aria-disabled="true"
                    data-testid="step-locked-complete-{{ $contextKey }}-{{ $recordId }}"
                    title="Cần Duyệt & Xác nhận trước khi Hoàn tất"
                    class="flex flex-col items-center justify-center rounded-xl border border-white/10 bg-[#0D121C]/60 px-3 py-2.5 text-center opacity-40 cursor-not-allowed select-none"
                >
                    <span class="text-xs font-bold text-zinc-400">{{ $completeLabel }}</span>
                    <span class="mt-0.5 text-[10px] text-zinc-500">Cần duyệt xác nhận trước</span>
                </button>

                <button
                    type="button"
                    @if(! $isRescheduleLocked)
                        wire:click="updateStatus({{ $recordId }}, 'cancelled')"
                    @endif
                    wire:loading.attr="disabled"
                    @disabled($isRescheduleLocked)
                    data-testid="action-cancel-{{ $contextKey }}-{{ $recordId }}"
                    class="flex flex-col items-center justify-center rounded-xl border border-rose-500/35 bg-rose-500/10 px-3 py-2.5 text-center transition-colors hover:bg-rose-600 hover:text-white cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span class="text-xs font-bold text-rose-200">{{ $cancelLabel }}</span>
                    <span class="mt-0.5 text-[10px] text-rose-300/80">Đóng hồ sơ theo yêu cầu</span>
                </button>
            </div>
        @elseif($isConfirmed)
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                <button
                    type="button"
                    disabled
                    aria-disabled="true"
                    data-testid="step-done-confirm-{{ $contextKey }}-{{ $recordId }}"
                    title="Bước Duyệt & Xác nhận đã thực hiện"
                    class="flex flex-col items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/[0.06] px-3 py-2.5 text-center opacity-45 cursor-not-allowed select-none"
                >
                    <span class="text-xs font-bold text-emerald-300">✓ Đã xác nhận</span>
                    <span class="mt-0.5 text-[10px] text-zinc-400">Bước 1 đã hoàn thành</span>
                </button>

                <button
                    type="button"
                    @if(! $isRescheduleLocked)
                        wire:click="{{ $completeAction ?: "updateStatus({$recordId}, 'completed')" }}"
                    @endif
                    wire:loading.attr="disabled"
                    @disabled($isRescheduleLocked)
                    data-testid="action-complete-{{ $contextKey }}-{{ $recordId }}"
                    class="flex flex-col items-center justify-center rounded-xl border border-[#1C69D4]/50 bg-[#1C69D4]/20 px-3 py-2.5 text-center transition-colors hover:bg-[#1C69D4] hover:text-white cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span class="text-xs font-bold text-[#70A7FF]">{{ $completeLabel }}</span>
                    <span class="mt-0.5 text-[10px] text-zinc-300">{{ $completeHint }}</span>
                </button>

                <button
                    type="button"
                    @if(! $isRescheduleLocked)
                        wire:click="updateStatus({{ $recordId }}, 'cancelled')"
                    @endif
                    wire:loading.attr="disabled"
                    @disabled($isRescheduleLocked)
                    data-testid="action-cancel-{{ $contextKey }}-{{ $recordId }}"
                    class="flex flex-col items-center justify-center rounded-xl border border-rose-500/35 bg-rose-500/10 px-3 py-2.5 text-center transition-colors hover:bg-rose-600 hover:text-white cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
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
    </div>
</section>
