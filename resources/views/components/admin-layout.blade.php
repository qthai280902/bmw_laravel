<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'BMW Admin') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

        @livewireStyles
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
                'service-bookings' => 'Dịch vụ chăm sóc xe',
                'ai-conversations' => 'Lịch sử trợ lý AI',
                'users' => 'Nhân sự',
                'customers' => 'Khách hàng',
            ];
            $sectionLabels['site-settings'] = 'Thiết lập giao diện';
            $currentSection = request()->segment(2);
        @endphp

        <div class="min-h-screen bg-ambient-showroom relative">
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-black/80 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"
            ></div>

            <!-- Admin Sidebar -->
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="fixed inset-y-0 left-0 z-50 flex h-screen w-72 flex-col border-r border-zinc-800/80 bg-[#050505]/95 shadow-2xl backdrop-blur-2xl transition-transform duration-300 lg:translate-x-0"
            >
                <!-- Brand Header -->
                <div class="shrink-0 border-b border-zinc-800/80 bg-[#0B0F17]/90 px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ Auth::user()?->canAccessCrm() ? route('dashboard') : route('admin.products.index') }}" class="flex items-center gap-3">
                            <img src="{{ asset('images/bmw-logo.svg') }}" class="h-8 w-8 shrink-0 object-contain" style="width: 32px; height: 32px;" alt="BMW Logo">
                            <div>
                                <p class="text-sm font-extrabold uppercase tracking-wider text-white">BMW Admin</p>
                                <p class="text-[10px] font-medium tracking-wide text-[#70A7FF]">Cổng Quản Trị Showroom</p>
                            </div>
                        </a>

                        <button type="button" class="border border-zinc-800 p-1.5 text-zinc-500 transition-colors hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Đóng menu">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Navigation Sections -->
                <nav class="sidebar-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-3 py-3.5">
                    @if(Auth::user()?->canAccessCrm())
                        <section>
                            <p class="mb-1 px-3.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Điều hành</p>
                            <div class="space-y-0.5">
                                <x-admin.sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 15l4-4 3 3 5-7" />
                                    </svg>
                                    <span>Tổng quan CRM</span>
                                </x-admin.sidebar-link>

                                <x-admin.sidebar-link :href="route('admin.appointments.index')" :active="request()->routeIs('admin.appointments.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Lịch hẹn</span>
                                </x-admin.sidebar-link>

                                <x-admin.sidebar-link :href="route('admin.accessory-orders.index')" :active="request()->routeIs('admin.accessory-orders.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7l1 13h10l1-13M9 7V5a3 3 0 016 0v2" />
                                    </svg>
                                    <span>Đơn phụ kiện</span>
                                </x-admin.sidebar-link>

                                <x-admin.sidebar-link :href="route('admin.service-bookings.index')" :active="request()->routeIs('admin.service-bookings.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Dịch vụ chăm sóc xe</span>
                                </x-admin.sidebar-link>

                                <x-admin.sidebar-link :href="route('admin.ai-conversations.index')" :active="request()->routeIs('admin.ai-conversations.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5M5 5h14v11H8l-3 3V5z" />
                                    </svg>
                                    <span>Lịch sử trợ lý AI</span>
                                </x-admin.sidebar-link>
                            </div>
                        </section>
                    @endif

                    @if(Auth::user()?->canAccessContent())
                        <section>
                            <p class="mb-1 px-3.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Showroom & Nội dung</p>
                            <div class="space-y-0.5">
                                <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M6 7l2 13h8l2-13M9 7V5h6v2" />
                                    </svg>
                                    <span>Kho sản phẩm</span>
                                </x-admin.sidebar-link>

                                <x-admin.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h12M4 18h8" />
                                    </svg>
                                    <span>Dòng xe</span>
                                </x-admin.sidebar-link>

                                <x-admin.sidebar-link :href="route('admin.articles.index')" :active="request()->routeIs('admin.articles.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16M4 10h16M4 15h10M4 20h7" />
                                    </svg>
                                    <span>Bài viết</span>
                                </x-admin.sidebar-link>

                                @if(Auth::user()?->canManageSystem())
                                    <x-admin.sidebar-link :href="route('admin.site-settings.edit')" :active="request()->routeIs('admin.site-settings.*')">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4 4 4 4-6 4 6M4 20h16M4 4h16v16H4z" />
                                        </svg>
                                        <span>Thiết lập giao diện</span>
                                    </x-admin.sidebar-link>
                                @endif
                            </div>
                        </section>
                    @endif

                    <section>
                        <p class="mb-1 px-3.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Tài khoản</p>
                        <div class="space-y-0.5">
                            @if(Auth::user()?->canAccessCrm() || Auth::user()?->canAccessContent())
                                <x-admin.sidebar-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 10-8 0M4 21a8 8 0 0116 0" />
                                    </svg>
                                    <span>Khách hàng</span>
                                </x-admin.sidebar-link>
                            @endif

                            @if(Auth::user()?->canManageSystem())
                                <x-admin.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Nhân sự</span>
                                </x-admin.sidebar-link>
                            @endif
                        </div>
                    </section>
                </nav>

                <!-- User Footer -->
                <div class="shrink-0 border-t border-zinc-800/80 bg-[#0B0F17]/90 p-3">
                    <div class="border border-zinc-800/80 bg-zinc-950/80 p-3 shadow-sm">
                        <div class="mb-2.5 flex items-center gap-2.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center border border-zinc-700 bg-black font-bold text-xs text-[#70A7FF]">
                                {{ Str::of(Auth::user()->name)->substr(0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-xs font-semibold text-white">{{ Auth::user()->name }}</p>
                                    <span class="shrink-0 whitespace-nowrap rounded-none border border-[#1C69D4]/40 bg-[#1C69D4]/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-[#70A7FF]">
                                        {{ Auth::user()->roleLabel() }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-[10px] text-zinc-400">{{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-none border border-zinc-800 bg-black px-3 py-1.5 text-xs font-medium text-zinc-400 transition-colors hover:border-rose-500/50 hover:bg-rose-950/20 hover:text-rose-400 cursor-pointer">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Layout Shell -->
            <div class="lg:pl-72">
                <header class="sticky top-0 z-30 border-b border-zinc-800/80 bg-zinc-950/80 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-4">
                            <button type="button" class="border border-zinc-800 p-2 text-zinc-400 transition-colors hover:text-white lg:hidden" @click="sidebarOpen = true" aria-label="Mở menu">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-[#70A7FF]">Cổng Quản Trị</p>
                                <p class="mt-0.5 truncate text-sm font-bold text-zinc-100">
                                    {{ $currentSection ? ($sectionLabels[$currentSection] ?? Str::headline($currentSection)) : 'Tổng quan CRM' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if(Auth::user()?->canAccessCrm())
                                <livewire:admin.notification-bell />
                            @endif

                            <a href="{{ route('home') }}" class="hidden items-center gap-1.5 border border-zinc-800 bg-[#121215] px-3.5 py-1.5 text-xs font-medium text-zinc-400 transition-colors hover:border-zinc-700 hover:text-white sm:inline-flex">
                                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Xem Trang Chủ
                            </a>
                            <div class="flex items-center gap-2 border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5">
                                <span class="h-1.5 w-1.5 bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)] animate-pulse"></span>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400">Hệ Thống Trực Tuyến</span>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="min-h-[calc(100vh-4rem)] px-4 py-8 sm:px-6 lg:px-8 max-w-full overflow-x-hidden">
                    <div class="mx-auto max-w-[1500px]">
                        @if (session('success'))
                            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="fixed bottom-6 right-6 z-[100] w-[calc(100%-3rem)] max-w-md">
                                <div class="border border-emerald-500/30 bg-zinc-950 p-4 shadow-2xl">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Thông báo</p>
                                            <p class="mt-0.5 text-xs font-medium text-zinc-200">{{ session('success') }}</p>
                                        </div>
                                        <button type="button" @click="show = false" class="text-zinc-500 transition-colors hover:text-white" aria-label="Đóng thông báo">
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
                                <div class="border border-rose-500/30 bg-zinc-950 p-4 shadow-2xl">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center border border-rose-500/30 bg-rose-500/10 text-rose-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-rose-400 uppercase tracking-wider">Lỗi hệ thống</p>
                                            <p class="mt-0.5 text-xs font-medium text-zinc-200">{{ session('error') }}</p>
                                        </div>
                                        <button type="button" @click="show = false" class="text-zinc-500 transition-colors hover:text-white" aria-label="Đóng thông báo">
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
        @livewireScriptConfig
    </body>
</html>
