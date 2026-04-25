<nav x-data="{ open: false }" class="bg-zinc-950 border-b border-zinc-900 sticky top-0 z-50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="group">
                        <div class="flex items-center gap-3">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" class="h-12 w-12" alt="BMW Logo">
                            <span class="text-lg font-black uppercase tracking-[0.2em] hidden sm:block">BMW <span class="text-zinc-500 ">Showroom</span></span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-10 sm:-my-px sm:ms-16 sm:flex items-center h-full">
                    <x-nav-link :href="route('products.index', ['type' => 'car'])" :active="request('type') == 'car'" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Ô tô
                    </x-nav-link>
                    <x-nav-link :href="route('products.index', ['type' => 'motorbike'])" :active="request('type') == 'motorbike'" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Xe máy
                    </x-nav-link>
                    <x-nav-link :href="route('services.index')" :active="request()->is('services*')" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Dịch vụ
                    </x-nav-link>
                    <x-nav-link :href="route('experiences.index')" :active="request()->is('experiences*')" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Trải nghiệm
                    </x-nav-link>
                </div>
            </div>

            <!-- Right Side Links -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-8">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-black uppercase tracking-widest text-zinc-400 bg-transparent hover:text-white focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if(auth()->user()->email === 'admin@bmw.com' || auth()->user()->email === 'quanly1@bmw.com')
                                <x-dropdown-link :href="route('admin.products.index')">
                                    {{ __('Trang Quản trị') }}
                                </x-dropdown-link>
                            @endif

                            <x-dropdown-link :href="route('dashboard')">
                                {{ __('Garage của tôi') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Cài đặt tài khoản') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Đăng xuất') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-none text-zinc-400 hover:text-white hover:bg-zinc-900 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-zinc-900 border-b border-zinc-800">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('products.index', ['type' => 'car'])" :active="request('type') == 'car'">
                Ô tô
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('products.index', ['type' => 'motorbike'])" :active="request('type') == 'motorbike'">
                Xe máy
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('services.index')" :active="request()->is('services*')">
                Dịch vụ
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('experiences.index')" :active="request()->is('experiences*')">
                Trải nghiệm
            </x-responsive-nav-link>
        </div>

        @auth
            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-zinc-800">
                <div class="px-4">
                    <div class="font-black text-xs uppercase tracking-widest text-white">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-[10px] text-zinc-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('dashboard')">
                        {{ __('My Garage') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Settings') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
