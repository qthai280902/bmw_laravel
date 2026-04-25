<x-app-layout>
    <x-slot name="header">
        <h2 class="text-5xl font-black uppercase tracking-tighter text-white">
            My <span class="text-[#1C69D4]">Garage</span>
        </h2>
        <p class="text-zinc-500 mt-2 font-medium uppercase text-xs tracking-widest ">Quản lý lịch hẹn lái thử và yêu cầu báo giá.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl transition-all hover:border-[#1C69D4]/50 group">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest leading-none">Tổng yêu cầu</div>
                    <div class="text-4xl font-black text-white px-0">{{ Auth::user()->appointments()->count() }}</div>
                </div>
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl transition-all hover:border-emerald-500/50 group">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest leading-none">Đã xác nhận</div>
                    <div class="text-4xl font-black text-white px-0">{{ Auth::user()->appointments()->where('status', \App\Enums\AppointmentStatus::Confirmed)->count() }}</div>
                </div>
                <div class="bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl transition-all hover:border-yellow-500/50 group">
                    <div class="text-[10px] font-black uppercase text-zinc-500 mb-2 tracking-widest leading-none">Đang chờ</div>
                    <div class="text-4xl font-black text-white px-0">{{ Auth::user()->appointments()->where('status', \App\Enums\AppointmentStatus::Pending)->count() }}</div>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="bg-zinc-900/10 border border-zinc-800 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                <div class="px-8 py-6 border-b border-zinc-800 bg-zinc-900/50 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-white">Lịch sử yêu cầu</h3>
                    <a href="{{ route('appointments.create') }}" class="px-6 py-2 bg-accent text-white text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-black transition-all">+ Đặt lịch mới</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-800 bg-zinc-950/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Mã</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Dòng xe</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Loại</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest  text-center">Trạng thái</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest  text-right">Ngày hẹn</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-900">
                            @forelse(Auth::user()->appointments()->with('product')->latest()->get() as $appointment)
                                <tr class="group hover:bg-zinc-900/30 transition-all duration-300">
                                    <td class="px-8 py-8 font-black text-xs text-zinc-400">#{{ $appointment->id }}</td>
                                    <td class="px-8 py-8">
                                        <div class="text-sm font-black uppercase tracking-wider text-zinc-200 group-hover:text-[#1C69D4] transition-colors">
                                            {{ $appointment->product?->name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-xs font-bold uppercase tracking-widest text-zinc-400">
                                        {{ $appointment->type->label() }}
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        @php
                                            $color = match($appointment->status) {
                                                \App\Enums\AppointmentStatus::Pending => 'text-yellow-500 border-yellow-500/20 bg-yellow-500/5',
                                                \App\Enums\AppointmentStatus::Confirmed => 'text-emerald-500 border-emerald-500/20 bg-emerald-500/5',
                                                default => 'text-rose-500 border-rose-500/20 bg-rose-500/5',
                                            };
                                        @endphp
                                        <span class="px-4 py-1.5 border {{ $color }} text-[9px] font-black uppercase tracking-widest">
                                            {{ $appointment->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-right text-xs font-bold text-zinc-400">
                                        {{ $appointment->appointment_date->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-32 text-center">
                                        <div class="text-zinc-600 font-black uppercase text-sm tracking-[0.2em]  mb-4">Bạn chưa có yêu cầu nào.</div>
                                        <a href="{{ route('appointments.create') }}" class="text-[#1C69D4] font-black uppercase text-xs tracking-widest hover:underline decoration-2 underline-offset-8">Đăng ký lái thử ngay &rarr;</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
