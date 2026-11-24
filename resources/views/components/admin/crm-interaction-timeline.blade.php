@props([
    'events' => [],
    'heading' => '3. Lịch sử tương tác Khách hàng ↔ Hệ thống',
    'emptyText' => 'Chưa ghi nhận mốc tương tác nào cho hồ sơ này.',
])

@php
    $normalizedEvents = is_array($events) ? array_values($events) : [];
    $cycles = \App\Services\Crm\InteractionTimelineRecorder::groupEventsIntoLifecycles($normalizedEvents);
    $totalEvents = count($normalizedEvents);
    $totalCycles = count($cycles);

    $initialOpenCycles = [];
    foreach ($cycles as $cycle) {
        $initialOpenCycles[$cycle['key']] = (bool) $cycle['is_latest'];
    }
    $allCycleKeys = array_values(array_column($cycles, 'key'));
@endphp

<section
    data-crm-interaction-timeline
    data-total-events="{{ $totalEvents }}"
    data-total-cycles="{{ $totalCycles }}"
    x-data="{
        sectionOpen: true,
        openCycles: @js($initialOpenCycles),
        cycleKeys: @js($allCycleKeys),
        allExpanded() {
            return this.cycleKeys.length > 0 && this.cycleKeys.every((k) => Boolean(this.openCycles[k]));
        },
        toggleAllCycles() {
            const nextState = !this.allExpanded();
            this.cycleKeys.forEach((k) => { this.openCycles[k] = nextState; });
            if (nextState) {
                this.sectionOpen = true;
            }
        },
        toggleCycle(key) {
            this.openCycles[key] = !Boolean(this.openCycles[key]);
        }
    }"
    class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-4 space-y-3"
