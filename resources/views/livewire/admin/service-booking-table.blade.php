@php
    $statusFilterOptions = ['' => 'Tất cả trạng thái'];
    $statusActionOptions = [];
    foreach ($statuses as $statusOption) {
        $statusFilterOptions[$statusOption->value] = $statusOption->label();
        $statusActionOptions[$statusOption->value] = $statusOption->label();
    }

    $typeFilterOptions = ['' => 'Tất cả dịch vụ'];
    foreach ($serviceTypes as $typeOption) {
        $typeFilterOptions[$typeOption->value] = $typeOption->label();
    }
@endphp

<div>
    <x-admin.page-header
        eyebrow="Aftersales operations"
        title="Dịch vụ"
        accent="Chăm sóc xe"
        description="Quản lý chuyên biệt lịch hẹn bảo dưỡng định kỳ, phủ Ceramic, chăm sóc nội thất da Merino và chẩn đoán ISTA."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng lịch dịch vụ</p>
            <p class="mt-1 text-2xl font-black tabular-nums text-white">{{ $bookings->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-xs font-bold text-emerald-300">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 hover:text-white cursor-pointer">
                Đóng
            </button>
        </div>
    @endif

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.service-bookings.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-3 lg:grid-cols-[1.5fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm khách hàng / xe / gói dịch vụ">
                <input
                    id="service-search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Tên khách, SĐT, email, biển số hoặc gói chăm sóc..."
                    class="h-10 w-full rounded-none border-zinc-800 bg-black px-3.5 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="service_type" label="Nhóm dịch vụ">
                <x-ui.custom-select
                    id="service-type-filter"
                    name="service_type"
                    :value="$serviceType"
                    :options="$typeFilterOptions"
                    wire-model="serviceType"
                />
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <x-ui.custom-select
                    id="service-status-filter"
                    name="status"
                    :value="$status"
                    :options="$statusFilterOptions"
                    wire-model="status"
                />
            </x-admin.form-field>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-10 whitespace-nowrap rounded-none border border-zinc-700 bg-zinc-900 px-4 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF] cursor-pointer">
                    Lọc
                </button>
                <button type="button" wire:click="resetFilters" class="h-10 whitespace-nowrap rounded-none border border-zinc-800 px-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400 transition-colors hover:border-zinc-600 hover:text-white cursor-pointer">
                    Xóa
                </button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full min-w-[1120px] text-left">
                <thead>
                    <tr class="border-b border-zinc-800 bg-black/60">
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Thời điểm hẹn</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Khách hàng</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Xe & Gói dịch vụ chăm sóc</th>
                        <th class="min-w-[140px] px-5 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Trạng thái</th>
                        <th class="min-w-[170px] px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Điều phối</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($bookings as $booking)
                        @php
                            $carModel = $booking->product?->name
                                ?? ($booking->meta_data['customer_car_model'] ?? 'Xe BMW của khách');
                            $carCondition = is_array($booking->meta_data)
                                ? ($booking->meta_data['customer_car_condition'] ?? null)
                                : null;
                            $servicePackage = $booking->servicePackageLabel();
                        @endphp
                        <tr wire:key="admin-service-booking-{{ $booking->id }}" class="group align-top transition-colors hover:bg-zinc-900/60">
                            <td class="whitespace-nowrap px-5 py-5">
                                <p class="text-sm font-black tabular-nums tracking-wider text-white">{{ $booking->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-1 text-[11px] font-bold tabular-nums tracking-wider text-zinc-500">{{ $booking->appointment_date->format('H:i') }} · #{{ $booking->id }}</p>
                            </td>
                            <td class="px-5 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $booking->user?->name ?? $booking->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-400">{{ $booking->user?->email ?? $booking->guest_email ?? $booking->guest_phone ?? 'Chưa có liên hệ' }}</p>
                                @if(filled($booking->guest_phone) && filled($booking->user?->email ?? $booking->guest_email))
                                    <p class="mt-0.5 text-[11px] tabular-nums text-zinc-500">{{ $booking->guest_phone }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-5">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-white">
                                            {{ $carModel }}
                                        </span>
                                        <span class="whitespace-nowrap rounded-none border border-[#1C69D4]/30 bg-[#1C69D4]/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.16em] text-[#70A7FF]">
                                            {{ $booking->type->label() }}
                                        </span>
                                        @if($servicePackage)
                                            <span class="rounded-none border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-300">
                                                {{ $servicePackage }}
                                            </span>
                                        @endif
                                    </div>

                                    @if(filled($carCondition) || filled($booking->notes))
                                        <p class="line-clamp-2 max-w-lg text-xs leading-relaxed text-zinc-400">
                                            {{ collect([$carCondition ? 'Tình trạng: '.$carCondition : null, $booking->notes])->filter()->implode(' · ') }}
                                        </p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                        @if($booking->confirmation_email_sent_at)
                                            <span class="whitespace-nowrap rounded-none border border-emerald-500/35 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-emerald-300">
                                                ĐÃ GỬI MAIL XÁC NHẬN · {{ $booking->confirmation_email_sent_at->format('H:i d/m') }}
                                            </span>
                                        @else
                                            <span class="whitespace-nowrap rounded-none border border-zinc-800 bg-zinc-950 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-500">
                                                CHƯA GỬI MAIL
                                            </span>
                                        @endif

                                        @if(filled($booking->user?->email ?? $booking->guest_email))
                                            <a
                                                href="{{ route('admin.mail-preview.appointment', $booking) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="whitespace-nowrap rounded-none border border-zinc-800 bg-black px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-zinc-400 transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]"
                                            >
                                                Xem mẫu Email xác nhận
                                            </a>
                                            <button
                                                type="button"
                                                wire:click="resendConfirmationEmail({{ $booking->id }})"
                                                class="whitespace-nowrap rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-[#70A7FF] transition-colors hover:bg-[#1C69D4] hover:text-white cursor-pointer"
                                            >
                                                Gửi lại Email xác nhận
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="min-w-[140px] whitespace-nowrap px-5 py-5 text-center">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-none border px-3 py-1 text-[9px] font-black uppercase tracking-[0.16em] {{ $booking->status->color() }}">
                                    {{ $booking->status->label() }}
                                </span>
                            </td>
                            <td class="min-w-[170px] whitespace-nowrap px-5 py-5 text-right">
                                <div class="ml-auto w-40">
                                    <x-ui.custom-select
                                        compact
                                        aria-label="Cập nhật trạng thái lịch chăm sóc xe #{{ $booking->id }}"
                                        :value="$booking->status->value"
                                        :options="$statusActionOptions"
                                        wire-action="updateStatus"
                                        :wire-record-id="$booking->id"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Chưa có lịch hẹn dịch vụ chăm sóc xe"
                                    description="Các yêu cầu bảo dưỡng định kỳ, phủ Ceramic, chăm sóc da Merino và chẩn đoán ISTA từ trang Dịch vụ sẽ xuất hiện tại đây."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($bookings->hasPages())
        <div class="mt-8">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
