<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BMW Showroom') }}</title>

        <!-- Fonts: Inter for better Vietnamese support -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-zinc-950 text-white min-h-screen">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-12 sm:pt-0 bg-black bg-[radial-gradient(circle_at_top,#1c69d410,transparent_50%)]">
            <div class="mb-8">
                <a href="/">
                    <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                        BMW <span class="text-[#1C69D4] underline decoration-4 underline-offset-8">SHOWROOM</span>
                    </h1>
                </a>
            </div>

            <div class="w-full sm:max-w-md bg-zinc-900/50 border border-zinc-800 p-8 shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                {{ $slot }}
            </div>

            <p class="mt-8 text-[10px] uppercase tracking-widest text-zinc-600 font-bold">
                Experience the Ultimate Driving Machine.
            </p>
        </div>
    </body>
</html>
