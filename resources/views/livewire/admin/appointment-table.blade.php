<div>
    <x-admin.page-header
        eyebrow="CRM pipeline"
        title="Lịch hẹn"
        accent="Showroom"
        description="Theo dõi đăng ký lái thử, báo giá, tư vấn, trade-in và dịch vụ sau bán hàng."
    >
        <x-slot name="metric">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">Tổng lead</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $appointments->total() }}</p>
        </x-slot>
    </x-admin.page-header>

    @if ($flashMessage)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-none border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-xs font-bold text-emerald-300">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400 hover:text-white">
                Đóng
            </button>
        </div>
    @endif

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.appointments.index') }}" method="GET" wire:submit.prevent="$refresh" class="grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr_1fr_1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="search" label="Tìm kiếm khách hàng / xe">
                <input
                    id="search"
                    type="text"
                    name="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Tên, SĐT, email hoặc mẫu xe..."
                    class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs text-white placeholder:text-zinc-600 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                >
            </x-admin.form-field>

            <x-admin.form-field name="status" label="Trạng thái">
                <select id="status" name="status" wire:model.live="status" class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">
                            {{ $statusOption->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="type" label="Loại yêu cầu">
                <select id="type" name="type" wire:model.live="type" class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả loại</option>
                    @foreach($types as $typeOption)
                        <option value="{{ $typeOption->value }}">
                            {{ $typeOption->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="leadPriority" label="Phân loại Lead AI">
                <select id="leadPriority" name="priority" wire:model.live="leadPriority" class="w-full rounded-none border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả mức độ</option>
                    <option value="hot">HOT LEAD</option>
                    <option value="warm">TIỀM NĂNG</option>
                    <option value="normal">THAM KHẢO</option>
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="rounded-none border border-zinc-700 bg-zinc-900 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]">
                    Lọc
                </button>
                <button type="button" wire:click="resetFilters" class="rounded-none border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
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
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Thời điểm</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Khách hàng</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Nhu cầu</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Trạng thái</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.22em] text-zinc-600">Điều phối</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($appointments as $appointment)
                        <tr wire:key="admin-appointment-{{ $appointment->id }}" class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-widest text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600">{{ $appointment->appointment_date->format('H:i') }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-500">{{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chưa có liên hệ' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">{{ $appointment->type->label() }}</p>
                                    @if($appointment->lead_priority)
                                        <span class="rounded-none border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em] {{ $appointment->leadPriorityColorClass() }}">
                                            {{ $appointment->leadPriorityLabel() }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-2 max-w-xs truncate text-xs font-black uppercase tracking-wider text-zinc-300">{{ $appointment->product?->name ?? 'Chưa gắn sản phẩm' }}</p>
                                @if($appointment->ai_lead_note)
                                    <p class="mt-1.5 max-w-sm truncate text-[11px] font-medium text-zinc-400">
                                        AI: {{ $appointment->ai_lead_note }}
                                    </p>
                                @elseif($appointment->meta_data)
                                    <p class="mt-2 max-w-sm truncate text-[10px] font-medium text-zinc-600">
                                        {{ collect($appointment->meta_data)->filter()->take(2)->map(fn ($value, $key) => Str::headline($key).': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' | ') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="rounded-none border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <select
                                    aria-label="Cập nhật trạng thái lịch hẹn #{{ $appointment->id }}"
                                    wire:change="updateStatus({{ $appointment->id }}, $event.target.value)"
                                    class="rounded-none border-zinc-800 bg-black px-3 py-2 text-[9px] font-black uppercase tracking-[0.18em] text-zinc-400 transition-colors hover:text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]"
                                >
                                    @foreach($statuses as $statusOption)
                                        <option value="{{ $statusOption->value }}" @selected($appointment->status === $statusOption)>
                                            {{ $statusOption->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10">
                                <x-admin.empty-state
                                    title="Chưa có lịch hẹn"
                                    description="Các đăng ký lái thử, tư vấn và dịch vụ từ website sẽ xuất hiện tại đây."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($appointments->hasPages())
        <div class="mt-8">
            {{ $appointments->links() }}
        </div>
    @endif
</div>
