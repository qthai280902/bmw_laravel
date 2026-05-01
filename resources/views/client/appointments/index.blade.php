<x-app-layout>
    <x-slot name="header">
        <h2 class="text-5xl font-black uppercase tracking-tighter text-white">
            Lịch hẹn <span class="text-[#1C69D4]">Của bạn</span>
        </h2>
        <p class="text-zinc-500 mt-2 font-medium uppercase text-xs tracking-widest ">Theo dõi lịch lái thử và bảo dưỡng xe của bạn.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-zinc-900 border border-zinc-800 shadow-2xl overflow-hidden">
                <div class="px-8 py-6 border-b border-zinc-800 bg-zinc-900/50 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-white">Lịch sử đặt hẹn</h3>
                    <a href="{{ route('products.index') }}" class="text-[10px] font-black uppercase tracking-widest text-[#1C69D4] hover:text-white transition-colors underline underline-offset-8 decoration-2">Đặt lịch mới</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-800 bg-zinc-950/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Thời gian</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Dòng xe</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Loại dịch vụ</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest  text-center">Trạng thái</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase text-zinc-500 tracking-widest ">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-900">
                            @forelse($appointments as $appointment)
                                <tr class="group hover:bg-zinc-900/30 transition-all duration-300">
                                    <td class="px-8 py-8">
                                        <div class="text-xs font-black text-white tabular-nums tracking-widest leading-none">
                                            {{ $appointment->appointment_date->format('d/M/Y') }}
                                        </div>
                                        <div class="text-[10px] font-bold text-zinc-600 uppercase mt-2  leading-none">
                                            Lúc: {{ $appointment->appointment_date->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 uppercase text-xs font-black tracking-tighter text-zinc-200 group-hover:text-[#1C69D4] transition-colors">
                                        {{ $appointment->product?->name ?? 'Dòng xe không xác định' }}
                                    </td>
                                    <td class="px-8 py-8">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-zinc-500 px-3 py-1 bg-zinc-950 border border-zinc-800 ">
                                            {{ $appointment->type->label() }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <span class="px-4 py-1.5 border {{ $appointment->status->color() }} text-[9px] font-black uppercase tracking-[0.2em] shadow-inner">
                                            {{ $appointment->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-xs text-zinc-500  font-medium max-w-xs truncate">
                                        {{ $appointment->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-32 text-center">
                                        <div class="text-zinc-600 font-black uppercase text-sm tracking-[0.2em]  mb-4">Bạn chưa có lịch hẹn nào.</div>
                                        <a href="{{ route('products.index') }}" class="text-[#1C69D4] font-black uppercase text-xs tracking-widest hover:underline decoration-2 underline-offset-8">Khám phá Showroom ngay &rarr;</a>
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
            
            <div class="mt-12 p-8 border border-zinc-900 bg-zinc-950/50 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-600 max-w-xl  leading-relaxed">
                    * Lưu ý: Lịch hẹn của bạn cần được chuyên viên BMW xác nhận qua điện thoại hoặc email. 
                    Vui lòng có mặt trước 15 phút tại Showroom đã chọn để chúng tôi chuẩn bị xe tốt nhất.
                </div>
                <div class="flex gap-8">
                    <div class="text-center">
                        <div class="text-xs font-black text-white">1800-BMW-CARE</div>
                        <div class="text-[8px] font-bold text-zinc-700 uppercase tracking-widest mt-1 ">Hotline Chăm sóc khách hàng</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
