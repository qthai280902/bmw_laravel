<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <div class="mb-6">
        <h2 class="text-lg font-bold text-white tracking-tight">Đăng Nhập Quản Trị</h2>
        <p class="text-xs text-zinc-400 mt-1">Vui lòng nhập thông tin xác thực để truy cập hệ thống quản trị.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-xs text-zinc-300 mb-1.5">
                Tài khoản Email
            </label>
            <div class="relative">
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       autocomplete="username" 
                       placeholder="admin@bmw.com"
                       class="block w-full bg-zinc-900/90 border border-zinc-800 text-sm text-white px-3.5 py-2.5 placeholder-zinc-500 focus:outline-none focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]/50 transition-colors" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block font-medium text-xs text-zinc-300">
                    Mật khẩu bảo mật
                </label>
                @if (Route::has('password.request'))
                    <a class="text-xs text-[#70A7FF] hover:text-white transition-colors" href="{{ route('password.request') }}">
                        Quên mật khẩu?
                    </a>
                @endif
            </div>
            <div class="relative">
                <input id="password" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="current-password" 
                       placeholder="••••••••"
                       class="block w-full bg-zinc-900/90 border border-zinc-800 text-sm text-white px-3.5 py-2.5 placeholder-zinc-500 focus:outline-none focus:border-[#1C69D4] focus:ring-1 focus:ring-[#1C69D4]/50 transition-colors" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label for="remember_me" class="inline-flex items-center group cursor-pointer select-none">
                <input id="remember_me" type="checkbox" class="w-4 h-4 bg-zinc-900 border-zinc-700 text-[#1C69D4] focus:ring-0 focus:ring-offset-0 transition-all cursor-pointer" name="remember">
                <span class="ms-2.5 text-xs text-zinc-400 group-hover:text-zinc-200 transition-colors">Ghi nhớ phiên làm việc</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-[#1C69D4] to-[#154fa0] hover:from-[#2575e6] hover:to-[#1c69d4] text-white text-xs font-bold uppercase tracking-wider shadow-lg shadow-blue-900/30 hover:shadow-blue-700/40 focus:outline-none focus:ring-1 focus:ring-blue-400 transition-all duration-200 cursor-pointer">
                Đăng nhập hệ thống
            </button>
        </div>
    </form>
</x-guest-layout>
