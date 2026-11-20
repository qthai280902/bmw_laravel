<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-10 py-3.5 bg-zinc-800 border border-zinc-700 font-bold uppercase text-[11px] text-white tracking-[0.15em] hover:bg-zinc-700 active:bg-zinc-900 focus:outline-none focus:ring-0 transition-all duration-300 rounded-lg shadow-xl hover:-translate-y-0.5']) }}>
    {{ $slot }}
</button>
