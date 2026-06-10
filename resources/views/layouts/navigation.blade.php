<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-zinc-900 bg-zinc-950/95 backdrop-blur">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 justify-between">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="group">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/bmw-logo.svg') }}" class="h-12 w-12 shrink-0 object-contain" style="width: 48px; height: 48px;" alt="BMW Logo">
                            <span class="hidden text-lg font-black uppercase tracking-[0.2em] sm:block">BMW <span class="text-zinc-500">Showroom</span></span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden h-full items-center space-x-8 lg:-my-px lg:ms-14 lg:flex">
                    <x-nav-link :href="route('products.index', ['type' => 'car'])" :active="request('type') == 'car'" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Ô tô
                    </x-nav-link>
                    <x-nav-link :href="route('products.index', ['type' => 'motorbike'])" :active="request('type') == 'motorbike'" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Xe máy
                    </x-nav-link>
                    <x-nav-link :href="route('accessories.index')" :active="request()->is('accessories*')" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Phụ kiện
                    </x-nav-link>
                    <x-nav-link :href="route('services.index')" :active="request()->is('services*')" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Dịch vụ
                    </x-nav-link>
                    <x-nav-link :href="route('experiences.index')" :active="request()->is('experiences*')" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        Trải nghiệm
                    </x-nav-link>
                    <x-nav-link :href="route('products.compare')" :active="request()->is('compare*')" class="text-sm font-black uppercase tracking-widest h-full flex items-center">
                        So sánh
                    </x-nav-link>
                </div>
            </div>

            <!-- Right Side Links -->
            <div class="hidden lg:flex lg:items-center lg:ms-6 space-x-5">
                <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="border border-zinc-800 px-5 py-3 text-[10px] font-black uppercase tracking-[0.24em] text-zinc-300 transition-all hover:border-white hover:bg-white hover:text-black">
                    Tư vấn
                </a>
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
            <div class="-me-2 flex items-center lg:hidden">
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-b border-zinc-800 bg-zinc-900 lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('products.index', ['type' => 'car'])" :active="request('type') == 'car'">
                Ô tô
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('products.index', ['type' => 'motorbike'])" :active="request('type') == 'motorbike'">
                Xe máy
            </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('accessories.index')" :active="request()->is('accessories*')">
                        Phụ kiện
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('services.index')" :active="request()->is('services*')">
                        Dịch vụ
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('experiences.index')" :active="request()->is('experiences*')">
                        Trải nghiệm
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('products.compare')" :active="request()->is('compare*')">
                        So sánh
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('appointments.create', ['type' => 'consult'])">
                        Tư vấn
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
