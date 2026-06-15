<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'BMW Admin') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 font-sans text-zinc-100 antialiased selection:bg-[#1C69D4] selection:text-white" x-data="{ sidebarOpen: false }">
        @php
            $sectionLabels = [
                'products' => 'Kho sản phẩm',
                'categories' => 'Dòng xe',
                'articles' => 'Bài viết',
                'appointments' => 'Lịch hẹn',
                'accessory-orders' => 'Đơn phụ kiện',
                'users' => 'Nhân sự',
                'customers' => 'Khách hàng',
            ];
            $sectionLabels['site-settings'] = 'Thiết lập giao diện';
            $currentSection = request()->segment(2);
        @endphp

        <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(28,105,212,0.12),transparent_34rem),linear-gradient(180deg,#09090b_0%,#000_100%)]">
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-black/75 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"
            ></div>

            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-800 bg-black/90 backdrop-blur-xl transition-transform duration-300 lg:translate-x-0"
            >
                <div class="border-b border-zinc-900 px-6 py-6">
                    <div class="flex items-center justify-between gap-4">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                            <img src="{{ asset('images/bmw-logo.svg') }}" class="h-10 w-10 shrink-0 object-contain" style="width: 40px; height: 40px;" alt="BMW Logo">
                            <div>
                                <p class="text-sm font-black uppercase tracking-[0.24em] text-white">BMW Admin</p>
                                <p class="mt-1 text-[9px] font-black uppercase tracking-[0.22em] text-zinc-600">Showroom CRM</p>
                            </div>
                        </a>

                        <button type="button" class="border border-zinc-800 p-2 text-zinc-500 transition-colors hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Đóng menu">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <nav class="scrollbar-none flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 py-4 [mask-image:linear-gradient(to_bottom,transparent,black_1rem,black_calc(100%_-_1rem),transparent)]">
                    <section>
                        <p class="mb-2 px-4 text-[9px] font-black uppercase tracking-[0.28em] text-zinc-700">Điều hành</p>
                        <div class="space-y-1">
                            <x-admin.sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 15l4-4 3 3 5-7" />
                                    </svg>
                                    Tổng quan CRM
                                </span>
                            </x-admin.sidebar-link>

                            <x-admin.sidebar-link :href="route('admin.appointments.index')" :active="request()->routeIs('admin.appointments.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Lịch hẹn
                                </span>
                            </x-admin.sidebar-link>

                            <x-admin.sidebar-link :href="route('admin.accessory-orders.index')" :active="request()->routeIs('admin.accessory-orders.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7l1 13h10l1-13M9 7V5a3 3 0 016 0v2" />
                                    </svg>
                                    Đơn phụ kiện
                                </span>
                            </x-admin.sidebar-link>
                        </div>
                    </section>

                    <section>
                        <p class="mb-2 px-4 text-[9px] font-black uppercase tracking-[0.28em] text-zinc-700">Showroom</p>
                        <div class="space-y-1">
                            <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M6 7l2 13h8l2-13M9 7V5h6v2" />
                                    </svg>
                                    Kho sản phẩm
                                </span>
                            </x-admin.sidebar-link>

                            <x-admin.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h12M4 18h8" />
                                    </svg>
                                    Dòng xe
                                </span>
                            </x-admin.sidebar-link>

                            <x-admin.sidebar-link :href="route('admin.site-settings.edit')" :active="request()->routeIs('admin.site-settings.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4 4 4 4-6 4 6M4 20h16M4 4h16v16H4z" />
                                    </svg>
                                    Thiết lập giao diện
                                </span>
                            </x-admin.sidebar-link>
                        </div>
                    </section>

                    <section>
                        <p class="mb-2 px-4 text-[9px] font-black uppercase tracking-[0.28em] text-zinc-700">Nội dung</p>
                        <div class="space-y-1">
                            <x-admin.sidebar-link :href="route('admin.articles.index')" :active="request()->routeIs('admin.articles.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16M4 10h16M4 15h10M4 20h7" />
                                    </svg>
                                    Bài viết
                                </span>
                            </x-admin.sidebar-link>
                        </div>
                    </section>

                    <section>
                        <p class="mb-2 px-4 text-[9px] font-black uppercase tracking-[0.28em] text-zinc-700">Tài khoản</p>
                        <div class="space-y-1">
                            <x-admin.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Nhân sự
                                </span>
                            </x-admin.sidebar-link>

                            <x-admin.sidebar-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')">
                                <span class="flex items-center gap-3">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 10-8 0M4 21a8 8 0 0116 0" />
                                    </svg>
                                    Khách hàng
                                </span>
                            </x-admin.sidebar-link>
                        </div>
                    </section>
                </nav>

                <div class="border-t border-zinc-900 p-4">
                    <div class="border border-zinc-800 bg-zinc-950 p-4">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center border border-zinc-800 bg-black">
                                <span class="text-xs font-black uppercase text-[#1C69D4]">{{ Str::of(Auth::user()->name)->substr(0, 1) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-black uppercase tracking-wider text-white">{{ Auth::user()->name }}</p>
                                <p class="mt-1 truncate text-[10px] font-medium text-zinc-600">{{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full border border-zinc-800 px-4 py-3 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:border-rose-500/50 hover:text-rose-400">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="lg:pl-72">
                <header class="sticky top-0 z-30 border-b border-zinc-900 bg-black/70 backdrop-blur-xl">
                    <div class="flex min-h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-4">
                            <button type="button" class="border border-zinc-800 p-2 text-zinc-400 transition-colors hover:text-white lg:hidden" @click="sidebarOpen = true" aria-label="Mở menu">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0">
                                <p class="text-[9px] font-black uppercase tracking-[0.28em] text-zinc-700">Cổng quản trị</p>
                                <p class="mt-1 truncate text-sm font-black uppercase tracking-[0.16em] text-zinc-200">
                                    {{ $currentSection ? ($sectionLabels[$currentSection] ?? Str::headline($currentSection)) : 'Dashboard' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('home') }}" class="hidden border border-zinc-800 px-4 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-colors hover:border-white hover:text-white sm:inline-flex">
                                Xem website
                            </a>
                            <div class="flex items-center gap-2 border border-zinc-800 bg-zinc-950 px-3 py-2">
                                <span class="h-1.5 w-1.5 bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.8)]"></span>
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-zinc-500">System live</span>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="min-h-[calc(100vh-5rem)] px-4 py-8 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-[1500px]">
                        @if (session('success'))
                            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="fixed bottom-6 right-6 z-[100] w-[calc(100%-3rem)] max-w-md">
                                <div class="border border-emerald-500/20 bg-zinc-950 p-4 shadow-2xl">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center border border-emerald-500/20 bg-emerald-500/10 text-emerald-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-400">Thông báo</p>
                                            <p class="mt-1 text-sm font-semibold leading-5 text-zinc-200">{{ session('success') }}</p>
                                        </div>
                                        <button type="button" @click="show = false" class="text-zinc-600 transition-colors hover:text-white" aria-label="Đóng thông báo">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (session('error'))
                            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="fixed bottom-6 right-6 z-[100] w-[calc(100%-3rem)] max-w-md">
                                <div class="border border-rose-500/20 bg-zinc-950 p-4 shadow-2xl">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center border border-rose-500/20 bg-rose-500/10 text-rose-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-rose-400">Lỗi hệ thống</p>
                                            <p class="mt-1 text-sm font-semibold leading-5 text-zinc-200">{{ session('error') }}</p>
                                        </div>
                                        <button type="button" @click="show = false" class="text-zinc-600 transition-colors hover:text-white" aria-label="Đóng thông báo">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <x-admin.delete-confirm-modal />
    </body>
</html>
