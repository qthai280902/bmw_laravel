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
        <form method="GET" action="{{ route('admin.ai-conversations.index') }}" class="grid grid-cols-1 gap-4 xl:grid-cols-[1.6fr_1fr_1fr_1fr_1fr_auto] xl:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm">
                <input
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="w-full border border-zinc-800 bg-black/60 px-4 py-3 text-sm text-white placeholder:text-zinc-700 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                    placeholder="Tên, IP, visitor, 330i, S1000RR..."
                >
            </x-admin.form-field>

            <x-admin.form-field name="identity" label="Nhận diện">
                <select id="identity" name="identity" class="w-full border border-zinc-800 bg-black/60 px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả</option>
                    <option value="linked" @selected(request('identity') === 'linked')>Đã linked</option>
                    <option value="unknown" @selected(request('identity') === 'unknown')>Chưa biết khách</option>
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="interest" label="Intent">
                <select id="interest" name="interest" class="w-full border border-zinc-800 bg-black/60 px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả</option>
                    <option value="product" @selected(request('interest') === 'product')>Có hỏi sản phẩm</option>
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="range" label="Thời gian">
                <select id="range" name="range" class="w-full border border-zinc-800 bg-black/60 px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả</option>
                    <option value="today" @selected(request('range') === 'today')>Hôm nay</option>
                    <option value="7d" @selected(request('range') === '7d')>7 ngày</option>
                    <option value="30d" @selected(request('range') === '30d')>30 ngày</option>
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <select id="status" name="status" class="w-full border border-zinc-800 bg-black/60 px-4 py-3 text-sm text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-2">
                <button type="submit" class="bg-[#1C69D4] px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:bg-white hover:text-black">
                    Lọc
                </button>
                <a href="{{ route('admin.ai-conversations.index') }}" class="border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                    Reset
                </a>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-900">
                <thead class="bg-black/55">
                    <tr>
                        <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Khách</th>
                        <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Nội dung gần nhất</th>
                        <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Intent</th>
                        <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Linked</th>
                        <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Trạng thái</th>
                        <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse ($sessions as $session)
                        <tr class="transition-colors hover:bg-white/[0.03]">
                            <td class="px-5 py-5 align-top">
                                <p class="text-sm font-black text-white">{{ $session->displayLabel() }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-600">IP {{ $session->maskedIp() }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-700">Visitor {{ $session->maskedVisitorId() }}</p>
                            </td>
                            <td class="max-w-xl px-5 py-5 align-top">
                                <p class="text-sm font-semibold leading-6 text-zinc-300">{{ $session->last_message_preview ?? 'Chưa có preview' }}</p>
                                <p class="mt-2 text-xs font-medium text-zinc-600">
                                    {{ $session->message_count }} messages · {{ optional($session->last_seen_at)->diffForHumans() ?? 'chưa rõ' }}
                                </p>
                            </td>
                            <td class="px-5 py-5 align-top">
                                @if ($session->last_intent)
                                    <x-admin.badge tone="blue">{{ $session->last_intent }}</x-admin.badge>
                                @else
                                    <span class="text-xs font-semibold text-zinc-600">Chưa rõ</span>
                                @endif
                            </td>
                            <td class="px-5 py-5 align-top">
                                <p class="text-xs font-bold text-zinc-300">{{ $session->linkedSourceLabel() }}</p>
                                @if ($session->link_confidence)
                                    <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.18em] text-zinc-600">{{ $session->link_confidence }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-5 align-top">
                                <span class="inline-flex border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] {{ $session->statusColorClass() }}">
                                    {{ $session->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-5 text-right align-top">
                                <a href="{{ route('admin.ai-conversations.show', $session) }}" class="text-[10px] font-black uppercase tracking-[0.2em] text-[#70A7FF] transition-colors hover:text-white">
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
