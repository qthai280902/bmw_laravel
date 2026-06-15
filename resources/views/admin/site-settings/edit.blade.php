<x-admin-layout>
    <x-admin.page-header
        eyebrow="Visual settings"
        title="Thiết lập"
        accent="Giao diện"
        description="Quản lý hình nền chung cho các form public như tư vấn, báo giá, đặt lịch và đặt hàng phụ kiện."
    >
        <x-slot name="actions">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center border border-zinc-800 px-6 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500 transition-colors hover:border-white hover:text-white">
                Xem website
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[0.95fr_1.05fr]">
        <x-admin.card class="!p-0 overflow-hidden">
            <div class="relative min-h-[460px] bg-black">
                <img src="{{ $backgroundUrl }}" alt="Hình nền form public hiện tại" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/45 to-black/10"></div>
                <div class="absolute inset-x-0 bottom-0 p-8">
                    <p class="text-[10px] font-black uppercase tracking-[0.32em] text-[#70A7FF]">Public form background</p>
                    <h2 class="mt-4 text-4xl font-black uppercase leading-none tracking-normal text-white">
                        Nền form tư vấn
                    </h2>
                    <p class="mt-4 max-w-xl text-sm font-medium leading-6 text-zinc-300">
                        Ảnh này được dùng cho form đặt lịch, báo giá, tư vấn và đặt hàng phụ kiện. Nếu chưa upload, hệ thống dùng ảnh showroom mặc định.
                    </p>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card class="!p-8 lg:!p-10">
            <form method="POST" action="{{ route('admin.site-settings.update') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')

                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-500">Cấu hình hiện tại</p>
                    <div class="mt-5 border border-zinc-800 bg-black p-5">
                        <p class="text-xs font-bold text-zinc-400">
                            {{ $backgroundPath ? 'Đang dùng ảnh upload: '.$backgroundPath : 'Đang dùng ảnh fallback mặc định.' }}
                        </p>
                        <p class="mt-3 break-all text-[11px] font-medium leading-5 text-zinc-600">
                            Fallback: {{ $fallbackUrl }}
                        </p>
                    </div>
                </div>

                <x-admin.form-field name="public_form_background_image" label="Upload hình nền mới" hint="Định dạng ảnh hợp lệ, tối đa 5MB. Nên dùng ảnh ngang, đủ tối hoặc có vùng trống để form dễ đọc.">
                    <input
                        id="public_form_background_image"
                        type="file"
                        name="public_form_background_image"
                        accept="image/*"
                        class="w-full border border-zinc-800 bg-black px-4 py-4 text-xs text-zinc-400 file:mr-4 file:border-0 file:bg-white file:px-4 file:py-2 file:text-[10px] file:font-black file:uppercase file:tracking-[0.18em] file:text-black hover:file:bg-[#1C69D4] hover:file:text-white"
                    >
                </x-admin.form-field>

                <label class="flex items-start gap-4 border border-zinc-800 bg-black p-5">
                    <input
                        type="checkbox"
                        name="remove_public_form_background_image"
                        value="1"
                        class="mt-1 border-zinc-700 bg-zinc-950 text-[#1C69D4] focus:ring-[#1C69D4]"
                    >
                    <span>
                        <span class="block text-[10px] font-black uppercase tracking-[0.24em] text-white">Reset về fallback</span>
                        <span class="mt-2 block text-xs font-medium leading-5 text-zinc-500">
                            Xóa cấu hình ảnh upload hiện tại và dùng lại ảnh showroom mặc định trong public assets.
                        </span>
                    </span>
                </label>

                <div class="grid grid-cols-1 gap-4 border-t border-zinc-800 pt-8 sm:grid-cols-[1fr_auto]">
                    <button type="submit" class="bg-[#1C69D4] px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-white transition-all hover:bg-white hover:text-black">
                        Lưu thiết lập
                    </button>
                    <a href="{{ route('appointments.create', ['type' => 'consult']) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center border border-zinc-700 px-8 py-5 text-[10px] font-black uppercase tracking-[0.24em] text-zinc-300 transition-all hover:border-white hover:text-white">
                        Xem form public
                    </a>
                </div>
            </form>
        </x-admin.card>
    </div>
</x-admin-layout>
