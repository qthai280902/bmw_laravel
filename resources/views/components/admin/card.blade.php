<div {{ $attributes->merge(['class' => 'bg-zinc-900 border border-zinc-800 p-6 shadow-2xl relative overflow-hidden']) }}>
    <div class="absolute top-0 right-0 w-24 h-24 -mr-12 -mt-12 bg-[#1C69D4]/5 rounded-full blur-3xl"></div>
    {{ $slot }}
</div>
