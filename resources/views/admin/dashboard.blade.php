<x-admin-layout>
    <div data-dashboard-live-region x-init="if (typeof window.bmwDashboardAutoRefresh === 'function') window.bmwDashboardAutoRefresh(60000)">
    <x-admin.page-header
        eyebrow="BMW Showroom CRM · Lead Analytics"
        title="Tổng Quan Vận Hành"
        accent="Showroom"
        description="Theo dõi hiệu suất lịch hẹn, khách hàng tiềm năng, dịch vụ chăm sóc xe và hội thoại AI."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.appointments.index') }}" class="inline-flex h-10 shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white shadow-lg shadow-[#1C69D4]/25 transition-all hover:bg-[#1657B0]">
                Mở danh sách lead
            </a>
        </x-slot>
    </x-admin.page-header>

    <!-- Top 4 Rounded Luxury KPI Cards -->
    <section class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 shadow-xl shadow-black/40 transition-all hover:border-[#1C69D4]/40 hover:bg-[#131A29]">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Tổng lead CRM</span>
                <span class="inline-flex items-center rounded-full border border-[#1C69D4]/25 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[11px] font-semibold text-[#70A7FF]">Toàn thời gian</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($totalAppointments) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Lịch hẹn từ mọi kênh</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#1C69D4]/25 bg-[#1C69D4]/15 text-[#38BDF8]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 shadow-xl shadow-black/40 transition-all hover:border-emerald-500/40 hover:bg-[#131A29]">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Lead hôm nay</span>
                <span class="inline-flex items-center rounded-full border border-emerald-500/25 bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300">Hôm nay</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($todayAppointments) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Ghi nhận trong ngày</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-500/25 bg-emerald-500/15 text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 shadow-xl shadow-black/40 transition-all hover:border-amber-500/40 hover:bg-[#131A29]">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Chờ xử lý</span>
                <span class="inline-flex items-center rounded-full border border-amber-500/25 bg-amber-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-amber-300">Chờ xử lý</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($pendingAppointments) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Cần liên hệ phản hồi</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-amber-500/25 bg-amber-500/15 text-amber-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-6 shadow-xl shadow-black/40 transition-all hover:border-sky-500/40 hover:bg-[#131A29]">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Hội thoại AI</span>
                <span class="inline-flex items-center rounded-full border border-sky-500/25 bg-sky-500/15 px-2.5 py-0.5 text-[11px] font-semibold text-sky-300">Trợ lý AI</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($aiConversationStats['today']) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">{{ number_format($aiConversationStats['interested']) }} tín hiệu quan tâm</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-sky-500/25 bg-sky-500/15 text-sky-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5M5 5h14v11H8l-3 3V5z" />
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- AI CRM & Latest AI Leads -->
    <section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1.5fr]">
        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
            <div class="flex items-center justify-between border-b border-white/[0.08] px-6 py-4">
                <div>
                    <h3 class="text-sm font-bold text-white">Trợ Lý AI Showroom</h3>
                    <p class="mt-0.5 text-xs text-zinc-400">Tín hiệu tương tác khách hàng từ AI Widget</p>
                </div>
                <span class="h-2.5 w-2.5 rounded-full bg-[#38BDF8] animate-pulse"></span>
            </div>
            <div class="grid grid-cols-2 gap-3 p-5">
                <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-4">
                    <p class="text-xs font-medium text-zinc-400">Hôm nay</p>
                    <p class="mt-1.5 text-2xl font-bold tabular-nums text-white">{{ number_format($aiConversationStats['today']) }}</p>
                </div>
                <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-4">
                    <p class="text-xs font-medium text-zinc-400">Đã liên kết</p>
                    <p class="mt-1.5 text-2xl font-bold tabular-nums text-emerald-400">{{ number_format($aiConversationStats['linked']) }}</p>
                </div>
                <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-4">
                    <p class="text-xs font-medium text-zinc-400">Quan tâm xe</p>
                    <p class="mt-1.5 text-2xl font-bold tabular-nums text-[#70A7FF]">{{ number_format($aiConversationStats['interested']) }}</p>
                </div>
                <div class="rounded-xl border border-white/[0.06] bg-[#070A10]/80 p-4">
                    <p class="text-xs font-medium text-zinc-400">Chuyển tiếp</p>
                    <p class="mt-1.5 text-2xl font-bold tabular-nums text-amber-400">{{ number_format($aiConversationStats['fallback']) }}</p>
                </div>
            </div>
            <div class="border-t border-white/[0.08] bg-[#070A10]/40 px-6 py-3.5">
                <a href="{{ route('admin.ai-conversations.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#38BDF8] transition-colors hover:text-white">
                    <span>Mở lịch sử trợ lý AI</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
            <div class="border-b border-white/[0.08] px-6 py-4">
                <h3 class="text-sm font-bold text-white">Khách Hàng Tiềm Năng Từ AI Mới Nhất</h3>
                <p class="mt-0.5 text-xs text-zinc-400">Khách hàng vừa để lại thông tin hoặc tương tác gần đây</p>
            </div>
            <div class="divide-y divide-white/[0.06]">
                @forelse($latestAiLeads as $lead)
                    <a href="{{ route('admin.ai-conversations.show', $lead) }}" class="flex items-center justify-between gap-5 px-6 py-4 transition-colors hover:bg-white/[0.03]">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ $lead->displayLabel() }}</p>
                            <p class="mt-0.5 truncate text-xs text-zinc-400">{{ $lead->last_message_preview ?? 'Chưa có preview' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="inline-block rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[11px] font-semibold text-[#70A7FF]">{{ $lead->last_intent ?? 'general' }}</span>
                            <p class="mt-1 text-[11px] text-zinc-500">{{ optional($lead->last_seen_at)->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl border border-white/[0.08] bg-[#131A29] text-zinc-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h8M8 14h5M5 5h14v11H8l-3 3V5z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-zinc-300">Chưa ghi nhận hội thoại mới từ Trợ lý ảo</p>
                        <p class="mt-1 text-xs text-zinc-500">Các phiên chat từ khách hàng showroom sẽ xuất hiện tại đây.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- 7 Day Trend & Type Distribution -->
    <section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
            <div class="border-b border-white/[0.08] px-6 py-4">
                <h3 class="text-sm font-bold text-white">Xu Hướng Lead 7 Ngày Qua</h3>
            </div>
            <div class="flex h-64 items-end gap-3 px-6 py-6">
                @foreach($leadTrend as $day)
                    <div class="flex h-full flex-1 flex-col justify-end gap-2.5">
                        <div class="relative flex flex-1 items-end overflow-hidden rounded-xl border border-white/[0.08] bg-[#070A10]/70">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-[#154fa0] to-[#38BDF8]" style="height: {{ $day['count'] > 0 ? max(8, (int) round(($day['count'] / $maxTrendCount) * 100)) : 2 }}%"></div>
                            <span class="absolute inset-x-0 top-2.5 text-center text-xs font-bold tabular-nums text-zinc-200">{{ $day['count'] }}</span>
                        </div>
                        <p class="text-center text-xs font-medium text-zinc-400">{{ $day['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
            <div class="border-b border-white/[0.08] px-6 py-4">
                <h3 class="text-sm font-bold text-white">Phân Bổ Theo Loại Lịch Hẹn</h3>
            </div>
            <div class="space-y-4 p-6">
                @forelse($typeDistribution as $type)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-4">
                            <p class="truncate text-xs font-medium text-zinc-300">{{ $type['label'] }}</p>
                            <p class="text-xs font-bold tabular-nums text-white">{{ $type['count'] }}</p>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full border border-white/[0.06] bg-[#070A10]">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#1C69D4] to-[#38BDF8]" style="width: {{ max(4, (int) round(($type['count'] / $typeDistributionMax) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-zinc-500">Chưa có dữ liệu loại lịch hẹn</div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Top Products & Special Leads -->
    <section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
            <div class="border-b border-white/[0.08] px-6 py-4">
                <h3 class="text-sm font-bold text-white">Top Mẫu Xe Được Quan Tâm</h3>
            </div>
            <div class="divide-y divide-white/[0.06]">
                @forelse($topProductLeads as $productLead)
                    <div class="flex items-center justify-between gap-5 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">
                                {{ $productLead->product?->name ?? 'Sản phẩm không khả dụng' }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-zinc-400">
                                {{ $productLead->product?->category?->name ?? 'Chưa phân loại' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold tabular-nums text-[#38BDF8]">{{ $productLead->getAttribute('leads_count') }}</p>
                            <p class="text-[11px] text-zinc-500">Leads</p>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-zinc-500">Chưa có lead gắn sản phẩm</div>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
            <div class="border-b border-white/[0.08] px-6 py-4">
                <h3 class="text-sm font-bold text-white">Khách Hàng Đặc Biệt (Trade-in &amp; Bảo Dưỡng)</h3>
            </div>
            <div class="grid grid-cols-1 divide-y divide-white/[0.06] lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                @foreach($specialLeadSections as $section)
                    <div class="p-6">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold text-[#38BDF8]">
                                    {{ $section['title'] === 'Trade-in' ? 'Thu Cũ Đổi Mới (Trade-in)' : ($section['title'] === 'Service' ? 'Dịch Vụ Bảo Dưỡng (Service)' : $section['title']) }}
                                </p>
                                <p class="mt-0.5 text-xs text-zinc-400">{{ $section['subtitle'] }}</p>
                            </div>
                            <p class="text-2xl font-bold tabular-nums text-white">{{ $section['count'] }}</p>
                        </div>

                        <div class="space-y-2.5">
                            @forelse($section['leads'] as $appointment)
                                <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/70 p-3.5">
                                    <p class="truncate text-xs font-semibold text-white">
                                        {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}
                                    </p>
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-400">
                                        {{ $appointment->product?->name ?? $appointment->type->label() }}
                                    </p>
                                </div>
                            @empty
                                <div class="py-4 text-center text-xs text-zinc-500">Chưa có lead</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Recent 10 Appointments Table -->
    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl shadow-black/40">
        <div class="flex items-center justify-between border-b border-white/[0.08] px-6 py-4">
            <h3 class="text-sm font-bold text-white">10 Lịch Hẹn Mới Nhất</h3>
            <a href="{{ route('admin.appointments.index') }}" class="text-xs font-semibold text-[#38BDF8] transition-colors hover:text-white">Xem tất cả &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/60">
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Khách hàng</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Loại lịch hẹn</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Sản phẩm</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Ngày hẹn</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($latestAppointments as $appointment)
                        <tr class="transition-colors hover:bg-white/[0.03]">
                            <td class="px-6 py-4">
                                <p class="text-xs font-semibold text-white">
                                    {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-zinc-400">
                                    {{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chưa có liên hệ' }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium text-[#70A7FF]">{{ $appointment->type->label() }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="max-w-56 truncate text-xs font-medium text-zinc-300">
                                    {{ $appointment->product?->name ?? 'Chưa gắn sản phẩm' }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs tabular-nums text-zinc-400">
                                {{ $appointment->appointment_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="min-w-[140px] whitespace-nowrap px-6 py-4 text-center">
                                <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-semibold {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-xs text-zinc-500">
                                Chưa có lịch hẹn nào trong CRM
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</x-admin-layout>
