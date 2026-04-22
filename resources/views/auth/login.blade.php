<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center group cursor-pointer">
                <input id="remember_me" type="checkbox" class="w-4 h-4 bg-zinc-950 border-zinc-800 text-[#1C69D4] focus:ring-0 rounded-none transition-all" name="remember">
                <span class="ms-3 text-[10px] font-black uppercase tracking-widest text-zinc-500 group-hover:text-zinc-300 transition-colors">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-[10px] font-black uppercase tracking-widest text-[#1C69D4] hover:text-white transition-colors" href="{{ route('password.request') }}">
                    {{ __('Forgot Password?') }}
                </a>
            @endif
        </div>

        <div class="pt-4 space-y-4">
            <x-primary-button class="w-full justify-center">
                {{ __('Initiate Login') }}
            </x-primary-button>
            
            <a href="{{ route('register') }}" class="block w-full text-center px-12 py-4 border border-zinc-800 font-black uppercase text-[10px] text-zinc-400 tracking-[0.2em] hover:text-white hover:border-zinc-600 transition-all duration-300">
                {{ __('Create New Account') }}
            </a>
        </div>

        <div class="text-center mt-6">
            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600">
                New to the showroom? 
                <a href="{{ route('register') }}" class="text-[#1C69D4] hover:text-white transition-colors ml-2">Request Access</a>
            </p>
        </div>
    </form>
</x-guest-layout>
