@props([
    'events' => [],
    'heading' => 'Lịch sử tương tác Khách hàng ↔ Hệ thống',
])

<section class="rounded-2xl border border-white/[0.08] bg-[#0D121C]/80 p-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold text-[#38BDF8]">
            {{ $heading }}
        </p>
        <div class="flex flex-wrap items-center gap-3 text-[11px] font-medium">
            <span class="inline-flex items-center gap-1.5 text-[#70A7FF]">
                <span class="h-2 w-2 rounded-full bg-[#1C69D4]"></span> Khách hàng
            </span>
            <span class="inline-flex items-center gap-1.5 text-indigo-300">
                <span class="h-2 w-2 rounded-full bg-indigo-400"></span> Hệ thống
            </span>
            <span class="inline-flex items-center gap-1.5 text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Showroom
            </span>
        </div>
    </div>

    @if(empty($events))
        <p class="text-xs text-zinc-500">Chưa có mốc tương tác nào được ghi nhận.</p>
    @else
        <ol class="relative space-y-3.5 border-l border-white/[0.08] pl-5 ml-1.5">
            @foreach($events as $event)
                @php
                    $actorType = $event['actor_type'] ?? 'system';
                    $dotClass = match ($actorType) {
                        'customer' => 'bg-[#1C69D4] ring-[#1C69D4]/25',
                        'admin' => 'bg-emerald-400 ring-emerald-400/25',
                        default => 'bg-indigo-400 ring-indigo-400/25',
                    };
                    $badgeClass = match ($actorType) {
                        'customer' => 'border-[#1C69D4]/30 bg-[#1C69D4]/15 text-[#70A7FF]',
                        'admin' => 'border-emerald-500/25 bg-emerald-500/15 text-emerald-300',
                        default => 'border-indigo-400/25 bg-indigo-500/15 text-indigo-300',
                    };
                    $actorTag = match ($actorType) {
                        'customer' => 'KHÁCH HÀNG',
                        'admin' => 'ADMIN / SHOWROOM',
                        default => 'HỆ THỐNG TỰ ĐỘNG',
                    };
                @endphp
                <li class="relative">
                    <span class="absolute -left-[25px] top-2 h-2.5 w-2.5 rounded-full ring-4 {{ $dotClass }}"></span>

                    <div class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">
                                    {{ $actorTag }}
                                </span>
                                @if(!empty($event['channel']))
                                    <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[10px] font-medium text-zinc-300">
                                        {{ $event['channel'] }}
                                    </span>
                                @endif
                            </div>
                            @if(!empty($event['occurred_at']))
                                <time class="text-[11px] font-medium tabular-nums text-zinc-400">
                                    {{ $event['occurred_at'] }}
                                </time>
                            @endif
                        </div>

                        <p class="mt-2 text-xs font-bold text-white leading-snug">
                            {{ $event['title'] ?? 'Cập nhật hồ sơ' }}
                        </p>

                        @if(!empty($event['actor_label']))
                            <p class="mt-0.5 text-[11px] font-medium text-zinc-400">
                                Tác nhân: {{ $event['actor_label'] }}
                            </p>
                        @endif

                        @if(!empty($event['detail']))
                            <p class="mt-1.5 text-xs leading-relaxed text-zinc-300">
                                {{ $event['detail'] }}
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
