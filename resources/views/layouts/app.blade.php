<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BMW Showroom') }}</title>

        <!-- Fonts: Outfit for premium modern feel -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;300;400;700;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-zinc-950 text-white min-h-screen">
        <div class="flex flex-col min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-zinc-900/50 border-b border-zinc-800">
                    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-grow">
                {{ $slot }}
            </main>

            <!-- Showroom Footer -->
            <footer class="bg-zinc-950 border-t border-zinc-900 py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                        <div class="col-span-1 md:col-span-2">
                            <h2 class="text-2xl font-black uppercase tracking-tighter mb-4">
                                BMW <span class="text-accent underline decoration-2">SHOWROOM</span>
                            </h2>
                            <p class="text-zinc-500 max-w-sm leading-relaxed">
                                Trải nghiệm sự kết hợp hoàn mỹ giữa kỹ thuật cơ khí Đức và ngôn ngữ thiết kế tương lai. Tầm nhìn của chúng tôi là định nghĩa lại sự sang trọng trong kỷ nguyên số.
                            </p>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-400 mb-6">Sản phẩm</h3>
                            <ul class="space-y-4 text-sm text-zinc-500">
                                <li><a href="#" class="hover:text-white transition-colors">Ô tô (Cars)</a></li>
                                <li><a href="#" class="hover:text-white transition-colors">Xe máy (Motorcycles)</a></li>
                                <li><a href="#" class="hover:text-white transition-colors">Phụ kiện chính hãng</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-400 mb-6">Liên hệ</h3>
                            <ul class="space-y-4 text-sm text-zinc-500">
                                <li><a href="#" class="hover:text-white transition-colors">Showroom Hà Nội</a></li>
                                <li><a href="#" class="hover:text-white transition-colors">Showroom TP. HCM</a></li>
                                <li><a href="#" class="hover:text-white transition-colors">Hotline: 1800-BMW-SERIES</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-16 pt-8 border-t border-zinc-900 flex justify-between items-center text-[10px] uppercase tracking-widest text-zinc-600">
                        <p>© {{ date('Y') }} BMW Group Vietnam. All rights reserved.</p>
                        <p>Privacy Policy | Contact Us</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
