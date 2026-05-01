<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'BMW Admin') }}</title>

        <!-- Fonts (Inter for official premium feel) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-zinc-950 text-zinc-100 selection:bg-[#1C69D4] selection:text-white" x-data="{ sidebarOpen: false }">
        <div class="h-screen flex bg-zinc-950 overflow-hidden">
            
            <!-- Mobile Overlay -->
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false" 
                 class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden"></div>

            <!-- Floating Sidebar -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
                   class="fixed md:sticky md:top-4 md:left-4 z-50 w-64 h-[calc(100vh-32px)] md:m-4 bg-zinc-900/90 backdrop-blur-xl border border-zinc-800 rounded-2xl shadow-2xl flex flex-col transition-transform duration-300 ease-in-out">
                
                <div class="p-8 flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <img src="{{ asset('images/bmw-logo.svg') }}" class="h-10 w-10 shrink-0 object-contain" style="width: 40px; height: 40px;" alt="BMW Logo">
                        <span class="font-black text-xl tracking-tighter uppercase text-white md:hidden lg:block">Quản trị</span>
                    </a>
                    <button @click="sidebarOpen = false" class="md:hidden text-zinc-500 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <nav class="flex-1 px-4 space-y-2 overflow-y-auto pt-4 scrollbar-hide">
                    <div class="px-4 mb-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Hệ thống cốt lõi</p>
                    </div>
                    
                    <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Kho xe Showroom
                        </span>
                    </x-admin.sidebar-link>

                    <x-admin.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                            Các dòng xe BMW
                        </span>
                    </x-admin.sidebar-link>

                    <div class="px-4 mt-8 mb-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600">Khách hàng & Bán hàng</p>
                    </div>

                    <x-admin.sidebar-link :href="route('admin.appointments.index')" :active="request()->routeIs('admin.appointments.*')">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Lịch hẹn & Lái thử
                        </span>
                    </x-admin.sidebar-link>

                    <x-admin.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Quản lý Nhân sự
                        </span>
                    </x-admin.sidebar-link>

                    <x-admin.sidebar-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1zm0-4.708a5.992 5.992 0 00-5.923-4.35H9v1.5a.5.5 0 01-1 0v-1.5H7.5c-2.31 0-4.223 1.636-4.636 3.791l-.114.563h6.5z"></path></svg>
                            Cơ sở dữ liệu KH
                        </span>
                    </x-admin.sidebar-link>
                </nav>

                <div class="p-4 mt-auto">
                    <div class="p-4 bg-zinc-950/50 rounded-xl border border-zinc-800/50">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-zinc-800 flex items-center justify-center border border-zinc-700 overflow-hidden">
                                <img src="{{ asset('images/bmw-logo.svg') }}" class="w-8 h-8 shrink-0 object-contain" style="width: 32px; height: 32px;" alt="B">
                            </div>
                            <div class="truncate">
                                <p class="text-[10px] font-black text-white truncate uppercase tracking-wider">{{ Auth::user()->name }}</p>
                                <p class="text-[8px] text-zinc-500 truncate uppercase">Quản trị viên BMW</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full py-2 bg-zinc-900 hover:bg-zinc-800 text-[10px] font-black uppercase text-zinc-500 hover:text-white transition-all border border-zinc-800 rounded-lg">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Workspace -->
            <div class="flex-1 flex flex-col overflow-y-auto">
                
                <!-- Floating Topbar -->
                <header class="sticky top-4 z-30 h-16 mt-4 mr-4 mb-8 bg-zinc-900/60 backdrop-blur-md border border-zinc-800 rounded-2xl flex items-center justify-between px-8 shadow-xl">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = true" class="md:hidden text-zinc-400 hover:text-white p-2 hover:bg-zinc-800 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <div class="h-4 w-[1px] bg-zinc-800 mx-2 hidden md:block"></div>
                        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-zinc-500 overflow-hidden truncate max-w-[200px] sm:max-w-none">
                            <span class="hover:text-zinc-300 transition-colors">Cổng quản trị</span>
                            <span class="text-zinc-800">/</span>
                             <span class="text-zinc-100">{{ request()->segment(2) ? __(ucfirst(request()->segment(2))) : __('Dashboard') }}</span>
                        </nav>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2 px-3 py-1 bg-zinc-950 border border-zinc-800 rounded-full shadow-inner">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)]"></span>
                            <span class="text-[9px] font-black uppercase text-zinc-500 tracking-tighter">Hệ thống Live</span>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 px-4 md:px-8 pb-12">
                    <div class="max-w-[1600px] mx-auto">
                        <!-- Premium Toast Message -->
                        @if (session('success'))
                            <div x-data="{ show: true }" 
                                 x-show="show" 
                                 x-init="setTimeout(() => show = false, 5000)"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 class="fixed bottom-8 right-8 z-[100] w-full max-w-sm">
                                <div class="bg-zinc-900/95 backdrop-blur-xl border border-zinc-800 p-4 shadow-2xl rounded-2xl flex items-center gap-4">
                                    <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-0.5">Notification</p>
                                        <p class="text-xs font-bold text-zinc-300">{{ session('success') }}</p>
                                    </div>
                                    <button @click="show = false" class="text-zinc-600 hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if (session('error'))
                            <div x-data="{ show: true }" 
                                 x-show="show" 
                                 x-init="setTimeout(() => show = false, 5000)"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 class="fixed bottom-8 right-8 z-[100] w-full max-w-sm">
                                <div class="bg-zinc-900/95 backdrop-blur-xl border border-zinc-800 p-4 shadow-2xl rounded-2xl flex items-center gap-4">
                                    <div class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center border border-rose-500/20">
                                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-rose-500 mb-0.5">System Error</p>
                                        <p class="text-xs font-bold text-zinc-300">{{ session('error') }}</p>
                                    </div>
                                    <button @click="show = false" class="text-zinc-600 hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
