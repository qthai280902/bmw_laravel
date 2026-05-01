<x-admin-layout>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12">
        <div>
            <h1 class="text-6xl font-light uppercase tracking-tighter mb-2 text-white">Yêu cầu <span class="font-black text-[#1C69D4]">Dịch vụ</span></h1>
            <p class="text-zinc-500 font-medium font-outfit">Quản lý các lượt đăng ký lái thử và lịch hẹn dịch vụ BMW.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="px-6 py-3 bg-zinc-900 border border-zinc-800 flex items-center gap-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500">Tổng Lead:</p>
                <p class="text-lg font-black text-white leading-none">{{ $appointments->total() }}</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <x-admin.card class="mb-8 !p-6">
        <form action="{{ route('admin.appointments.index') }}" method="GET" class="flex flex-wrap gap-6 items-end">
            <div class="w-64">
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest">Trạng thái xử lý</label>
                <select name="status" onchange="this.form.submit()" class="w-full bg-zinc-950 border-zinc-800 text-xs font-black uppercase tracking-widest text-zinc-400 focus:border-zinc-500 focus:ring-0 px-4 py-2.5 transition-all">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.appointments.index') }}" class="px-6 py-2.5 border border-zinc-800 text-zinc-500 font-black uppercase text-[10px] tracking-widest hover:text-white hover:bg-zinc-900 transition-all text-center">
                Xóa lọc
            </a>
        </form>
    </x-admin.card>

    <!-- Table -->
    <x-admin.card class="!p-0 border-zinc-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-800 bg-zinc-900/50">
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Thời điểm hẹn</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Khách hàng Lead</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em]">Cấu hình Xe / Dịch vụ</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-center">Trạng thái</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase text-zinc-500 tracking-[0.2em] text-right">Điều phối</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($appointments as $appointment)
                        <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                            <td class="px-8 py-8 border-l-2 border-transparent hover:border-[#1C69D4] transition-all">
                                <div class="text-sm font-black text-white tabular-nums tracking-widest leading-none mb-1">
                                    {{ $appointment->appointment_date->format('d/M/Y') }}
                                </div>
                                <div class="text-[10px] font-bold text-zinc-600 uppercase tracking-tight ">
                                    Slot: {{ $appointment->appointment_date->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-8 py-8">
                                <div class="text-sm font-black uppercase tracking-wider text-white leading-none mb-1">
                                    {{ $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai' }}
                                </div>
                                <div class="text-[10px] text-zinc-500 font-black tracking-widest">
                                    {{ $appointment->user?->email ?? $appointment->guest_email ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-8 py-8">
                                <div class="text-[10px] font-black uppercase text-[#1C69D4] tracking-[0.1em] mb-1">
                                    {{ $appointment->type->label() }}
                                </div>
                                <div class="text-xs font-black text-zinc-400 uppercase tracking-tighter">
                                    {{ $appointment->product?->name ?? 'Dòng xe không xác định' }}
                                </div>
                                
                                @if($appointment->meta_data)
                                    <div class="mt-2 p-2 bg-zinc-900 border border-zinc-800 rounded">
                                        <div class="text-[9px] font-bold text-accent uppercase tracking-widest mb-1">Dữ liệu CRM:</div>
                                        <div class="space-y-1">
                                            @if($appointment->type->value === 'trade_in' && isset($appointment->meta_data['desired_bmw']))
                                                <div class="text-[10px] text-zinc-400 font-medium">Sản phẩm muốn đổi: <span class="text-white">{{ $appointment->meta_data['desired_bmw'] }}</span></div>
                                            @endif
                                            
                                            @if(isset($appointment->meta_data['customer_car_model']))
                                                <div class="text-[10px] text-zinc-400 font-medium">Xe khách đang đi: <span class="text-white">{{ $appointment->meta_data['customer_car_model'] }}</span></div>
                                            @endif
                                            
                                            @if(isset($appointment->meta_data['customer_car_condition']))
                                                <div class="text-[10px] text-zinc-500 italic">{{ $appointment->meta_data['customer_car_condition'] }}</div>
                                            @endif

                                            @if(isset($appointment->meta_data['showroom']))
                                                <div class="text-[10px] text-zinc-400 font-medium">Showroom: <span class="text-white">{{ $appointment->meta_data['showroom'] }}</span></div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-8 text-center">
                                <span class="px-4 py-1.5 border {{ $appointment->status->color() }} text-[9px] font-black uppercase tracking-[0.2em]">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="px-8 py-8 text-right">
                                <form action="{{ route('admin.appointments.update', $appointment) }}" method="POST" class="inline-flex">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" onchange="this.form.submit()" class="bg-zinc-950 border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-500 hover:text-white focus:border-zinc-500 focus:ring-0 px-3 py-1.5 cursor-pointer transition-all">
                                        @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                                            <option value="{{ $status->value }}" {{ $appointment->status === $status ? 'selected' : '' }}>
                                                Move to: {{ $status->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-32 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-zinc-900/50 rounded-full flex items-center justify-center border border-zinc-800 mb-6">
                                        <svg class="w-6 h-6 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500">Không có yêu cầu</h3>
                                    <p class="text-[10px] text-zinc-700 font-bold mt-2 uppercase tracking-tight">Cơ sở dữ liệu lịch hẹn hiện đang trống.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if($appointments->hasPages())
        <div class="mt-12">
            {{ $appointments->links() }}
        </div>
    @endif
</x-admin-layout>
