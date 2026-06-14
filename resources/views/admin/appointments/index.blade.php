<x-admin-layout>
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

    <x-admin.card class="mb-6 !p-5">
        <form action="{{ route('admin.appointments.index') }}" method="GET" class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
            <x-admin.form-field name="status" label="Trạng thái">
                <select id="status" name="status" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <x-admin.form-field name="type" label="Loại yêu cầu">
                <select id="type" name="type" class="w-full border-zinc-800 bg-black px-4 py-3 text-xs font-bold uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                    <option value="">Tất cả loại</option>
                    @foreach(\App\Enums\AppointmentType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('type') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.form-field>

            <div class="grid grid-cols-2 gap-3">
                <button type="submit" class="border border-zinc-700 bg-zinc-900 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:border-[#1C69D4] hover:text-[#70A7FF]">
                    Lọc
                </button>
                <a href="{{ route('admin.appointments.index') }}" class="border border-zinc-800 px-5 py-3 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:text-white">
                    Xóa
                </a>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
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
                        <tr class="group transition-colors hover:bg-zinc-900/60">
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-widest text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600">{{ $appointment->appointment_date->format('H:i') }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-black uppercase tracking-wider text-white">{{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}</p>
                                <p class="mt-1 text-xs font-medium text-zinc-500">{{ $appointment->user?->email ?? $appointment->guest_email ?? $appointment->guest_phone ?? 'Chưa có liên hệ' }}</p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#70A7FF]">{{ $appointment->type->label() }}</p>
                                <p class="mt-2 max-w-xs truncate text-xs font-black uppercase tracking-wider text-zinc-300">{{ $appointment->product?->name ?? 'Chưa gắn sản phẩm' }}</p>
                                @if($appointment->meta_data)
                                    <p class="mt-2 max-w-sm truncate text-[10px] font-medium text-zinc-600">
                                        {{ collect($appointment->meta_data)->filter()->take(2)->map(fn ($value, $key) => Str::headline($key).': '.(is_scalar($value) ? $value : json_encode($value)))->implode(' | ') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="border px-3 py-1 text-[9px] font-black uppercase tracking-[0.2em] {{ $appointment->status->color() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <form action="{{ route('admin.appointments.update', $appointment) }}" method="POST" class="inline-flex">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" onchange="this.form.submit()" class="border-zinc-800 bg-black px-3 py-2 text-[9px] font-black uppercase tracking-[0.18em] text-zinc-500 transition-colors hover:text-white focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]">
                                        @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                                            <option value="{{ $status->value }}" {{ $appointment->status === $status ? 'selected' : '' }}>
                                                {{ $status->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
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
</x-admin-layout>
