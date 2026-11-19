@props([
    'events' => [],
    'heading' => 'Lịch sử tương tác Khách hàng ↔ Hệ thống',
])

<section class="rounded-none border border-[#242B38] bg-[#050505] p-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">
            {{ $heading }}
        </p>
        <div class="flex flex-wrap items-center gap-2 text-[9px] font-black uppercase tracking-[0.14em]">
            <span class="inline-flex items-center gap-1 text-[#60A5FA]">
                <span class="h-1.5 w-1.5 bg-[#1C69D4]"></span> Khách hàng
            </span>
            <span class="inline-flex items-center gap-1 text-indigo-300">
                <span class="h-1.5 w-1.5 bg-indigo-400"></span> Hệ thống
            </span>
            <span class="inline-flex items-center gap-1 text-emerald-400">
                <span class="h-1.5 w-1.5 bg-emerald-500"></span> Showroom
            </span>
        </div>
    </div>

    @if(empty($events))
        <p class="text-xs text-zinc-500">Chưa có mốc tương tác nào được ghi nhận.</p>
    @else
        <ol class="relative space-y-4 border-l border-[#242B38] pl-5 ml-1.5">
            @foreach($events as $event)
                @php
                    $actorType = $event['actor_type'] ?? 'system';
                    $dotClass = match ($actorType) {
                        'customer' => 'bg-[#1C69D4] ring-[#1C69D4]/30',
                        'admin' => 'bg-[#10B981] ring-[#10B981]/30',
                        default => 'bg-[#818CF8] ring-[#818CF8]/30',
                    };
                    $badgeClass = match ($actorType) {
                        'customer' => 'border-[#1C69D4]/45 bg-[#1C69D4]/15 text-[#70A7FF]',
                        'admin' => 'border-emerald-500/45 bg-emerald-500/15 text-emerald-300',
                        default => 'border-indigo-400/45 bg-indigo-500/15 text-indigo-300',
                    };
                    $actorTag = match ($actorType) {
                        'customer' => 'KHÁCH HÀNG',
                        'admin' => 'ADMIN / SHOWROOM',
                        default => 'HỆ THỐNG TỰ ĐỘNG',
                    };
                @endphp
                <li class="relative">
                    <span class="absolute -left-[25px] top-1.5 h-2.5 w-2.5 rounded-none ring-4 {{ $dotClass }}"></span>

                    <div class="rounded-none border border-[#1E2533] bg-[#0B0F17] p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-none border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] {{ $badgeClass }}">
                                    {{ $actorTag }}
                                </span>
                                @if(!empty($event['channel']))
                                    <span class="rounded-none border border-white/10 bg-[#050505] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-[0.12em] text-zinc-400">
                                        {{ $event['channel'] }}
                                    </span>
                                @endif
                            </div>
                            @if(!empty($event['occurred_at']))
                                <time class="text-[10px] font-bold tabular-nums text-zinc-500">
                                    {{ $event['occurred_at'] }}
                                </time>
                            @endif
                        </div>

                        <p class="mt-2 text-xs font-bold text-white leading-snug">
                            {{ $event['title'] ?? 'Cập nhật hồ sơ' }}
                        </p>

                        @if(!empty($event['actor_label']))
                            <p class="mt-0.5 text-[10px] font-semibold text-zinc-400">
                                Tác nhân: {{ $event['actor_label'] }}
                            </p>
                        @endif

                        @if(!empty($event['detail']))
                            <p class="mt-1.5 text-[11px] leading-relaxed text-zinc-300">
                                {{ $event['detail'] }}
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