>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <button
            type="button"
            @click="sectionOpen = !sectionOpen"
            data-timeline-master-toggle
            class="group inline-flex min-w-0 items-center gap-2 text-left cursor-pointer"
        >
            <svg
                class="h-4 w-4 shrink-0 text-[#38BDF8] transition-transform duration-200"
                :class="sectionOpen ? 'rotate-90' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-xs font-semibold text-[#38BDF8] group-hover:text-sky-200 transition-colors">
                {{ $heading }}
            </span>
        </button>

        <div class="flex flex-wrap items-center gap-1.5">
            <span class="rounded-full border border-white/10 bg-[#0D121C] px-2.5 py-0.5 text-[10px] font-semibold tabular-nums text-zinc-300">
                {{ $totalEvents }} sự kiện · {{ $totalCycles }} chu kỳ
            </span>

            @if($totalCycles > 1)
                <button
                    type="button"
                    @click="toggleAllCycles()"
                    data-timeline-toggle-all
                    class="rounded-lg border border-white/10 bg-[#131A29] px-2.5 py-1 text-[10px] font-semibold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:text-white cursor-pointer"
                    x-text="allExpanded() ? 'Thu gọn tất cả' : 'Mở tất cả'"
                ></button>
            @endif

            <button
                type="button"
                @click="sectionOpen = !sectionOpen"
                class="rounded-lg border border-white/10 bg-[#0D121C] px-2.5 py-1 text-[10px] font-semibold text-zinc-400 transition-colors hover:border-white/25 hover:text-white cursor-pointer"
                x-text="sectionOpen ? 'Thu gọn' : 'Mở rộng'"
            ></button>
        </div>
    </div>

    <div x-show="sectionOpen" x-collapse class="space-y-2.5">
        @if($cycles === [])
            <p class="rounded-xl border border-dashed border-white/10 bg-[#0D121C]/70 px-4 py-3 text-xs text-zinc-500">
                {{ $emptyText }}
            </p>
        @else
            @foreach($cycles as $cycle)
                @php
                    $toneClasses = match ($cycle['tone']) {
                        'amber' => [
                            'border' => 'border-amber-500/30',
                            'badge' => 'border-amber-400/35 bg-amber-500/15 text-amber-200',
                            'dot' => 'bg-amber-400',
                        ],
                        'emerald' => [
                            'border' => 'border-emerald-500/30',
                            'badge' => 'border-emerald-400/35 bg-emerald-500/15 text-emerald-200',
                            'dot' => 'bg-emerald-400',
                        ],
                        'rose' => [
                            'border' => 'border-rose-500/30',
                            'badge' => 'border-rose-400/35 bg-rose-500/15 text-rose-200',
                            'dot' => 'bg-rose-400',
                        ],
                        'zinc' => [
                            'border' => 'border-white/12',
                            'badge' => 'border-white/15 bg-white/[0.06] text-zinc-300',
                            'dot' => 'bg-zinc-400',
                        ],
                        default => [
                            'border' => 'border-[#1C69D4]/30',
                            'badge' => 'border-[#1C69D4]/35 bg-[#1C69D4]/15 text-[#70A7FF]',
                            'dot' => 'bg-[#38BDF8]',
                        ],
                    };
                    $cycleKey = $cycle['key'];
                @endphp
                <div
                    data-timeline-cycle="{{ $cycleKey }}"
                    data-cycle-kind="{{ $cycle['kind'] }}"
                    data-cycle-latest="{{ $cycle['is_latest'] ? 'true' : 'false' }}"
                    class="overflow-hidden rounded-xl border bg-[#0D121C]/90 {{ $toneClasses['border'] }}"
                >
                    <button
                        type="button"
                        @click="toggleCycle('{{ $cycleKey }}')"
                        class="flex w-full items-start justify-between gap-3 px-3.5 py-3 text-left transition-colors hover:bg-white/[0.03] cursor-pointer"
                    >
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $toneClasses['dot'] }}"></span>
                                <span class="text-xs font-bold text-white">
                                    Chu kỳ {{ $cycle['index'] }}: {{ $cycle['label'] }}
                                </span>
                                @if($cycle['is_latest'])
                                    <span class="rounded-full border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-[#70A7FF]">
                                        Gần nhất
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 pl-4 text-[10px] text-zinc-400">
                                <span class="font-semibold text-zinc-300">{{ $cycle['flow_summary'] }}</span>
                                @if($cycle['time_range'] !== '')
                                    <span class="text-zinc-600">·</span>
                                    <span class="tabular-nums">{{ $cycle['time_range'] }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 pt-0.5">
                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold tabular-nums {{ $toneClasses['badge'] }}">
                                {{ $cycle['event_count'] }} mốc
                            </span>
                            <svg
                                class="h-4 w-4 text-zinc-400 transition-transform duration-200"
                                :class="openCycles['{{ $cycleKey }}'] ? 'rotate-180 text-white' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <div
                        x-show="openCycles['{{ $cycleKey }}']"
                        x-collapse
                        class="border-t border-white/[0.06] bg-[#070A10]/60 px-3.5 py-3"
                    >
                        <ol class="relative space-y-2.5 before:absolute before:left-[9px] before:top-2 before:bottom-2 before:w-px before:bg-white/10">
                            @foreach($cycle['events'] as $event)
                                @php
                                    $actorType = $event['actor_type'] ?? 'system';
                                    $dotClass = match ($actorType) {
                                        'customer' => 'border-[#38BDF8] bg-[#1C69D4]',
                                        'admin' => 'border-emerald-300 bg-emerald-500',
                                        default => 'border-amber-300 bg-amber-500',
                                    };
                                    $pillClass = match ($actorType) {
                                        'customer' => 'border-[#1C69D4]/30 bg-[#1C69D4]/15 text-[#70A7FF]',
                                        'admin' => 'border-emerald-500/30 bg-emerald-500/15 text-emerald-300',
                                        default => 'border-amber-500/30 bg-amber-500/15 text-amber-300',
                                    };
                                    $actorBadge = match ($actorType) {
                                        'customer' => 'KHÁCH HÀNG',
                                        'admin' => 'ADMIN / SHOWROOM',
                                        default => 'HỆ THỐNG TỰ ĐỘNG',
                                    };
                                    $actorLabel = trim((string) ($event['actor_label'] ?? ''));
                                @endphp
                                <li class="relative pl-6">
                                    <span class="absolute left-[4px] top-1.5 h-[11px] w-[11px] rounded-full border-2 {{ $dotClass }}"></span>
                                    <div class="rounded-lg border border-white/[0.06] bg-[#0D121C] px-3 py-2.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="rounded-full border px-2 py-0.5 text-[9px] font-bold tracking-wider {{ $pillClass }}">
                                                    {{ $actorBadge }}
                                                </span>
                                                @if($actorLabel !== '' && mb_strtolower($actorLabel, 'UTF-8') !== mb_strtolower($actorBadge, 'UTF-8'))
                                                    <span class="text-[11px] font-semibold text-zinc-200">
                                                        {{ $actorLabel }}
                                                    </span>
                                                @endif
                                                @if(! empty($event['channel']))
                                                    <span class="rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] font-medium text-zinc-400">
                                                        {{ $event['channel'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if(! empty($event['occurred_at']))
                                                <span class="text-[10px] font-semibold tabular-nums text-zinc-500">
                                                    {{ $event['occurred_at'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 text-xs font-semibold text-white">
                                            {{ $event['title'] ?? 'Cập nhật hồ sơ' }}
                                        </p>
                                        @if(! empty($event['detail']))
                                            <p class="mt-1 text-[11px] leading-relaxed text-zinc-400">
                                                {{ $event['detail'] }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</section>
