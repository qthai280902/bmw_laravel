<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-zinc-900 border border-zinc-800 shadow-2xl overflow-hidden">
                <div class="px-8 py-6 border-b border-zinc-800 bg-zinc-900/50 flex justify-between items-center">
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-white">Quản lý Lịch hẹn Showroom</h2>
                    
                    <form action="{{ route('admin.appointments.index') }}" method="GET" class="flex gap-4">
                        <select name="status" onchange="this.form.submit()" class="bg-zinc-950 border-zinc-800 text-[10px] font-black uppercase tracking-widest text-zinc-400 focus:border-[#1C69D4] focus:ring-0 px-4 py-2">
                            <option value="">Tất cả trạng thái</option>
                            @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-800 bg-zinc-950/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Lịch hẹn</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Khách hàng</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic">Xe / Loại dịch vụ</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic text-center">Trạng thái</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest italic text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            @forelse($appointments as $appointment)
                                <tr class="group hover:bg-zinc-800/30 transition-all duration-300">
                                    <td class="px-8 py-8">
                                        <div class="text-xs font-black text-white tabular-nums tracking-widest">
                                            {{ $appointment->appointment_date->format('d/M/Y') }}
                                        </div>
                                        <div class="text-[10px] font-bold text-zinc-600 uppercase mt-1 italic">
                                            Lúc: {{ $appointment->appointment_date->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8">
                                        <div class="text-sm font-black uppercase tracking-wider text-white italic">{{ $appointment->user->name }}</div>
                                        <div class="text-[10px] text-zinc-500 font-medium">{{ $appointment->user->email }}</div>
                                    </td>
                                    <td class="px-8 py-8">
                                        <div class="text-xs font-black uppercase text-[#1C69D4] tracking-tighter mb-1">
                                            {{ $appointment->type->label() }}
                                        </div>
                                        <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">
                                            {{ $appointment->product->name }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <span class="px-4 py-1.5 border {{ $appointment->status->color() }} text-[9px] font-black uppercase tracking-[0.2em]">
                                            {{ $appointment->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-right">
                                        <form action="{{ route('admin.appointments.update', $appointment) }}" method="POST" class="inline-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" onchange="this.form.submit()" class="bg-zinc-800 border-zinc-700 text-[9px] font-black uppercase tracking-widest text-zinc-300 focus:border-[#1C69D4] focus:ring-0 px-3 py-1 cursor-pointer">
                                                @foreach(\App\Enums\AppointmentStatus::cases() as $status)
                                                    <option value="{{ $status->value }}" {{ $appointment->status === $status ? 'selected' : '' }}>
                                                        Update to: {{ $status->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-32 text-center text-zinc-600 font-black uppercase text-xs tracking-widest italic">
                                        Không tìm thấy lịch hẹn nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($appointments->hasPages())
                    <div class="px-8 py-6 border-t border-zinc-800 bg-zinc-950/50">
                        {{ $appointments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
