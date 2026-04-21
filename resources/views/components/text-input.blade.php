@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'bg-zinc-900 border-zinc-700 focus:border-accent focus:ring-accent rounded-none shadow-sm text-zinc-100 placeholder-zinc-600']) !!}>
