<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-zinc-900 border border-zinc-800 shadow-2xl overflow-hidden">
                <div class="px-8 py-6 border-b border-zinc-800 bg-zinc-900/50 flex justify-between items-center">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-white">Quản lý Khách hàng & Phân hạng VIP</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-800 bg-zinc-950/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">ID</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Họ tên</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Email</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic text-right">Ngày tham gia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            @foreach($users as $user)
                                <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                                    <td class="px-8 py-8 font-black text-xs text-zinc-500">#{{ $user->id }}</td>
                                    <td class="px-8 py-8">
                                        <div class="text-sm font-black uppercase tracking-wider text-white group-hover:text-[#1C69D4] transition-colors italic">
                                            {{ $user->name }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-xs font-medium text-zinc-400">{{ $user->email }}</td>
                                    <td class="px-8 py-8 text-right text-[10px] font-bold text-zinc-500 uppercase">
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="px-8 py-6 border-t border-zinc-800 bg-zinc-950/50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
