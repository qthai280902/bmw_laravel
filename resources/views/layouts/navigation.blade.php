<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-white/10 bg-black/90 text-white backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-[4.5rem] items-center justify-between gap-5">
            <div class="flex min-w-0 items-center gap-8">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset('images/bmw-logo.svg') }}" class="h-10 w-10 shrink-0 object-contain" style="width: 40px; height: 40px;" alt="BMW Logo">
                    <span class="hidden text-sm font-black uppercase tracking-[0.22em] text-white sm:block">
                        BMW <span class="text-zinc-500">Showroom</span>
                    </span>
                </a>

                <div class="hidden items-center gap-1 xl:flex">
                    <a href="{{ route('products.index', ['type' => 'car']) }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request('type') == 'car' ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        Ô tô
                    </a>
                    <a href="{{ route('products.index', ['type' => 'motorbike']) }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request('type') == 'motorbike' ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        Xe máy
                    </a>
                    <a href="{{ route('accessories.index') }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request()->is('accessories*') ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        Phụ kiện
                    </a>
                    <a href="{{ route('services.index') }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request()->is('services*') ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        Dịch vụ
                    </a>
                    <a href="{{ route('experiences.index') }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request()->is('experiences*') ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        Trải nghiệm
                    </a>
                    <a href="{{ route('articles.index') }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request()->is('tim-hieu-them*') ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        Tìm hiểu thêm
                    </a>
                    <a href="{{ route('products.compare') }}" class="px-3 py-7 text-[10px] font-black uppercase tracking-[0.22em] transition-colors {{ request()->is('compare*') ? 'text-white' : 'text-zinc-500 hover:text-white' }}">
                        So sánh
                    </a>
                </div>
            </div>

            <div class="hidden items-center gap-4 xl:flex">
                <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="border border-white/20 px-5 py-3 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-all hover:border-white hover:bg-white hover:text-black">
                    Tư vấn
                </a>

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 border border-transparent px-3 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-zinc-500 transition-colors hover:text-white focus:outline-none">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
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
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Đăng xuất') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <button @click="open = ! open" class="inline-flex items-center justify-center border border-white/10 p-3 text-zinc-400 transition-colors hover:border-white hover:text-white xl:hidden" aria-label="Mở menu">
                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/10 bg-black xl:hidden">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-px bg-white/10 px-4 py-4 sm:grid-cols-2 sm:px-6 lg:px-8">
            <a href="{{ route('products.index', ['type' => 'car']) }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">Ô tô</a>
            <a href="{{ route('products.index', ['type' => 'motorbike']) }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">Xe máy</a>
            <a href="{{ route('accessories.index') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">Phụ kiện</a>
            <a href="{{ route('services.index') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">Dịch vụ</a>
            <a href="{{ route('experiences.index') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">Trải nghiệm</a>
            <a href="{{ route('articles.index') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">Tìm hiểu thêm</a>
            <a href="{{ route('products.compare') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-white">So sánh</a>
            <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="bg-[#1C69D4] px-5 py-4 text-[10px] font-black uppercase tracking-[0.22em] text-white transition-colors hover:bg-white hover:text-black">Tư vấn</a>
        </div>

        @auth
            <div class="border-t border-white/10 px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <p class="text-xs font-black uppercase tracking-widest text-white">{{ Auth::user()->name }}</p>
                    <p class="mt-1 text-[10px] font-medium text-zinc-500">{{ Auth::user()->email }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-px bg-white/10 sm:grid-cols-3">
                        <a href="{{ route('dashboard') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300">Garage</a>
                        <a href="{{ route('profile.edit') }}" class="bg-zinc-950 px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300">Cài đặt</a>
                        <form method="POST" action="{{ route('logout') }}" class="bg-zinc-950">
                            @csrf
                            <button type="submit" class="w-full px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-zinc-300">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth
    </div>
</nav>
