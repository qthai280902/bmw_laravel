<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-12 py-4 bg-rose-600 border border-transparent font-black uppercase text-[10px] text-white tracking-[0.2em] hover:bg-rose-700 active:bg-rose-800 focus:outline-none focus:ring-0 transition-all duration-300 rounded-none shadow-[0_10px_20px_rgba(225,29,72,0.2)] hover:shadow-[0_15px_30px_rgba(225,29,72,0.3)] hover:-translate-y-0.5']) }}>
    {{ $slot }}
</button>
