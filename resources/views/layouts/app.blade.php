<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'BMW Showroom')</title>

        <!-- Fonts: Inter for better Vietnamese support -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Flatpickr -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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
            <footer class="border-t border-zinc-800 bg-zinc-950 py-16 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-12 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-4 mb-6">
                                <img src="{{ asset('images/bmw-logo.svg') }}" class="h-10 w-10 shrink-0 object-contain" style="width: 40px; height: 40px;" alt="BMW Logo">
                                <h2 class="text-2xl font-black uppercase tracking-normal text-white">
                                    BMW <span class="text-zinc-500">SHOWROOM</span>
                                </h2>
                            </div>
                            <p class="max-w-md text-sm font-medium leading-7 text-zinc-500">
                                Không gian public dành cho khám phá xe, phụ kiện chính hãng, so sánh cấu hình và gửi yêu cầu tư vấn đến showroom.
                            </p>
                            <div class="mt-8 grid max-w-xl grid-cols-3 gap-px bg-zinc-800">
                                <a href="{{ route('appointments.create', ['type' => 'consult']) }}" class="bg-zinc-950 p-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-[#1C69D4]">Tư vấn</a>
                                <a href="{{ route('appointments.create', ['type' => 'quote']) }}" class="bg-zinc-950 p-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-[#1C69D4]">Báo giá</a>
                                <a href="{{ route('contact.index') }}" class="bg-zinc-950 p-4 text-center text-[10px] font-black uppercase tracking-[0.22em] text-zinc-300 transition-colors hover:text-[#1C69D4]">Liên hệ</a>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-6 text-xs font-black uppercase tracking-widest text-white">Sản phẩm</h3>
                            <ul class="space-y-4 text-sm font-medium text-zinc-500">
                                <li><a href="{{ route('products.index', ['type' => 'car']) }}" class="transition-colors hover:text-white">Ô tô BMW</a></li>
                                <li><a href="{{ route('products.index', ['type' => 'motorbike']) }}" class="transition-colors hover:text-white">BMW Motorrad</a></li>
                                <li><a href="{{ route('accessories.index') }}" class="transition-colors hover:text-white">Phụ kiện chính hãng</a></li>
                                <li><a href="{{ route('products.compare') }}" class="transition-colors hover:text-white">So sánh sản phẩm</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="mb-6 text-xs font-black uppercase tracking-widest text-white">Showroom</h3>
                            <ul class="space-y-4 text-sm font-medium text-zinc-500">
                                <li><a href="{{ route('services.index') }}" class="transition-colors hover:text-white">Dịch vụ</a></li>
                                <li><a href="{{ route('experiences.index') }}" class="transition-colors hover:text-white">Trải nghiệm</a></li>
                                <li><a href="{{ route('offers.exclusive') }}" class="transition-colors hover:text-white">Ưu đãi</a></li>
                                <li><a href="{{ route('contact.index') }}" class="transition-colors hover:text-white">Liên hệ showroom</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-16 flex flex-col gap-4 border-t border-zinc-800 pt-8 text-[10px] font-bold uppercase tracking-widest text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
                        <p>© {{ date('Y') }} BMW Group Vietnam. Bảo lưu mọi quyền.</p>
                        <p><a href="{{ route('policy.privacy') }}" class="transition-colors hover:text-white">Chính sách bảo mật</a> | <a href="{{ route('contact.index') }}" class="transition-colors hover:text-white">Liên hệ chúng tôi</a></p>
                    </div>
                </div>
            </footer>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Flatpickr
                flatpickr(".flatpickr-input", {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    altInput: true,
                    altFormat: "d/m/Y H:i",
                    allowInput: true,
                    minDate: "today",
                    time_24hr: true
                });
            });
        </script>

        {{-- Floating Comparison Bar --}}
        <div id="comparison-bar" x-data="{ 
            ids: getComparisonIds(),
            update() { this.ids = getComparisonIds() },
            compare() {
                if(this.ids.length < 2) {
                    alert('Vui lòng chọn ít nhất 2 xe để so sánh.');
                    return;
                }
                window.location.href = '{{ route('products.compare') }}?ids=' + this.ids.join(',');
            }
        }" 
        x-show="ids.length > 0"
        @comparison-updated.window="update()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-[60] bg-zinc-950 border-t border-zinc-800 py-6 px-4 shadow-[0_-10px_40px_rgba(0,0,0,0.5)]"
        style="display: none;">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-6">
                    <div class="flex -space-x-3 overflow-hidden">
                        <template x-for="id in ids" :key="id">
                            <div class="flex h-10 w-10 items-center justify-center border-2 border-zinc-950 bg-zinc-900 text-[10px] font-black uppercase tracking-normal text-white">
                                BMW
                            </div>
                        </template>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] font-black text-accent uppercase tracking-[0.4em]">Comparison</p>
                        <p class="text-sm font-black text-white uppercase tracking-widest">
                            Đã chọn <span x-text="ids.length" class="text-accent"></span> mẫu xe
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <button @click="localStorage.removeItem('bmw_comparison_ids'); update();" class="text-[10px] font-black text-zinc-500 uppercase tracking-widest hover:text-white transition-colors">
                        Xóa tất cả
                    </button>
                    <button @click="compare()" class="px-10 py-4 bg-white text-black text-[10px] font-black uppercase tracking-[0.3em] hover:bg-zinc-200 transition-all flex-grow md:flex-grow-0">
                        So sánh ngay
                    </button>
                </div>
            </div>
        </div>
    </body>
</html>
