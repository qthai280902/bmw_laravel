<x-admin-layout>
    <x-admin.page-header
        eyebrow="Access control"
        title="Quản trị viên"
        accent="& Nhân sự"
        description="Quản lý tài khoản nội bộ Showroom và phân quyền 3 vai trò Quản Lý, Tư Vấn Bán Hàng (Sales), Marketing."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng nhân sự</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $users->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    <!-- Create Staff Card -->
    <x-admin.card class="mb-8 !p-6">
        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#70A7FF]">Thêm nhân sự Showroom</p>
        <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-[1.2fr_1.3fr_1fr_1fr_auto] lg:items-end">
            @csrf
            <x-admin.form-field name="name" label="Họ và tên">
                <input
                    id="staff-name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="Nguyễn Văn A..."
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs font-medium text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="email" label="Email nội bộ / Allowlist">
                <input
                    id="staff-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="sales01@bmw.com"
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs font-medium text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="password" label="Mật khẩu">
                <input
                    id="staff-password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    placeholder="Tối thiểu 8 ký tự"
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs font-medium text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="role" label="Vai trò">
                <x-ui.custom-select
                    id="staff-role"
                    name="role"
                    :value="(string) old('role', 'sales')"
                    :options="$roles"
                />
            </x-admin.form-field>

            <button type="submit" class="h-10 whitespace-nowrap rounded-none border border-[#1C69D4] bg-[#1C69D4] px-6 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:bg-white hover:text-black cursor-pointer">
                Tạo nhân sự
            </button>
        </form>
    </x-admin.card>

    <!-- Table -->
    <x-admin.card class="!p-0 border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Hồ sơ nhân viên</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Địa chỉ Email</th>
                        <th class="min-w-[240px] px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Phân quyền vai trò</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Ngày ghi danh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($users as $user)
                        @php
                            $isCurrentAdmin = Auth::id() === $user->id;
                            $currentRole = $user->normalizedRole();
                        @endphp
                        <tr class="group transition-colors hover:bg-zinc-800/30">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3.5">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none border border-zinc-800 bg-zinc-950 transition-all group-hover:border-[#1C69D4]">
                                        <span class="text-xs font-black text-zinc-500 transition-colors group-hover:text-[#70A7FF]">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="mb-1 flex flex-wrap items-center gap-2 text-sm font-black uppercase leading-none tracking-wider text-white transition-colors group-hover:text-[#70A7FF]">
                                            <span class="truncate">{{ $user->name }}</span>
                                            @if($isCurrentAdmin)
                                                <span class="whitespace-nowrap border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-1.5 py-0.5 text-[9px] text-[#70A7FF]">Bạn</span>
                                            @endif
                                        </div>
                                        <div class="whitespace-nowrap text-[9px] font-black uppercase tracking-widest text-zinc-500">
                                            ID NHÂN VIÊN: #STF-{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span class="text-xs font-medium text-zinc-300">{{ $user->email }}</span>
                                </div>
                                @if(! $user->isAdmin())
                                    <p class="mt-1 text-[10px] font-semibold text-amber-400">Chưa có đuôi @bmw.com hoặc trong ADMIN_EMAILS</p>
                                @endif
                            </td>
                            <td class="min-w-[240px] whitespace-nowrap px-6 py-5">
                                <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="w-40">
                                        <x-ui.custom-select
                                            compact
                                            name="role"
                                            aria-label="Vai trò của {{ $user->name }}"
                                            :value="$currentRole"
                                            :options="$roles"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        class="h-9 whitespace-nowrap rounded-none border border-zinc-700 bg-zinc-900 px-3.5 text-[9px] font-black uppercase tracking-[0.18em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer"
                                    >
                                        Lưu
                                    </button>
                                </form>
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-right text-[10px] font-black uppercase tracking-widest tabular-nums text-zinc-400">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-32 text-center">
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
