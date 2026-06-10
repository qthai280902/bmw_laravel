<x-admin-layout>
    <div class="mb-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="mb-3 text-[10px] font-black uppercase tracking-[0.3em] text-zinc-600">BMW Showroom CRM</p>
            <h1 class="text-4xl font-light uppercase tracking-tight text-white md:text-6xl">
                Lead <span class="font-black text-[#1C69D4]">Analytics</span>
            </h1>
            <p class="mt-3 max-w-2xl text-sm font-medium text-zinc-500">
                Tong quan lich hen, lead showroom, trade-in va dich vu tu he thong CRM.
            </p>
        </div>

        <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center justify-center border border-zinc-700 bg-white px-6 py-3 text-[10px] font-black uppercase tracking-[0.2em] text-black transition-all hover:bg-zinc-200">
            Mo danh sach lead
        </a>
    </div>

    <section class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="border border-zinc-800 bg-zinc-900 p-6">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Tong lead</p>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-5xl font-black tabular-nums text-white">{{ number_format($totalAppointments) }}</p>
                <span class="border border-[#1C69D4]/30 bg-[#1C69D4]/10 px-3 py-1 text-[9px] font-black uppercase tracking-widest text-[#1C69D4]">All time</span>
            </div>
        </div>

        <div class="border border-zinc-800 bg-zinc-900 p-6">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Hom nay</p>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-5xl font-black tabular-nums text-white">{{ number_format($todayAppointments) }}</p>
                <span class="border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[9px] font-black uppercase tracking-widest text-emerald-400">Today</span>
            </div>
        </div>

        <div class="border border-zinc-800 bg-zinc-900 p-6">
            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-zinc-600">Chua xu ly</p>
            <div class="mt-5 flex items-end justify-between gap-4">
                <p class="text-5xl font-black tabular-nums text-white">{{ number_format($pendingAppointments) }}</p>
                <span class="border border-yellow-500/30 bg-yellow-500/10 px-3 py-1 text-[9px] font-black uppercase tracking-widest text-yellow-400">Pending</span>
            </div>
        </div>
    </section>

    <section class="mb-8 grid grid-cols-1 gap-8 xl:grid-cols-3">
        <x-admin.card class="xl:col-span-2 !p-0">
            <div class="border-b border-zinc-800 px-6 py-5">
                <h2 class="text-xs font-black uppercase tracking-[0.25em] text-white">Xu huong 7 ngay</h2>
            </div>
            <div class="flex h-72 items-end gap-3 px-6 py-6">
                @foreach($leadTrend as $day)
                    <div class="flex h-full flex-1 flex-col justify-end gap-3">
                        <div class="relative flex flex-1 items-end border border-zinc-800 bg-zinc-950">
                            <div class="w-full bg-[#1C69D4]" style="height: {{ $day['count'] > 0 ? max(8, (int) round(($day['count'] / $maxTrendCount) * 100)) : 2 }}%"></div>
                            <span class="absolute inset-x-0 top-3 text-center text-[10px] font-black tabular-nums text-zinc-300">{{ $day['count'] }}</span>
                        </div>
                        <p class="text-center text-[10px] font-black uppercase tracking-tight text-zinc-600">{{ $day['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card class="!p-0">
            <div class="border-b border-zinc-800 px-6 py-5">
                <h2 class="text-xs font-black uppercase tracking-[0.25em] text-white">Phan bo theo type</h2>
            </div>
            <div class="space-y-5 p-6">
                @forelse($typeDistribution as $type)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <p class="truncate text-[11px] font-black uppercase tracking-widest text-zinc-300">{{ $type['label'] }}</p>
                            <p class="text-xs font-black tabular-nums text-white">{{ $type['count'] }}</p>
                        </div>
                        <div class="h-2 border border-zinc-800 bg-zinc-950">
                            <div class="h-full bg-white" style="width: {{ max(4, (int) round(($type['count'] / $typeDistributionMax) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="border border-zinc-800 bg-zinc-950 p-8 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-zinc-600">Chua co du lieu type</p>
                    </div>
                @endforelse
            </div>
        </x-admin.card>
    </section>

    <section class="mb-8 grid grid-cols-1 gap-8 xl:grid-cols-2">
        <x-admin.card class="!p-0">
            <div class="border-b border-zinc-800 px-6 py-5">
                <h2 class="text-xs font-black uppercase tracking-[0.25em] text-white">Top xe duoc quan tam</h2>
            </div>
            <div class="divide-y divide-zinc-900">
                @forelse($topProductLeads as $productLead)
                    <div class="flex items-center justify-between gap-5 px-6 py-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black uppercase tracking-wider text-white">
                                {{ $productLead->product?->name ?? 'San pham khong kha dung' }}
                            </p>
                            <p class="mt-1 truncate text-[10px] font-black uppercase tracking-widest text-zinc-600">
                                {{ $productLead->product?->category?->name ?? 'Chua phan loai' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black tabular-nums text-[#1C69D4]">{{ $productLead->getAttribute('leads_count') }}</p>
                            <p class="text-[9px] font-black uppercase tracking-widest text-zinc-600">Leads</p>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-16 text-center">
                        <p class="text-xs font-black uppercase tracking-widest text-zinc-600">Chua co lead gan voi san pham</p>
                    </div>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card class="!p-0">
            <div class="border-b border-zinc-800 px-6 py-5">
                <h2 class="text-xs font-black uppercase tracking-[0.25em] text-white">Lead dac biet</h2>
            </div>
            <div class="grid grid-cols-1 divide-y divide-zinc-900 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                @foreach($specialLeadSections as $section)
                    <div class="p-6">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#1C69D4]">{{ $section['title'] }}</p>
                                <p class="mt-2 text-xs font-medium text-zinc-500">{{ $section['subtitle'] }}</p>
                            </div>
                            <p class="text-3xl font-black tabular-nums text-white">{{ $section['count'] }}</p>
                        </div>

                        <div class="space-y-4">
                            @forelse($section['leads'] as $appointment)
                                <div class="border border-zinc-800 bg-zinc-950 p-4">
                                    <p class="truncate text-xs font-black uppercase tracking-wider text-white">
                                        {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khach vang lai' }}
                                    </p>
                                    <p class="mt-1 truncate text-[10px] font-bold text-zinc-500">
                                        {{ $appointment->product?->name ?? $appointment->type->label() }}
                                    </p>
                                </div>
                            @empty
                                <div class="border border-zinc-800 bg-zinc-950 p-6 text-center">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Chua co lead</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.card>
    </section>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="border-b border-zinc-800 px-6 py-5">
            <h2 class="text-xs font-black uppercase tracking-[0.25em] text-white">10 lich hen moi nhat</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Khach hang</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Type</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">San pham</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Ngay hen</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 text-center">Trang thai</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 text-right">Chi tiet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($latestAppointments as $appointment)
                        <tr class="hover:bg-zinc-800/30">
                            <td class="px-6 py-5">
                                <p class="text-xs font-black uppercase tracking-wider text-white">
                                    {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khach vang lai' }}
                                </p>
                                <p class="mt-1 text-[10px] font-bold text-zinc-600">
                                    {{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chua co lien he' }}
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#1C69D4]">{{ $appointment->type->label() }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <p class="max-w-56 truncate text-xs font-black uppercase tracking-wider text-zinc-300">
                                    {{ $appointment->product?->name ?? 'Chua gan san pham' }}
                                </p>
                                <p class="mt-1 max-w-56 truncate text-[10px] font-black uppercase tracking-widest text-zinc-700">
                                    {{ $appointment->product?->category?->name ?? 'N/A' }}
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
                            <td class="px-6 py-5 text-right">
                                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-700">Chua co route show</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <p class="text-xs font-black uppercase tracking-widest text-zinc-600">Chua co lich hen nao trong CRM</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</x-admin-layout>
