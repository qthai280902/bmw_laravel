<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.accounting.index')"
        back-label="Quay lại Sổ tổng hợp"
        eyebrow="Gói Sổ Kế Toán Ngày · Chỉ Super Admin"
        title="Sổ Chi Tiết Ngày"
        :accent="$dateLabel"
        :description="'Tổng hợp ' . $summary['total_vouchers'] . ' chứng từ (' . $summary['vehicles_count'] . ' lịch hẹn xe · ' . $summary['services_count'] . ' dịch vụ · ' . $summary['orders_count'] . ' đơn phụ kiện).'"
    >
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ route('admin.accounting.export', ['date' => $date]) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-5 text-xs font-semibold text-white transition-colors hover:bg-emerald-500"
                >
                    <span>Xuất sổ ngày (.CSV)</span>
                </a>
            </div>
        </x-slot>
    </x-admin.page-header>

    @if(session('status'))
        <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/15 px-4 py-3 text-xs font-semibold text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-white/[0.08] bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-zinc-400">Tổng số chứng từ trong ngày</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $summary['total_vouchers'] }}</p>
            <p class="mt-1 text-xs text-zinc-400">{{ $summary['vehicles_count'] }} xe · {{ $summary['services_count'] }} dịch vụ · {{ $summary['orders_count'] }} phụ kiện</p>
        </div>
        <div class="rounded-2xl border border-emerald-500/25 bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-emerald-400">Doanh thu hoàn tất trong ngày</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($summary['completed_revenue'], 0, ',', '.') }} ₫</p>
        </div>
        <div class="rounded-2xl border border-[#1C69D4]/30 bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-[#70A7FF]">Đơn / Đặt cọc cam kết</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($summary['committed_value'], 0, ',', '.') }} ₫</p>
        </div>
        <div class="rounded-2xl border border-rose-500/25 bg-[#0D121C] p-5">
            <p class="text-[11px] font-semibold text-rose-400">Giá trị hủy trong ngày</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ number_format($summary['cancelled_value'], 0, ',', '.') }} ₫</p>
        </div>
    </div>

    {{-- Khối Chốt sổ ngày --}}
    <x-admin.card class="mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-base font-bold text-white">Trạng thái chốt sổ ngày {{ $dateLabel }}</h2>
                    @if($closing)
                        <span class="rounded-full border border-emerald-500/30 bg-emerald-500/15 px-3 py-0.5 text-xs font-semibold text-emerald-300">
                            Đã chốt sổ ngày
                        </span>
                    @else
                        <span class="rounded-full border border-amber-500/30 bg-amber-500/15 px-3 py-0.5 text-xs font-semibold text-amber-300">
                            Đang mở
                        </span>
                    @endif
                </div>
                @if($closing)
                    <p class="mt-1.5 text-xs text-zinc-400">
                        Chốt lần cuối bởi <strong class="text-white">{{ $closing->closedBy?->name ?? 'Super Admin' }}</strong>
                        lúc {{ $closing->closed_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i — d/m/Y') }}.
                    </p>
                @else
                    <p class="mt-1.5 text-xs text-zinc-400">
                        Lưu bản snapshot doanh thu &amp; sản lượng cuối ngày vào sổ kế toán nội bộ.
                    </p>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.accounting.close-day', ['date' => $date]) }}" class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                @csrf
                <input
                    type="text"
                    name="notes"
                    value="{{ old('notes', $closing?->notes) }}"
                    placeholder="Ghi chú chốt sổ ngày (tùy chọn)..."
                    class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4] sm:w-72"
                >
                <button
                    type="submit"
                    class="h-10 whitespace-nowrap rounded-xl bg-[#1C69D4] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#1557B0] cursor-pointer"
                >
                    {{ $closing ? 'Cập nhật chốt sổ ngày' : 'Chốt sổ ngày ' . $dateLabel }}
                </button>
            </form>
        </div>
    </x-admin.card>

    {{-- Sổ Chi Tiết Chứng Từ --}}
    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-[#070A10]/60 text-zinc-400">
                        <th class="px-4 py-3.5 font-semibold">Mã chứng từ / Mã KH</th>
                        <th class="px-4 py-3.5 font-semibold">Giờ</th>
                        <th class="px-4 py-3.5 font-semibold">Phân hệ</th>
                        <th class="px-4 py-3.5 font-semibold">Khách hàng</th>
                        <th class="px-4 py-3.5 font-semibold">Sản phẩm / Hạng mục</th>
                        <th class="px-4 py-3.5 text-right font-semibold">Giá trị / Tiền cọc</th>
                        <th class="px-4 py-3.5 font-semibold">Sales</th>
                        <th class="px-4 py-3.5 text-center font-semibold">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.06]">
                    @forelse($vouchers as $v)
                        <tr class="hover:bg-white/[0.03]">
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <p class="font-bold tabular-nums text-white">{{ $v['voucher_code'] }}</p>
                                <p class="mt-0.5 font-semibold tabular-nums text-[#70A7FF]">{{ $v['reference_code'] }}</p>
                                <p class="mt-0.5 text-[11px] tabular-nums text-zinc-400">{{ $v['customer_internal_code'] }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 tabular-nums text-zinc-300">
                                {{ $v['time_label'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[11px] font-semibold text-zinc-200">
                                    {{ $v['module_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-white">{{ $v['customer_name'] }}</p>
                                <p class="mt-0.5 tabular-nums text-zinc-400">{{ $v['customer_phone'] }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-white">{{ $v['item_name'] }}</p>
                                <p class="mt-0.5 text-[11px] text-zinc-400">SL: {{ $v['quantity'] }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right tabular-nums">
                                <p class="font-bold text-white">{{ number_format($v['amount'], 0, ',', '.') }} ₫</p>
                                @if($v['reference_price'] > $v['amount'])
                                    <p class="mt-0.5 text-[11px] text-zinc-400">Niêm yết: {{ number_format($v['reference_price'], 0, ',', '.') }} ₫</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-zinc-300">
                                {{ $v['sales_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                <span class="inline-flex rounded-full border border-white/10 bg-white/[0.05] px-2.5 py-0.5 text-[11px] font-semibold text-zinc-200">
                                    {{ $v['status_label'] }}
                                </span>
                                @if(($v['completion_outcome'] ?? null) === \App\Models\Appointment::OUTCOME_PURCHASED)
                                    <p class="mt-1">
                                        <span class="inline-flex rounded-full border border-emerald-500/35 bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300">
                                            {{ $v['completion_outcome_label'] }}{{ ! empty($v['care_voucher_code']) ? ' · ' . $v['care_voucher_code'] : '' }}{{ ! empty($v['care_voucher_expires_label']) ? ' (hạn ' . $v['care_voucher_expires_label'] . ')' : '' }}
                                        </span>
                                    </p>
                                @elseif(($v['completion_outcome'] ?? null) === \App\Models\Appointment::OUTCOME_VISITED_NO_PURCHASE)
                                    <p class="mt-1">
                                        <span class="inline-flex rounded-full border border-white/15 bg-white/[0.05] px-2 py-0.5 text-[10px] font-semibold text-zinc-300">
                                            {{ $v['completion_outcome_label'] }}
                                        </span>
                                    </p>
                                @endif
                                @if(! empty($v['cancellation_label']))
                                    <p class="mt-1 text-[10px] font-medium text-rose-300">{{ $v['cancellation_label'] }}</p>
                                @endif
                                @if(! empty($v['is_archived']))
                                    <p class="mt-1">
                                        <span class="inline-flex rounded-full border border-amber-500/30 bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold text-amber-200">
                                            Đã lưu trữ
                                        </span>
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6">
                                <x-admin.empty-state
                                    title="Không có chứng từ trong ngày {{ $dateLabel }}"
                                    description="Chưa có giao dịch nào phát sinh vào ngày này."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
