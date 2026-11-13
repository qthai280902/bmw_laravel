<x-admin-layout>
    <x-admin.page-header
        eyebrow="BMW Showroom CRM • Lead Analytics"
        title="Phân Tích Khách Hàng Tiềm Năng"
        description="Tổng quan lịch hẹn, lead showroom, trade-in và dịch vụ từ hệ thống CRM."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center justify-center border border-zinc-700 bg-white px-5 py-2.5 text-xs font-bold text-black transition-colors hover:bg-[#1C69D4] hover:text-white hover:border-[#1C69D4] shadow-sm">
                Mở danh sách lead
            </a>
        </x-slot>
    </x-admin.page-header>

    <!-- Top 4 Luxury Stat Cards -->
    <section class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-card-luxury p-6 relative">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Tổng lead CRM</span>
                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#70A7FF] bg-blue-950/40 border border-blue-500/20">Toàn thời gian</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($totalAppointments) }}</p>
                    <p class="mt-1 text-[11px] text-zinc-500">Lịch hẹn từ mọi kênh</p>
                </div>
                <div class="p-2.5 bg-blue-500/10 border border-blue-500/20 text-[#1C69D4]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-card-luxury p-6 relative">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Lead hôm nay</span>
                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400 bg-emerald-950/40 border border-emerald-500/20">Hôm nay</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($todayAppointments) }}</p>
                    <p class="mt-1 text-[11px] text-zinc-500">Ghi nhận trong ngày</p>
                </div>
                <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-card-luxury p-6 relative">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Chờ xử lý</span>
                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400 bg-amber-950/40 border border-amber-500/20">Chờ xử lý</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($pendingAppointments) }}</p>
                    <p class="mt-1 text-[11px] text-zinc-500">Cần liên hệ phản hồi</p>
                </div>
                <div class="p-2.5 bg-amber-500/10 border border-amber-500/20 text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-card-luxury p-6 relative">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-semibold text-zinc-400">Hội thoại AI</span>
                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-purple-400 bg-purple-950/40 border border-purple-500/20">Trợ lý AI</span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <p class="text-3xl font-extrabold tabular-nums text-white">{{ number_format($aiConversationStats['today']) }}</p>
                    <p class="mt-1 text-[11px] text-zinc-500">{{ number_format($aiConversationStats['interested']) }} tín hiệu quan tâm</p>
                </div>
                <div class="p-2.5 bg-purple-500/10 border border-purple-500/20 text-purple-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5M5 5h14v11H8l-3 3V5z" />
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- AI CRM & Latest AI Leads -->
    <section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-[1fr_1.5fr]">
        <div class="bg-card-luxury overflow-hidden">
            <div class="border-b border-zinc-800/80 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Trợ Lý AI Showroom</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Tín hiệu tương tác khách hàng từ AI Widget</p>
                </div>
                <span class="w-2 h-2 rounded-full bg-[#1C69D4] animate-pulse"></span>
            </div>
            <div class="grid grid-cols-2 gap-px bg-zinc-800/60">
                <div class="bg-zinc-950/90 p-5">
                    <p class="text-[11px] font-medium text-zinc-400">Hôm nay</p>
                    <p class="mt-2 text-2xl font-bold text-white">{{ number_format($aiConversationStats['today']) }}</p>
                </div>
                <div class="bg-zinc-950/90 p-5">
                    <p class="text-[11px] font-medium text-zinc-400">Đã liên kết</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-400">{{ number_format($aiConversationStats['linked']) }}</p>
                </div>
                <div class="bg-zinc-950/90 p-5">
                    <p class="text-[11px] font-medium text-zinc-400">Quan tâm xe</p>
                    <p class="mt-2 text-2xl font-bold text-[#70A7FF]">{{ number_format($aiConversationStats['interested']) }}</p>
                </div>
                <div class="bg-zinc-950/90 p-5">
                    <p class="text-[11px] font-medium text-zinc-400">Chuyển tiếp</p>
                    <p class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($aiConversationStats['fallback']) }}</p>
                </div>
            </div>
            <div class="border-t border-zinc-800/80 px-6 py-3.5 bg-[#121215]/50">
                <a href="{{ route('admin.ai-conversations.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#70A7FF] transition-colors hover:text-white">
                    <span>Mở lịch sử trợ lý AI</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="bg-card-luxury overflow-hidden">
            <div class="border-b border-zinc-800/80 px-6 py-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Khách Hàng Tiềm Năng Từ AI Mới Nhất</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Khách hàng vừa để lại thông tin hoặc tương tác gần đây</p>
            </div>
            <div class="divide-y divide-zinc-900">
                @forelse($latestAiLeads as $lead)
                    <a href="{{ route('admin.ai-conversations.show', $lead) }}" class="flex items-center justify-between gap-5 px-6 py-4 transition-colors hover:bg-zinc-900/50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ $lead->displayLabel() }}</p>
                            <p class="mt-0.5 truncate text-xs text-zinc-400">{{ $lead->last_message_preview ?? 'Chưa có preview' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="inline-block px-2 py-0.5 text-[10px] font-semibold text-[#70A7FF] bg-blue-950/30 border border-blue-500/20">{{ $lead->last_intent ?? 'general' }}</span>
                            <p class="mt-1 text-[11px] text-zinc-500">{{ optional($lead->last_seen_at)->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center border border-zinc-800 bg-zinc-900/50 text-zinc-500 mb-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h8M8 14h5M5 5h14v11H8l-3 3V5z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-zinc-300">Chưa ghi nhận hội thoại mới từ Trợ lý ảo</p>
                        <p class="mt-1 text-xs text-zinc-500">Các phiên chat từ khách hàng showroom sẽ xuất hiện tại đây.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- 7 Day Trend & Type Distribution -->
    <section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 bg-card-luxury overflow-hidden">
            <div class="border-b border-zinc-800/80 px-6 py-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Xu Hướng Lead 7 Ngày Qua</h3>
            </div>
            <div class="flex h-64 items-end gap-3 px-6 py-6">
                @foreach($leadTrend as $day)
                    <div class="flex h-full flex-1 flex-col justify-end gap-2.5">
                        <div class="relative flex flex-1 items-end border border-zinc-800 bg-zinc-900/40">
                            <div class="w-full bg-gradient-to-t from-[#154fa0] to-[#1C69D4]" style="height: {{ $day['count'] > 0 ? max(8, (int) round(($day['count'] / $maxTrendCount) * 100)) : 2 }}%"></div>
                            <span class="absolute inset-x-0 top-2.5 text-center text-xs font-bold tabular-nums text-zinc-300">{{ $day['count'] }}</span>
                        </div>
                        <p class="text-center text-[11px] font-medium text-zinc-400">{{ $day['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-card-luxury overflow-hidden">
            <div class="border-b border-zinc-800/80 px-6 py-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Phân Bổ Theo Loại Lịch Hẹn</h3>
            </div>
            <div class="space-y-4 p-6">
                @forelse($typeDistribution as $type)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-4">
                            <p class="truncate text-xs font-medium text-zinc-300">{{ $type['label'] }}</p>
                            <p class="text-xs font-bold tabular-nums text-white">{{ $type['count'] }}</p>
                        </div>
                        <div class="h-2 border border-zinc-800 bg-zinc-900">
                            <div class="h-full bg-[#1C69D4]" style="width: {{ max(4, (int) round(($type['count'] / $typeDistributionMax) * 100)) }}%"></div>
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
        <div class="bg-card-luxury overflow-hidden">
            <div class="border-b border-zinc-800/80 px-6 py-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Top Mẫu Xe Được Quan Tâm</h3>
            </div>
            <div class="divide-y divide-zinc-900">
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
                            <p class="text-xl font-bold tabular-nums text-[#70A7FF]">{{ $productLead->getAttribute('leads_count') }}</p>
                            <p class="text-[10px] text-zinc-500">Leads</p>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-zinc-500">Chưa có lead gắn sản phẩm</div>
                @endforelse
            </div>
        </div>

        <div class="bg-card-luxury overflow-hidden">
            <div class="border-b border-zinc-800/80 px-6 py-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Khách Hàng Đặc Biệt (Thu Cũ Đổi Mới & Bảo Dưỡng)</h3>
            </div>
            <div class="grid grid-cols-1 divide-y divide-zinc-900 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                @foreach($specialLeadSections as $section)
                    <div class="p-6">
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold text-[#70A7FF] uppercase tracking-wider">
                                    {{ $section['title'] === 'Trade-in' ? 'Thu Cũ Đổi Mới (Trade-in)' : ($section['title'] === 'Service' ? 'Dịch Vụ Bảo Dưỡng (Service)' : $section['title']) }}
                                </p>
                                <p class="mt-0.5 text-xs text-zinc-400">{{ $section['subtitle'] }}</p>
                            </div>
                            <p class="text-2xl font-bold tabular-nums text-white">{{ $section['count'] }}</p>
                        </div>

                        <div class="space-y-3">
                            @forelse($section['leads'] as $appointment)
                                <div class="border border-zinc-800 bg-[#121215] p-3.5 shadow-sm">
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
    <div class="bg-card-luxury overflow-hidden">
        <div class="border-b border-zinc-800/80 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">10 Lịch Hẹn Mới Nhất</h3>
            <a href="{{ route('admin.appointments.index') }}" class="text-xs text-[#70A7FF] hover:text-white transition-colors">Xem tất cả &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/60">
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Khách hàng</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Loại lịch hẹn</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Sản phẩm</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-zinc-400">Ngày hẹn</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-zinc-400">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($latestAppointments as $appointment)
                        <tr class="transition-colors hover:bg-zinc-900/50">
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
                            <td class="px-6 py-4 text-xs tabular-nums text-zinc-400">
                                {{ $appointment->appointment_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider {{ $appointment->status->color() }}">
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
</x-admin-layout>
