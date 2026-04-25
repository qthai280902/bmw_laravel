@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-2 ']) }}>
    {{ $value ?? $slot }}
</label>
