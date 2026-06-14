@props([
    'name',
    'label',
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="mb-2 block text-[10px] font-black uppercase tracking-[0.22em] text-zinc-500">
        {{ $label }}
    </label>

    {{ $slot }}

    @if($hint)
        <p class="mt-2 text-[11px] font-medium leading-5 text-zinc-600">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-2 text-xs font-bold text-rose-400">{{ $message }}</p>
    @enderror
</div>
