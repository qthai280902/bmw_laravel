<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.35em] text-[#1C69D4]">BMW stories</p>
                <h1 class="mt-3 text-4xl font-black leading-tight tracking-tight text-white md:text-6xl">
                    Tìm hiểu thêm
                </h1>
                <p class="mt-5 max-w-2xl text-sm font-medium leading-relaxed text-zinc-400">
                    Cập nhật ưu đãi, sự kiện showroom, chương trình bán hàng và các trải nghiệm BMW được biên tập cho khách hàng đang tìm hiểu xe.
                </p>
            </div>
        </div>
    </x-slot>

    <livewire:article-hub />
</x-app-layout>
