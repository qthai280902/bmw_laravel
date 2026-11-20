<x-admin-layout>
    <x-admin.page-header
        eyebrow="AI Showroom CRM"
        title="Lịch sử trợ lý"
        accent="AI"
        description="Theo dõi hội thoại public, nhận diện nhu cầu khách hàng và các phiên đã chuyển đổi sang lịch hẹn hoặc đơn phụ kiện."
    />

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <x-admin.card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Hôm nay</p>
            <p class="mt-3 text-3xl font-black text-white">{{ $stats['today'] }}</p>
        </x-admin.card>
        <x-admin.card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Đã linked khách</p>
            <p class="mt-3 text-3xl font-black text-emerald-400">{{ $stats['linked'] }}</p>
        </x-admin.card>
        <x-admin.card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Có intent sản phẩm</p>
            <p class="mt-3 text-3xl font-black text-[#70A7FF]">{{ $stats['interested'] }}</p>
        </x-admin.card>
        <x-admin.card class="!p-5">
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-zinc-500">Fallback</p>
            <p class="mt-3 text-3xl font-black text-yellow-400">{{ $stats['fallback'] }}</p>
        </x-admin.card>
    </div>

    <x-admin.card class="mb-6 !p-5">
        <form method="GET" action="{{ route('admin.ai-conversations.index') }}" class="grid grid-cols-1 gap-3 xl:grid-cols-[1.6fr_1fr_1fr_1fr_1fr_auto] xl:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm">
                <input
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="h-10 w-full rounded-lg border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    placeholder="Tên, IP, visitor, 330i, S1000RR..."
                >
            </x-admin.form-field>

            <x-admin.form-field name="identity" label="Nhận diện">
                <x-ui.custom-select
                    id="identity"
                    name="identity"
                    :value="(string) request('identity', '')"
                    :options="[
                        '' => 'Tất cả',
                        'linked' => 'Đã linked',
                        'unknown' => 'Chưa biết khách',
                    ]"
                />
            </x-admin.form-field>

            <x-admin.form-field name="interest" label="Intent">
                <x-ui.custom-select
                    id="interest"
                    name="interest"
                    :value="(string) request('interest', '')"
                    :options="[
                        '' => 'Tất cả',
                        'product' => 'Có hỏi sản phẩm',
                    ]"
                />
            </x-admin.form-field>

            <x-admin.form-field name="range" label="Thời gian">
                <x-ui.custom-select
                    id="range"
                    name="range"
                    :value="(string) request('range', '')"
                    :options="[
                        '' => 'Tất cả',
                        'today' => 'Hôm nay',
                        '7d' => '7 ngày',
                        '30d' => '30 ngày',
                    ]"
                />
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <x-ui.custom-select
                    id="status"
                    name="status"
                    :value="(string) request('status', '')"
                    :options="array_merge(['' => 'Tất cả'], $statusOptions)"
                />
            </x-admin.form-field>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 whitespace-nowrap rounded-lg bg-[#1C69D4] px-4 text-[10px] font-semibold uppercase tracking-[0.2em] text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer">
                    Lọc
                </button>
                <a href="{{ route('admin.ai-conversations.index') }}" class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-lg border border-white/10 bg-white/[0.03] px-4 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-300 transition-colors hover:border-white/25 hover:bg-white/[0.08] hover:text-white">
                    Reset
                </a>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/[0.06]">
                <thead class="bg-[#070A10]/70">
                    <tr>
                        <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Khách</th>
                        <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Nội dung gần nhất</th>
                        <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Intent</th>
                        <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Linked</th>
                        <th class="px-5 py-4 text-left text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Trạng thái</th>
                        <th class="px-5 py-4 text-right text-[10px] font-bold uppercase tracking-[0.22em] text-zinc-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse ($sessions as $session)
                        <tr class="transition-colors hover:bg-[#131A29]/60">
                            <td class="px-5 py-5 align-top">
                                <p class="text-sm font-bold text-white">{{ $session->displayLabel() }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-500">IP {{ $session->maskedIp() }}</p>
                                <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-600">Visitor {{ $session->maskedVisitorId() }}</p>
                            </td>
                            <td class="max-w-xl px-5 py-5 align-top">
                                <p class="text-sm font-medium leading-6 text-zinc-200">{{ $session->last_message_preview ?? 'Chưa có preview' }}</p>
                                <p class="mt-2 text-xs font-medium text-zinc-500">
                                    {{ $session->message_count }} messages · {{ optional($session->last_seen_at)->diffForHumans() ?? 'chưa rõ' }}
                                </p>
                            </td>
                            <td class="px-5 py-5 align-top">
                                @if ($session->last_intent)
                                    <x-admin.badge tone="blue">{{ $session->last_intent }}</x-admin.badge>
                                @else
                                    <span class="text-xs font-semibold text-zinc-600">Chưa rõ</span>
                                @endif
                                @if ($session->contextProductLabel())
                                    <p class="mt-2 text-xs font-bold text-[#70A7FF]">
                                        Đang xem: {{ $session->contextProductLabel() }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-5 align-top">
                                <p class="text-xs font-bold text-zinc-300">{{ $session->linkedSourceLabel() }}</p>
                                @if ($session->link_confidence)
                                    <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-500">{{ $session->link_confidence }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 align-top">
                                <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $session->statusColorClass() }}">
                                    {{ $session->statusLabel() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-5 text-right align-top">
                                <a href="{{ route('admin.ai-conversations.show', $session) }}" class="inline-flex items-center rounded-lg border border-white/10 bg-white/[0.03] px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#70A7FF] transition-colors hover:border-[#1C69D4]/50 hover:bg-[#1C69D4]/15 hover:text-white">
                                    Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-admin.empty-state
                                    title="Chưa có hội thoại AI"
                                    description="Khi khách hỏi trợ lý BMW trên public site, phiên hội thoại sẽ xuất hiện ở đây."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sessions->hasPages())
            <div class="border-t border-zinc-900 px-5 py-4">
                {{ $sessions->links() }}
            </div>
        @endif
    </x-admin.card>
</x-admin-layout>
