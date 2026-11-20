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
                    class="h-10 w-full rounded-lg border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
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
                    class="h-10 w-full rounded-lg border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
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
                    class="h-10 w-full rounded-lg border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
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

            <button type="submit" class="h-10 whitespace-nowrap rounded-lg border border-[#1C69D4] bg-[#1C69D4] px-6 text-[10px] font-semibold uppercase tracking-[0.2em] text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer">
                Tạo nhân sự
            </button>
        </form>
    </x-admin.card>

    <!-- Table -->
    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/70">
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Hồ sơ nhân viên</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Địa chỉ Email</th>
                        <th class="min-w-[240px] px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Phân quyền vai trò</th>
                        <th class="px-6 py-4 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Tải việc & Hiệu suất</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Ngày ghi danh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($users as $user)
                        @php
                            $isCurrentAdmin = Auth::id() === $user->id;
                            $currentRole = $user->normalizedRole();
                        @endphp
                        <tr class="group transition-colors hover:bg-[#131A29]/60">
                            <td class="px-6 py-4.5">
                                <div class="flex items-center gap-3.5">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#070A10] transition-all group-hover:border-[#1C69D4]">
                                        <span class="text-xs font-black text-zinc-400 transition-colors group-hover:text-[#70A7FF]">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="mb-1 flex flex-wrap items-center gap-2 text-sm font-black uppercase leading-none tracking-wider text-white transition-colors group-hover:text-[#70A7FF]">
                                            <span class="truncate">{{ $user->name }}</span>
                                            @if($isCurrentAdmin)
                                                <span class="whitespace-nowrap rounded-full border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2 py-0.5 text-[9px] font-semibold text-[#70A7FF]">Bạn</span>
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
                                        class="h-9 whitespace-nowrap rounded-lg border border-white/10 bg-[#070A10] px-3.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer"
                                    >
                                        Lưu
                                    </button>
                                </form>
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-center">
                                <div class="inline-flex flex-col items-center gap-1.5">
                                    <span class="rounded-full border border-white/10 bg-[#070A10] px-2.5 py-0.5 text-[10px] font-semibold tabular-nums text-zinc-300">
                                        Đang giữ: {{ ($user->active_appointments_count ?? 0) + ($user->active_orders_count ?? 0) }} hồ sơ
                                    </span>
                                    <a
                                        href="{{ route('admin.users.performance', $user) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-[#38BDF8]/30 bg-[#38BDF8]/10 px-3 py-1.5 text-[11px] font-semibold text-[#38BDF8] transition-colors hover:border-[#38BDF8] hover:bg-[#38BDF8] hover:text-black"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span>Xem thống kê</span>
                                    </a>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-5 text-right text-[10px] font-bold uppercase tracking-widest tabular-nums text-zinc-400">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-[#070A10]">
                                        <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1zm0-4.708a5.992 5.992 0 00-5.923-4.35H9v1.5a.5.5 0 01-1 0v-1.5H7.5c-2.31 0-4.223 1.636-4.636 3.791l-.114.563h6.5z"></path></svg>
                                    </div>
                                    <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-400">Dữ liệu trống</h3>
                                    <p class="mt-2 text-[11px] font-medium text-zinc-500">Chưa có tài khoản quản trị nào ngoài hệ thống gốc.</p>
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

    <!-- Recent Staff Activity Audit Log -->
    <div class="mt-10">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.26em] text-[#70A7FF]">Audit Trail &middot; 3 Vai Trò Nội Bộ</p>
                <h3 class="mt-1 text-lg font-black uppercase tracking-wider text-white">Nhật Ký Hoạt Động Nhân Sự Gần Đây</h3>
            </div>
            <span class="whitespace-nowrap rounded-full border border-white/10 bg-[#0D121C] px-3.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">
                15 bản ghi mới nhất
            </span>
        </div>

        <x-admin.card class="!p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/[0.08] bg-[#070A10]/70">
                            <th class="whitespace-nowrap px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Thời gian</th>
                            <th class="whitespace-nowrap px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Nhân sự</th>
                            <th class="whitespace-nowrap px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Vai trò</th>
                            <th class="whitespace-nowrap px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Hành động</th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400">Mô tả chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06]">
                        @forelse(($activityLogs ?? collect()) as $log)
                            <tr class="transition-colors hover:bg-[#131A29]/60">
                                <td class="whitespace-nowrap px-6 py-4 text-[11px] font-semibold tabular-nums text-zinc-400">
                                    {{ $log->created_at?->format('H:i:s · d/m/Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="text-xs font-bold text-white">{{ $log->user_email }}</p>
                                    @if($log->ip_address)
                                        <p class="mt-0.5 text-[10px] text-zinc-500">IP: {{ $log->ip_address }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-block rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $log->user_role === 'admin' ? 'border-[#1C69D4]/50 bg-[#1C69D4]/15 text-[#70A7FF]' : ($log->user_role === 'sales' ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300' : 'border-amber-500/40 bg-amber-500/15 text-amber-300') }}">
                                        {{ $log->roleLabel() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-block rounded-full border border-white/10 bg-[#070A10] px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-200">
                                        {{ $log->actionLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs leading-relaxed text-zinc-300">
                                    {{ $log->description }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-xs font-semibold uppercase tracking-widest text-zinc-500">
                                    Chưa có nhật ký hoạt động nào được ghi nhận.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
</x-admin-layout>
