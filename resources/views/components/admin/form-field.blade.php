@props([
    'name',
    'label',
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="mb-2 block text-xs font-semibold text-zinc-300">
        {{ $label }}
    </label>

    {{ $slot }}

    @if($hint)
        <p class="mt-1.5 text-xs leading-5 text-zinc-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs font-semibold text-rose-400">{{ $message }}</p>
    @enderror
</div>
