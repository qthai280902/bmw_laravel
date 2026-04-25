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

        <script>
            // Global Comparison State Management (BMW Senior Architect Standard)
            window.getComparisonIds = () => {
                try {
                    return JSON.parse(localStorage.getItem('bmw_comparison_ids') || '[]');
                } catch (e) {
                    return [];
                }
            };

            window.isVehicleSelected = (id) => {
                return getComparisonIds().includes(id);
            };

            window.toggleComparison = (id) => {
                let ids = getComparisonIds();
                if (ids.includes(id)) {
                    ids = ids.filter(i => i !== id);
                } else {
                    if (ids.length >= 4) {
                        alert('Bạn chỉ có thể so sánh tối đa 4 xe cùng lúc.');
                        return;
                    }
                    ids.push(id);
                }
                localStorage.setItem('bmw_comparison_ids', JSON.stringify(ids));
                window.dispatchEvent(new CustomEvent('comparison-updated', { detail: ids }));
            };
        </script>
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
            <footer class="bg-white border-t border-zinc-200 py-16 text-black">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                        <div class="col-span-1 md:col-span-2">
                            <div class="flex items-center gap-4 mb-6">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg" class="h-10 w-10" alt="BMW Logo">
                                <h2 class="text-2xl font-black uppercase tracking-tighter text-black">
                                    BMW <span class="text-zinc-400">SHOWROOM</span>
                                </h2>
                            </div>
                            <p class="text-zinc-600 max-w-sm leading-relaxed">
                                Trải nghiệm sự kết hợp hoàn mỹ giữa kỹ thuật cơ khí Đức và ngôn ngữ thiết kế tương lai. Tầm nhìn của chúng tôi là định nghĩa lại sự sang trọng trong kỷ nguyên số.
                            </p>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-black mb-6">Sản phẩm</h3>
                            <ul class="space-y-4 text-sm text-zinc-600 font-medium">
                                <li><a href="#" class="hover:text-black transition-colors">Ô tô (Cars)</a></li>
                                <li><a href="#" class="hover:text-black transition-colors">Xe máy (Motorcycles)</a></li>
                                <li><a href="#" class="hover:text-black transition-colors">Phụ kiện chính hãng</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-black mb-6">Liên hệ</h3>
                            <ul class="space-y-4 text-sm text-zinc-600 font-medium">
                                <li><a href="#" class="hover:text-black transition-colors">Showroom Hà Nội</a></li>
                                <li><a href="#" class="hover:text-black transition-colors">Showroom TP. HCM</a></li>
                                <li><a href="#" class="hover:text-black transition-colors">Hotline: 1800-BMW-SERIES</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-16 pt-8 border-t border-zinc-200 flex justify-between items-center text-[10px] uppercase tracking-widest text-zinc-500 font-bold">
                        <p>© {{ date('Y') }} BMW Group Vietnam. Bảo lưu mọi quyền.</p>
                        <p>Chính sách bảo mật | Liên hệ chúng tôi</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
