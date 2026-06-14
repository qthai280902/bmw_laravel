<x-admin-layout>
    <x-admin.page-header
        eyebrow="BMW Showroom CRM"
        title="Lead Analytics"
        description="Tổng quan lịch hẹn, lead showroom, trade-in và dịch vụ từ hệ thống CRM."
    >
        <x-slot name="actions">
            <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center justify-center border border-white bg-white px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-black transition-colors hover:bg-[#1C69D4] hover:text-white">
                Mở danh sách lead
            </a>
        </x-slot>
    </x-admin.page-header>

    <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-admin.card class="!p-6">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Tổng lead</p>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-4xl font-black tabular-nums text-white">{{ number_format($totalAppointments) }}</p>
                <x-admin.badge tone="blue">All time</x-admin.badge>
            </div>
        </x-admin.card>

        <x-admin.card class="!p-6">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Hôm nay</p>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-4xl font-black tabular-nums text-white">{{ number_format($todayAppointments) }}</p>
                <x-admin.badge tone="emerald">Today</x-admin.badge>
            </div>
        </x-admin.card>

        <x-admin.card class="!p-6">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Chưa xử lý</p>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-4xl font-black tabular-nums text-white">{{ number_format($pendingAppointments) }}</p>
                <x-admin.badge tone="yellow">Pending</x-admin.badge>
            </div>
        </x-admin.card>
    </section>

    <section class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-admin.card class="xl:col-span-2 !p-0 overflow-hidden">
            <div class="border-b border-zinc-900 px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Xu hướng 7 ngày</p>
            </div>
            <div class="flex h-72 items-end gap-3 px-6 py-6">
                @foreach($leadTrend as $day)
                    <div class="flex h-full flex-1 flex-col justify-end gap-3">
                        <div class="relative flex flex-1 items-end border border-zinc-800 bg-black">
                            <div class="w-full bg-[#1C69D4]" style="height: {{ $day['count'] > 0 ? max(8, (int) round(($day['count'] / $maxTrendCount) * 100)) : 2 }}%"></div>
                            <span class="absolute inset-x-0 top-3 text-center text-[10px] font-black tabular-nums text-zinc-300">{{ $day['count'] }}</span>
                        </div>
                        <p class="text-center text-[10px] font-black uppercase tracking-tight text-zinc-600">{{ $day['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card class="!p-0 overflow-hidden">
            <div class="border-b border-zinc-900 px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Phân bổ theo type</p>
            </div>
            <div class="space-y-5 p-6">
                @forelse($typeDistribution as $type)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <p class="truncate text-[11px] font-black uppercase tracking-widest text-zinc-300">{{ $type['label'] }}</p>
                            <p class="text-xs font-black tabular-nums text-white">{{ $type['count'] }}</p>
                        </div>
                        <div class="h-2 border border-zinc-800 bg-black">
                            <div class="h-full bg-white" style="width: {{ max(4, (int) round(($type['count'] / $typeDistributionMax) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state title="Chưa có dữ liệu type" />
                @endforelse
            </div>
        </x-admin.card>
    </section>

    <section class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-admin.card class="!p-0 overflow-hidden">
            <div class="border-b border-zinc-900 px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Top xe được quan tâm</p>
            </div>
            <div class="divide-y divide-zinc-900">
                @forelse($topProductLeads as $productLead)
                    <div class="flex items-center justify-between gap-5 px-6 py-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black uppercase tracking-wider text-white">
                                {{ $productLead->product?->name ?? 'Sản phẩm không khả dụng' }}
                            </p>
                            <p class="mt-1 truncate text-[10px] font-black uppercase tracking-widest text-zinc-600">
                                {{ $productLead->product?->category?->name ?? 'Chưa phân loại' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black tabular-nums text-[#70A7FF]">{{ $productLead->getAttribute('leads_count') }}</p>
                            <p class="text-[9px] font-black uppercase tracking-widest text-zinc-600">Leads</p>
                        </div>
                    </div>
                @empty
                    <div class="p-6">
                        <x-admin.empty-state title="Chưa có lead gắn sản phẩm" />
                    </div>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card class="!p-0 overflow-hidden">
            <div class="border-b border-zinc-900 px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Lead đặc biệt</p>
            </div>
            <div class="grid grid-cols-1 divide-y divide-zinc-900 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                @foreach($specialLeadSections as $section)
                    <div class="p-6">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#70A7FF]">{{ $section['title'] }}</p>
                                <p class="mt-2 text-xs font-medium text-zinc-500">{{ $section['subtitle'] }}</p>
                            </div>
                            <p class="text-3xl font-black tabular-nums text-white">{{ $section['count'] }}</p>
                        </div>

                        <div class="space-y-3">
                            @forelse($section['leads'] as $appointment)
                                <div class="border border-zinc-900 bg-black p-4">
                                    <p class="truncate text-xs font-black uppercase tracking-wider text-white">
                                        {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}
                                    </p>
                                    <p class="mt-1 truncate text-[10px] font-bold text-zinc-500">
                                        {{ $appointment->product?->name ?? $appointment->type->label() }}
                                    </p>
                                </div>
                            @empty
                                <x-admin.empty-state title="Chưa có lead" />
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    </section>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="border-b border-zinc-900 px-6 py-5">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">10 lịch hẹn mới nhất</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Khách hàng</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Type</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Sản phẩm</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Ngày hẹn</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($latestAppointments as $appointment)
                        <tr class="transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <p class="text-xs font-black uppercase tracking-wider text-white">
                                    {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}
                                </p>
                                <p class="mt-1 text-[10px] font-bold text-zinc-600">
                                    {{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chưa có liên hệ' }}
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#70A7FF]">{{ $appointment->type->label() }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-56 truncate text-xs font-black uppercase tracking-wider text-zinc-300">
                                    {{ $appointment->product?->name ?? 'Chưa gắn sản phẩm' }}
                                </p>
                            </td>
                            <td class="px-6 py-5 text-xs font-black tabular-nums text-zinc-400">
                                {{ $appointment->appointment_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="border px-3 py-1 text-[9px] font-black uppercase tracking-widest {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <x-admin.empty-state title="Chưa có lịch hẹn nào trong CRM" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</x-admin-layout>
