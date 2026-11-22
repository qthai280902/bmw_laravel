@props([
    'record',
    'acknowledgeAction' => 'acknowledgeGuestReschedule',
])

@php
    $meta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($record->meta_data);
    $isPendingReview = ! $record->isTerminalStatus() && (bool) ($meta['reschedule_pending_review'] ?? false);
    $previousDateLabel = is_string($meta['previous_appointment_date'] ?? null) ? trim($meta['previous_appointment_date']) : '';
    $newDateLabel = $record->appointment_date?->format('H:i — d/m/Y') ?? 'Chưa ấn định';
    $rescheduleReason = is_string($meta['reschedule_reason'] ?? null) ? trim($meta['reschedule_reason']) : '';
@endphp

@if($isPendingReview)
    <section
        data-reschedule-alert-card="{{ $record->id }}"
        class="rounded-xl border border-amber-400/45 bg-amber-500/15 p-4 space-y-2.5"
    >
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-300">
                    Cảnh báo đổi lịch hẹn
                </p>
                <p class="mt-0.5 text-sm font-bold text-white">
                    Khách hàng vừa đổi lịch — Cần xác nhận tiếp nhận
                </p>
            </div>
            <span class="rounded-full border border-amber-400/40 bg-amber-500/20 px-2.5 py-0.5 text-[10px] font-bold text-amber-200">
                CHỜ DUYỆT LỊCH MỚI
            </span>
        </div>

        <div class="rounded-lg border border-amber-400/25 bg-[#070A10]/70 px-3.5 py-2.5 text-xs space-y-1">
            <p class="text-zinc-300">
                <span class="font-semibold text-zinc-400">Thời gian:</span>
                @if($previousDateLabel !== '')
                    <span class="line-through text-zinc-400 tabular-nums">{{ $previousDateLabel }}</span>
                    <span class="mx-1.5 text-amber-300">→</span>
                @endif
                <strong class="text-amber-200 tabular-nums">{{ $newDateLabel }}</strong>
            </p>
            <p class="text-zinc-300">
                <span class="font-semibold text-zinc-400">Lý do khách đổi:</span>
                <span>{{ $rescheduleReason !== '' ? $rescheduleReason : 'Khách hàng không để lại ghi chú cụ thể.' }}</span>
            </p>
        </div>

        <div class="pt-0.5">
            <button
                type="button"
                wire:click="{{ $acknowledgeAction }}({{ $record->id }})"
                data-action="acknowledge-reschedule-{{ $record->id }}"
                class="inline-flex h-9 items-center justify-center rounded-lg bg-amber-500 px-4 text-xs font-bold text-slate-950 transition-colors hover:bg-amber-400 cursor-pointer"
            >
                Xác nhận đã tiếp nhận lịch mới
            </button>
        </div>
    </section>
@endif
