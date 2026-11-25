@php
    $backUrl = Auth::user()?->canManageSystem()
        ? route('admin.users.index')
        : (Auth::user()?->canAccessCrm() ? route('dashboard') : route('admin.products.index'));
    $backLabel = Auth::user()?->canManageSystem() ? 'Danh sách nhân sự' : 'Quay lại bảng điều khiển';
    $isActive = $staffUser->isActiveAccount();
@endphp

<x-admin-layout>
    <x-admin.page-header
        :back-url="$backUrl"
        :back-label="$backLabel"
        :eyebrow="'Hồ sơ nhân sự · #STF-' . str_pad((string) $staffUser->id, 4, '0', STR_PAD_LEFT) . ' · ' . $staffUser->roleLabel()"
        :title="$staffUser->name"
        :description="'Vai trò: ' . $staffUser->roleLabel() . ' · Email: ' . $staffUser->email . ' · Tham gia từ ' . ($staffUser->created_at?->format('d/m/Y') ?? '—')"
    >
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2.5">
                @if($canViewPerformance)
                    <a
                        href="{{ route('admin.users.performance', $staffUser) }}"
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#38BDF8]/40 bg-[#38BDF8]/15 px-4 text-xs font-semibold text-[#38BDF8] transition-colors hover:bg-[#38BDF8] hover:text-black"
                    >
                        <span>Biểu đồ hiệu suất chi tiết</span>
                    </a>
                @endif

                @if($canMutate)
                    <a
                        href="{{ route('admin.users.edit', $staffUser) }}"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#1C69D4] px-4 text-xs font-bold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6]"
                    >
                        <span>Chỉnh sửa hồ sơ</span>
                    </a>
                @endif

                @if($canToggleLock)
                    <form method="POST" action="{{ route('admin.users.toggle-active', $staffUser) }}">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border px-4 text-xs font-bold transition-colors cursor-pointer {{ $isActive ? 'border-rose-500/40 bg-rose-500/15 text-rose-300 hover:bg-rose-500 hover:text-white' : 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500 hover:text-slate-950' }}"
                        >
                            <span>{{ $isActive ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}</span>
                        </button>
                    </form>
                @endif
            </div>
        </x-slot>
    </x-admin.page-header>

    {{-- 4 Operational KPI Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Tổng Lead được giao</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-white">{{ number_format($kpis['total_leads']) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Đang mở xử lý: <strong class="text-sky-300 tabular-nums">{{ $kpis['open_leads'] }}</strong> hồ sơ</p>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Lịch hoàn thành / Chốt mua xe</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-300">
                {{ $kpis['completed_appointments'] }}
                <span class="text-sm font-bold text-[#70A7FF]">/ {{ $kpis['purchased_vehicles'] }} xe đã chốt</span>
            </p>
            <p class="mt-1 text-xs text-zinc-500">Tính trên tổng lịch hẹn &amp; lái thử phụ trách</p>
        </div>

        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Đơn phụ kiện phụ trách</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-[#70A7FF]">{{ $kpis['accessory_orders'] }}</p>
            <p class="mt-1 text-xs text-zinc-500">Đã giao hoàn tất: <strong class="text-emerald-300 tabular-nums">{{ $kpis['completed_orders'] }}</strong> đơn</p>
        </div>

        <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-5">
            <p class="text-[11px] font-semibold text-emerald-300">Doanh thu đóng góp</p>
            <p class="mt-2 truncate text-xl font-extrabold tabular-nums text-white" title="{{ number_format($kpis['contributed_revenue'], 0, ',', '.') }} VNĐ">
                {{ number_format($kpis['contributed_revenue'], 0, ',', '.') }} đ
            </p>
            <p class="mt-1 text-xs text-emerald-200/80">Phụ kiện hoàn tất: {{ number_format($kpis['accessory_revenue'], 0, ',', '.') }} đ</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[0.9fr_1.1fr] xl:items-start">
        {{-- Left Column: Staff Identity & Governance Details --}}
        <x-admin.card class="!p-6 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/[0.08] pb-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#38BDF8]">Thông tin định danh</p>
                    <h2 class="mt-1 text-base font-bold text-white">Hồ sơ &amp; Trạng thái phân quyền</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($staffUser->isTopSuperAdmin())
                        <span class="rounded-full border border-amber-500/40 bg-amber-500/15 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-300">
                            Top Super Admin
                        </span>
                    @endif
                    <span class="rounded-full border px-3 py-1 text-[10px] font-bold uppercase tracking-wider {{ $isActive ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300' : 'border-rose-500/40 bg-rose-500/15 text-rose-300' }}">
                        {{ $isActive ? 'Đang hoạt động' : 'Tài khoản bị khóa' }}
                    </span>
                </div>
            </div>

            <dl class="divide-y divide-white/[0.06] text-xs">
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Họ và tên</dt>
                    <dd class="font-bold text-white">{{ $staffUser->name }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Email công vụ</dt>
                    <dd class="font-medium text-zinc-200">{{ $staffUser->email }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Số điện thoại</dt>
                    <dd class="font-semibold tabular-nums text-zinc-200">{{ $staffUser->phone ?: 'Chưa cập nhật' }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Vai trò hệ thống</dt>
                    <dd class="font-bold text-[#70A7FF]">{{ $staffUser->roleLabel() }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Chức danh</dt>
                    <dd class="text-zinc-200">{{ $staffUser->job_title ?: 'Chuyên viên Showroom' }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Phòng ban</dt>
                    <dd class="text-zinc-200">{{ $staffUser->department ?: 'BMW Flagship Showroom' }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Ngày tham gia</dt>
                    <dd class="tabular-nums text-zinc-300">{{ $staffUser->created_at?->format('H:i · d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[38%_1fr]">
                    <dt class="font-semibold text-zinc-400">Ghi chú quản trị</dt>
                    <dd class="leading-relaxed text-zinc-300">{{ $staffUser->notes ?: 'Không có ghi chú riêng.' }}</dd>
                </div>
            </dl>

            @if($staffUser->isTopSuperAdmin() && ! Auth::user()?->isTopSuperAdmin())
                <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-xs text-amber-200">
                    Đây là tài khoản <strong>Top Super Admin</strong> cấp cao nhất. Tài khoản Quản Lý thường chỉ được xem thông tin, không có quyền chỉnh sửa hoặc khóa tài khoản này.
                </div>
            @endif
        </x-admin.card>

        {{-- Right Column: Recent Assigned Records & Activity Audit Log --}}
        <div class="space-y-6">
            <x-admin.card class="!p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-white/[0.08] pb-3.5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#38BDF8]">CRM Workload</p>
                        <h2 class="mt-1 text-base font-bold text-white">Hồ sơ đang &amp; đã phụ trách gần nhất</h2>
                    </div>
                    <span class="rounded-full border border-white/10 bg-[#070A10] px-3 py-1 text-[10px] font-semibold text-zinc-400">
                        {{ $recentItems->count() }} hồ sơ gần nhất
                    </span>
                </div>

                @forelse($recentItems as $item)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/[0.07] bg-[#070A10]/80 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-[#70A7FF]">
                                {{ $item['customer_order_code'] }}
                                <span class="text-zinc-500">·</span>
                                <span class="text-zinc-300">{{ $item['kind_label'] }}</span>
                            </p>
                            <p class="mt-0.5 truncate text-xs font-semibold text-white">
                                {{ $item['customer_name'] }} — {{ $item['subject'] }}
                            </p>
                            <p class="mt-0.5 text-[11px] tabular-nums text-zinc-500">
                                {{ $item['created_at']?->format('H:i · d/m/Y') }}
                            </p>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[10px] font-bold text-zinc-200">
                            {{ $item['status_label'] }}
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-zinc-500">Nhân sự này hiện chưa được gán hồ sơ lịch hẹn hoặc đơn phụ kiện nào.</p>
                @endforelse
            </x-admin.card>

            <x-admin.card class="!p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-white/[0.08] pb-3.5">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#70A7FF]">Audit Trail</p>
                        <h2 class="mt-1 text-base font-bold text-white">Nhật ký hoạt động gần đây</h2>
                    </div>
                    <span class="rounded-full border border-white/10 bg-[#070A10] px-3 py-1 text-[10px] font-semibold text-zinc-400">
                        {{ $activityLogs->count() }} bản ghi
                    </span>
                </div>

                @forelse($activityLogs as $log)
                    <div class="rounded-xl border border-white/[0.07] bg-[#070A10]/80 px-4 py-3 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[10px] font-bold text-[#70A7FF]">
                                {{ $log->actionLabel() }}
                            </span>
                            <span class="tabular-nums text-[11px] text-zinc-500">
                                {{ $log->created_at?->format('H:i:s · d/m/Y') }}
                            </span>
                        </div>
                        <p class="mt-1.5 leading-relaxed text-zinc-300">{{ $log->description }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-zinc-500">Chưa có nhật ký hoạt động nào được ghi nhận cho nhân sự này.</p>
                @endforelse
            </x-admin.card>
        </div>
    </div>
</x-admin-layout>
