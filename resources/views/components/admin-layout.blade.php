<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'BMW Admin') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-cockpit bg-[#070A10] font-sans text-zinc-100 antialiased selection:bg-[#1C69D4] selection:text-white" x-data="{ sidebarOpen: false }">
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
                'accounting' => 'Kế toán & Sổ sách',
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
                class="fixed inset-y-0 left-0 z-50 flex h-screen w-72 flex-col border-r border-white/[0.08] bg-[#0D121C]/95 shadow-2xl shadow-black/60 backdrop-blur-2xl transition-transform duration-300 lg:translate-x-0"
            >
                <!-- Brand Header -->
                <div class="shrink-0 border-b border-white/[0.08] bg-[#0D121C] px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ Auth::user()?->canAccessCrm() ? route('dashboard') : route('admin.products.index') }}" class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#070A10] p-1.5">
                                <img src="{{ asset('images/bmw-logo.svg') }}" class="h-full w-full object-contain" alt="BMW Logo">
                            </div>
                            <div>
                                <p class="text-sm font-extrabold uppercase tracking-wider text-white">BMW Admin</p>
                                <p class="text-[11px] font-medium text-[#38BDF8]">Cổng Quản Trị Showroom</p>
                            </div>
                        </a>

                        <button type="button" class="rounded-xl border border-white/10 bg-[#131A29] p-1.5 text-zinc-400 transition-colors hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Đóng menu">
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
                                <x-admin.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.index')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Nhân sự</span>
                                </x-admin.sidebar-link>
                            @endif

                            @if(Auth::user()?->isTopSuperAdmin())
                                <x-admin.sidebar-link :href="route('admin.accounting.index')" :active="request()->routeIs('admin.accounting.*')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Kế toán &amp; Sổ sách</span>
                                </x-admin.sidebar-link>
                            @endif

                            @if(Auth::user()?->isSales())
                                <x-admin.sidebar-link :href="route('admin.sales.performance', Auth::user())" :active="request()->routeIs('admin.users.performance', 'admin.sales.performance')">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span>Hiệu suất cá nhân</span>
                                </x-admin.sidebar-link>
                            @endif
                        </div>
                    </section>
                </nav>

                <!-- User Footer -->
                <div class="shrink-0 border-t border-white/[0.08] bg-[#0D121C] p-3.5">
                    <div class="rounded-2xl border border-white/[0.08] bg-[#070A10]/90 p-3.5 shadow-sm">
                        <div class="mb-3 flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#1C69D4]/30 bg-[#1C69D4]/15 font-bold text-xs text-[#38BDF8]">
                                {{ Str::of(Auth::user()->name)->substr(0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-xs font-semibold text-white">{{ Auth::user()->name }}</p>
                                    <span class="shrink-0 whitespace-nowrap rounded-full border border-[#1C69D4]/30 bg-[#1C69D4]/15 px-2.5 py-0.5 text-[10px] font-semibold text-[#70A7FF]">
                                        {{ Auth::user()->roleLabel() }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-[11px] text-zinc-400">{{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="h-9 w-full rounded-xl border border-white/10 bg-[#131A29] px-3 text-xs font-semibold text-zinc-300 transition-colors hover:border-rose-500/40 hover:bg-rose-500/15 hover:text-rose-300 cursor-pointer">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Layout Shell -->
            <div class="flex min-h-screen flex-col lg:pl-72">
                <header class="sticky top-0 z-30 shrink-0 border-b border-white/[0.08] bg-[#070A10]/90 backdrop-blur-xl">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <button type="button" class="rounded-xl border border-white/10 bg-[#0D121C] p-2 text-zinc-400 transition-colors hover:border-[#1C69D4] hover:text-white lg:hidden cursor-pointer" @click="sidebarOpen = true" aria-label="Mở menu">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold text-[#38BDF8]">Cổng Quản Trị Showroom</p>
                                <p class="truncate text-sm font-bold text-white">
                                    {{ $currentSection ? ($sectionLabels[$currentSection] ?? Str::headline($currentSection)) : 'Tổng quan CRM' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2.5">
                            @if(Auth::user()?->canAccessCrm())
                                <livewire:admin.notification-bell />
                            @endif

                            <a href="{{ route('home') }}" class="hidden h-9 items-center gap-2 rounded-xl border border-white/10 bg-[#0D121C] px-3.5 text-xs font-semibold text-zinc-300 transition-colors hover:border-[#1C69D4] hover:bg-[#131A29] hover:text-white sm:inline-flex">
                                <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Trang Chủ
                            </a>
                            <div class="hidden h-9 items-center gap-2 rounded-full border border-emerald-500/25 bg-emerald-500/15 px-3 md:flex">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)] animate-pulse"></span>
                                <span class="text-xs font-semibold text-emerald-300">Online</span>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="relative z-10 flex-1 max-w-full overflow-x-hidden px-4 pt-8 pb-12 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-[1440px]">
                        @if (session('success'))
                            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="fixed bottom-6 right-6 z-[100] w-[calc(100%-3rem)] max-w-md">
                                <div class="rounded-2xl border border-emerald-500/30 bg-[#0D121C] p-4 shadow-2xl shadow-black/60">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-emerald-500/30 bg-emerald-500/15 text-emerald-300">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-emerald-300">Thông báo</p>
                                            <p class="mt-0.5 text-xs font-medium text-zinc-200">{{ session('success') }}</p>
                                        </div>
                                        <button type="button" @click="show = false" class="rounded-lg p-1 text-zinc-500 transition-colors hover:bg-white/5 hover:text-white" aria-label="Đóng thông báo">
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
                                <div class="rounded-2xl border border-rose-500/30 bg-[#0D121C] p-4 shadow-2xl shadow-black/60">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-rose-500/30 bg-rose-500/15 text-rose-300">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-rose-300">Lỗi hệ thống</p>
                                            <p class="mt-0.5 text-xs font-medium text-zinc-200">{{ session('error') }}</p>
                                        </div>
                                        <button type="button" @click="show = false" class="rounded-lg p-1 text-zinc-500 transition-colors hover:bg-white/5 hover:text-white" aria-label="Đóng thông báo">
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
