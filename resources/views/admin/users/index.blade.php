<x-admin-layout>
    <x-admin.page-header
        eyebrow="Access control"
        title="Quản trị viên"
        accent="& Nhân sự"
        description="Danh sách các tài khoản có quyền truy cập quản trị BMW."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng nhân sự</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $users->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Table -->
    <x-admin.card class="!p-0 border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Hồ sơ nhân viên</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Địa chỉ Email</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-right">Ngày ghi danh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($users as $user)
                        <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-zinc-950 border border-zinc-800 flex items-center justify-center group-hover:border-[#1C69D4] transition-all overflow-hidden">
                                        <span class="text-xs font-black text-zinc-600 group-hover:text-[#1C69D4] transition-colors">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black uppercase tracking-wider text-white group-hover:text-[#1C69D4] transition-colors leading-none mb-1">
                                            {{ $user->name }}
                                        </div>
                                        <div class="text-[9px] text-zinc-600 font-black uppercase tracking-widest ">
                                            ID NHÂN VIÊN: #STF-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span class="text-xs font-medium text-zinc-400 group-hover:text-zinc-200 transition-colors">{{ $user->email }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right text-[10px] font-black text-zinc-500 uppercase tracking-widest tabular-nums">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-8 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-900/50 flex items-center justify-center border border-zinc-800 mb-6">
                                        <svg class="w-6 h-6 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1zm0-4.708a5.992 5.992 0 00-5.923-4.35H9v1.5a.5.5 0 01-1 0v-1.5H7.5c-2.31 0-4.223 1.636-4.636 3.791l-.114.563h6.5z"></path></svg>
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500">Dữ liệu trống</h3>
                                    <p class="text-[10px] text-zinc-700 font-bold mt-2 uppercase tracking-tight">Chưa có tài khoản quản trị nào ngoài hệ thống gốc.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($users->hasPages())
        <div class="mt-12">
            {{ $users->links() }}
        </div>
    @endif
</x-admin-layout>
