<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;300;400;700;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-black text-white selection:bg-[#1C69D4] selection:text-white">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <aside class="w-64 bg-zinc-950 border-r border-zinc-900 flex flex-col">
                <div class="p-8">
                    <a href="{{ route('dashboard') }}" class="font-black text-2xl tracking-tighter uppercase italic">
                        BMW <small class="font-light not-italic text-sm text-[#1C69D4]">Admin</small>
                    </a>
                </div>

                <nav class="flex-1 px-4 space-y-2">
                    <x-admin.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        Quản lý Xe
                    </x-admin.sidebar-link>
                    <x-admin.sidebar-link href="#" :active="false">
                        Thương hiệu
                    </x-admin.sidebar-link>
                    <x-admin.sidebar-link href="#" :active="false">
                        Đơn đặt cọc
                    </x-admin.sidebar-link>
                </nav>

                <div class="p-8 border-t border-zinc-900">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs font-black uppercase text-zinc-500 hover:text-white transition-colors">
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-12 overflow-y-auto">
                @if (session('success'))
                    <div class="mb-8 p-4 bg-emerald-500/10 border-l-2 border-emerald-500 text-emerald-500 text-sm font-bold uppercase tracking-wider">
                        {{ session('success') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
