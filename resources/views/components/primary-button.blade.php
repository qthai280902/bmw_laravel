<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-12 py-4 bg-[#1C69D4] border border-transparent font-black uppercase text-[10px] text-white tracking-[0.2em] hover:bg-[#165bb0] active:bg-[#124d96] focus:outline-none focus:ring-0 transition-all duration-300 rounded-none shadow-[0_10px_20px_rgba(28,105,212,0.3)] hover:shadow-[0_15px_30px_rgba(28,105,212,0.4)] hover:-translate-y-0.5']) }}>
    {{ $slot }}
</button>
