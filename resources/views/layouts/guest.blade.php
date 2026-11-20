<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BMW Showroom') }}</title>

        <!-- Fonts: Be Vietnam Pro unified typography for Vietnamese UI -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-zinc-950 text-white min-h-screen selection:bg-[#1C69D4] selection:text-white">
        <div class="min-h-screen flex flex-col justify-between items-center py-12 px-4 sm:px-6 relative overflow-hidden bg-ambient-showroom">
            <!-- Ambient Glow Spotlights -->
            <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[450px] bg-[#1C69D4]/10 blur-[130px] rounded-full"></div>
            <div class="pointer-events-none absolute bottom-0 right-10 w-96 h-96 bg-[#1C69D4]/5 blur-[100px] rounded-full"></div>

            <!-- Top Header / Brand Logo -->
            <div class="w-full flex justify-center pt-4 z-10">
                <a href="/" class="group flex flex-col items-center gap-1.5 focus:outline-none">
                    <div class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 bg-[#1C69D4] rotate-45 group-hover:scale-125 transition-transform duration-300"></span>
                        <span class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white uppercase group-hover:text-zinc-100 transition-colors">
                            BMW <span class="text-[#1C69D4] font-black">SHOWROOM</span>
                        </span>
                    </div>
                    <span class="text-[11px] font-medium tracking-wider text-zinc-400 uppercase">
                        Hệ Thống Quản Trị Nội Bộ (Admin Portal)
                    </span>
                </a>
            </div>

            <!-- Main Auth Card -->
            <div class="w-full sm:max-w-md my-8 z-10">
                <div class="bg-card-luxury p-8 sm:p-10 relative">
                    <!-- Subtle top light bar -->
                    <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-[#1C69D4] to-transparent"></div>
                    {{ $slot }}
                </div>
            </div>

            <!-- Footer -->
            <div class="w-full text-center z-10">
                <p class="text-xs text-zinc-500 font-normal tracking-wide">
                    &copy; {{ date('Y') }} BMW Group Vietnam. Hệ thống quản trị đặc quyền.
                </p>
            </div>
        </div>
        @livewireScriptConfig
    </body>
</html>
