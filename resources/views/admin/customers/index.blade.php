<x-admin-layout>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">Cơ sở dữ liệu <span class="font-black text-[#1C69D4]">Khách hàng</span></h1>
            <p class="text-zinc-500 font-medium font-outfit">Hệ thống CRM tổng hợp dữ liệu khách hàng từ các lượt tương tác và lịch hẹn.</p>
        </div>
        <div class="px-6 py-3 bg-zinc-900 border border-zinc-800 flex items-center gap-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Tổng khách hàng:</p>
            <p class="text-lg font-black text-white leading-none">{{ $customers->total() }}</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-8 !p-6">
        <form action="{{ route('admin.customers.index') }}" method="GET" class="flex flex-wrap gap-6 items-end">
            <div class="flex-1 min-w-[300px]">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest">Tìm kiếm khách hàng</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tên, Email hoặc Số điện thoại..." 
                           class="w-full bg-zinc-950 border-zinc-800 text-xs font-bold text-white focus:border-[#1C69D4] focus:ring-0 px-10 py-3 transition-all">
                    <svg class="w-4 h-4 text-zinc-600 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <button type="submit" class="px-8 py-3 bg-white text-black font-black uppercase text-[10px] tracking-widest hover:bg-zinc-200 transition-all">
                Lọc dữ liệu
            </button>
            @if(request('search'))
                <a href="{{ route('admin.customers.index') }}" class="px-6 py-3 border border-zinc-800 text-zinc-500 font-black uppercase text-[10px] tracking-widest hover:text-white hover:bg-zinc-900 transition-all text-center">
                    Xóa tìm kiếm
                </a>
            @endif
        </form>
    </x-admin.card>

    <!-- CRM Table -->
    <x-admin.card class="!p-0 border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Thông tin khách hàng</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Liên lạc</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-center">Tương tác</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-right">Gần nhất</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($customers as $customer)
                        <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-zinc-950 border border-zinc-800 flex items-center justify-center group-hover:border-[#1C69D4] transition-all overflow-hidden">
                                        <span class="text-xs font-black text-zinc-600 group-hover:text-[#1C69D4] transition-colors">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black uppercase tracking-wider text-white group-hover:text-[#1C69D4] transition-colors leading-none mb-1">
                                            {{ $customer->name }}
                                        </div>
                                        <div class="text-[9px] text-zinc-600 font-black uppercase tracking-widest ">
                                            Premium Lead
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        <span class="text-xs font-medium text-zinc-400">{{ $customer->email }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3 h-3 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        <span class="text-xs font-medium text-zinc-400">{{ $customer->phone }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-zinc-950 border border-zinc-800 rounded-full">
                                    <span class="text-[10px] font-black text-white">{{ $customer->interactions_count }}</span>
                                    <span class="text-[8px] font-black uppercase text-zinc-500 tracking-tighter">Lượt</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="text-[10px] font-black text-zinc-400 uppercase tracking-widest tabular-nums">
                                    {{ \Carbon\Carbon::parse($customer->last_interaction)->format('d/m/Y') }}
                                </div>
                                <div class="text-[8px] text-zinc-600 font-bold uppercase tracking-tighter mt-1">
                                    {{ \Carbon\Carbon::parse($customer->last_interaction)->diffForHumans() }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-900/50 rounded-full flex items-center justify-center border border-zinc-800 mb-6">
                                        <svg class="w-6 h-6 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500">Khách hàng trống</h3>
                                    <p class="text-[10px] text-zinc-700 font-bold mt-2 uppercase tracking-tight">Cơ sở dữ liệu CRM hiện chưa thu thập được dữ liệu.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($customers->hasPages())
        <div class="mt-12">
            {{ $customers->links() }}
        </div>
    @endif
</x-admin-layout>
