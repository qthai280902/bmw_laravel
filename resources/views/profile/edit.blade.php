<x-app-layout>
    <x-slot name="header">
        <h2 class="text-5xl font-black uppercase tracking-tighter text-white">
            Access <span class="text-[#1C69D4]">Settings</span>
        </h2>
        <p class="text-zinc-500 mt-2 font-medium uppercase text-xs tracking-widest ">Quản lý định danh và bảo mật tài khoản cá nhân.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="md:col-span-1">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-[#1C69D4] mb-2  underline decoration-2 underline-offset-8 decoration-[#1C69D4]/30">Profile Information</h3>
                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest leading-relaxed">Cập nhật thông tin định danh hiển thị trên hệ thống showroom.</p>
                </div>
                <div class="md:col-span-2 bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="border-t border-zinc-900 pt-12 grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="md:col-span-1">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-[#1C69D4] mb-2  underline decoration-2 underline-offset-8 decoration-[#1C69D4]/30">Security Credentials</h3>
                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest leading-relaxed">Đảm bảo tài khoản của bạn luôn được bảo vệ bởi các tiêu chuẩn mã hóa mới nhất.</p>
                </div>
                <div class="md:col-span-2 bg-zinc-900/30 border border-zinc-800 p-8 shadow-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="border-t border-zinc-900 pt-12 grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="md:col-span-1">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-rose-600 mb-2  underline decoration-2 underline-offset-8 decoration-rose-600/30">Termination Zone</h3>
                    <p class="text-[10px] text-zinc-600 font-bold uppercase tracking-widest leading-relaxed">Xóa vĩnh viễn mọi dữ liệu định danh và cấu hình xe khỏi Garage.</p>
                </div>
                <div class="md:col-span-2 bg-zinc-900/10 border border-rose-900/20 p-8">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
