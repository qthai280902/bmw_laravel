{{-- Quick Booking Form Component (Context-Aware with pre-filled Showroom) --}}
@props(['showroom' => ''])

<div x-data="{ showModal: false }" class="inline-block w-full">
    <button @click="showModal = true" type="button"
        class="w-full text-center px-8 py-4 bg-accent text-white text-[10px] font-black uppercase tracking-[0.3em] hover:bg-blue-700 transition-all duration-300 cursor-pointer">
        Đặt lịch tại {{ $showroom }}
    </button>

    {{-- Modal Overlay --}}
    <div x-show="showModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        @click.self="showModal = false" style="display: none;">

        <div x-show="showModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-xl max-h-[90vh] overflow-y-auto bg-zinc-950 border border-zinc-800 shadow-2xl">

            <div class="p-8 md:p-12">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <p class="text-[10px] font-black text-accent uppercase tracking-[0.4em] mb-2">Quick Booking</p>
                        <h2 class="text-xl font-black text-white uppercase tracking-widest">Đặt lịch hẹn nhanh</h2>
                    </div>
                    <button @click="showModal = false" class="text-zinc-500 hover:text-white transition-colors p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('appointments.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Chi nhánh (pre-filled, readonly) --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Chi nhánh</label>
                        <input type="text" name="showroom" value="{{ $showroom }}" readonly
                            class="w-full bg-zinc-800 border border-zinc-700 text-zinc-300 px-4 py-3 cursor-not-allowed font-bold uppercase tracking-widest text-xs">
                    </div>

                    {{-- Loại yêu cầu --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Loại yêu cầu *</label>
                        <select name="type" required class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors uppercase text-xs font-black tracking-widest">
                            @foreach([\App\Enums\AppointmentType::TestDrive, \App\Enums\AppointmentType::Viewing, \App\Enums\AppointmentType::Quote, \App\Enums\AppointmentType::Consult, \App\Enums\AppointmentType::AdvisorMeeting] as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Thông tin khách hàng --}}
                    @guest
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Họ và tên *</label>
                                <input type="text" name="guest_name" required
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Số điện thoại *</label>
                                <input type="text" name="guest_phone" required
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-zinc-900/50 border border-zinc-800 text-zinc-300 text-xs">
                            Xin chào <span class="font-bold text-white">{{ auth()->user()->name }}</span>
                        </div>
                    @endguest

                    {{-- Chọn xe --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Dòng xe quan tâm *</label>
                        <select name="product_id" required class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors">
                            <option value="">-- Chọn xe --</option>
                            @foreach(\App\Models\Product::where('is_active', true)->get() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ngày hẹn --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-zinc-400 mb-2">Ngày hẹn *</label>
                        <input type="datetime-local" name="appointment_date" required
                            class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 focus:border-accent focus:ring-1 focus:ring-accent transition-colors [color-scheme:dark]">
                    </div>

                    <button type="submit" class="w-full bg-accent text-white py-4 text-sm font-black uppercase tracking-[0.2em] hover:bg-white hover:text-black transition-all">
                        Xác nhận đặt lịch
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
