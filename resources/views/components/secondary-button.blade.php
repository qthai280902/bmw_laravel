<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-12 py-4 bg-zinc-800 border border-zinc-700 font-black uppercase text-[10px] text-white tracking-[0.2em] hover:bg-zinc-700 active:bg-zinc-900 focus:outline-none focus:ring-0 transition-all duration-300 rounded-none shadow-xl hover:-translate-y-0.5']) }}>
    {{ $slot }}
</button>
