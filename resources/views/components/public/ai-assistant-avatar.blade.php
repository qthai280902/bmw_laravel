@props(['size' => 'h-12 w-12'])

<div {{ $attributes->merge(['class' => 'ai-assistant-idle relative '.$size]) }} aria-hidden="true">
    <svg viewBox="0 0 64 64" class="h-full w-full drop-shadow-[0_14px_26px_rgba(28,105,212,0.35)]" role="img">
        <title>BMW AI assistant</title>
        <circle cx="32" cy="32" r="30" fill="#050505" stroke="#27272a" stroke-width="2" />
        <path d="M17 36L22 25H42L47 36" fill="#18181b" stroke="#70A7FF" stroke-width="2" stroke-linejoin="round" />
        <path d="M23 25L28 19H36L41 25" fill="none" stroke="#52525b" stroke-width="2" stroke-linecap="round" />
        <path d="M22 36H42" stroke="#1C69D4" stroke-width="2" stroke-linecap="round" />
        <circle cx="24" cy="40" r="3" fill="#f4f4f5" />
        <circle cx="40" cy="40" r="3" fill="#f4f4f5" />
        <path d="M28 33H36" stroke="#f4f4f5" stroke-width="2" stroke-linecap="round" />
        <path d="M31 13V9H33V13" stroke="#70A7FF" stroke-width="2" stroke-linecap="round" />
        <circle cx="32" cy="8" r="2" fill="#70A7FF" />
    </svg>
    <span class="ai-assistant-ready-pulse absolute right-0.5 top-0.5 h-2.5 w-2.5 rounded-full border border-black bg-emerald-400"></span>
</div>
