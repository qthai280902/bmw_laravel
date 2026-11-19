@props([
    'id' => null,
    'name' => null,
    'value' => '',
    'options' => [],
    'placeholder' => null,
    'autoSubmit' => false,
    'disabled' => false,
    'wireModel' => null,
    'wireLive' => true,
    'wireAction' => null,
    'wireRecordId' => null,
    'compact' => false,
    'ariaLabel' => null,
    'errorField' => null,
    'showError' => false,
])

@php
    $normalized = [];

    if ($placeholder !== null) {
        $hasEmptyKey = false;
        foreach ($options as $k => $v) {
            if (is_array($v) && array_key_exists('value', $v) && (string) $v['value'] === '') {
                $hasEmptyKey = true;
                break;
            }
            if (! is_array($v) && (string) $k === '') {
                $hasEmptyKey = true;
                break;
            }
        }

        if (! $hasEmptyKey) {
            $normalized[] = [
                'type' => 'option',
                'value' => '',
                'label' => (string) $placeholder,
            ];
        }
    }

    foreach ($options as $key => $item) {
        if (is_array($item) && isset($item['group']) && isset($item['options']) && is_array($item['options'])) {
            $groupItems = [];
            foreach ($item['options'] as $gKey => $gVal) {
                if (is_array($gVal) && array_key_exists('value', $gVal)) {
                    $groupItems[] = [
                        'value' => (string) $gVal['value'],
                        'label' => (string) ($gVal['label'] ?? $gVal['value']),
                    ];
                } else {
                    $groupItems[] = [
                        'value' => (string) $gKey,
                        'label' => (string) $gVal,
                    ];
                }
            }

            $normalized[] = [
                'type' => 'group',
                'label' => (string) $item['group'],
                'options' => $groupItems,
            ];
        } elseif (is_array($item) && array_key_exists('value', $item)) {
            $optVal = $item['value'] instanceof \BackedEnum ? $item['value']->value : $item['value'];
            $optLabel = ($item['label'] ?? $optVal) instanceof \BackedEnum ? ($item['label'] ?? $optVal)->value : ($item['label'] ?? $optVal);
            $normalized[] = [
                'type' => 'option',
                'value' => (string) $optVal,
                'label' => (string) $optLabel,
            ];
        } else {
            $optVal = $key instanceof \BackedEnum ? $key->value : $key;
            $optLabel = $item instanceof \BackedEnum ? $item->value : $item;
            $normalized[] = [
                'type' => 'option',
                'value' => (string) $optVal,
                'label' => (string) $optLabel,
            ];
        }
    }

    $initialValue = $value instanceof \BackedEnum ? (string) $value->value : (string) ($value ?? '');
    $optionsJson = json_encode($normalized, JSON_UNESCAPED_UNICODE);
    $initConfigJson = json_encode([
        'initial' => $initialValue,
        'wireModel' => $wireModel,
        'wireLive' => (bool) $wireLive,
        'wireAction' => $wireAction,
        'wireRecordId' => $wireRecordId,
        'autoSubmit' => (bool) $autoSubmit,
        'placeholder' => (string) ($placeholder ?? 'Chọn một mục...'),
    ], JSON_UNESCAPED_UNICODE);

    $triggerSizeClasses = $compact
        ? 'h-9 px-3 text-[10px] font-black uppercase tracking-[0.14em]'
        : 'h-10 px-3.5 text-xs font-semibold';

    $resolvedErrorField = $errorField ?? $name ?? $wireModel;
@endphp

<div
    x-data="bmwCustomSelect()"
    x-modelable="selected"
    data-config="{{ $initConfigJson }}"
    data-options="{{ $optionsJson }}"
    data-disabled="{{ $disabled ? 'true' : 'false' }}"
    @keydown.escape.window="close()"
    @scroll.window="if (open) updatePosition()"
    @resize.window="if (open) updatePosition()"
    {{ $attributes->except(['id', 'name', 'value', 'options', 'placeholder', 'autoSubmit', 'disabled', 'wireModel', 'wireLive', 'wireAction', 'wireRecordId', 'compact', 'ariaLabel', 'errorField', 'showError', 'required'])->merge(['class' => 'relative w-full']) }}
>
    @if($name)
        {{-- Never attach required on hidden input to prevent unfocusable form control error --}}
        <input
            type="hidden"
            name="{{ $name }}"
            value="{{ $initialValue }}"
            x-bind:value="selected"
        >
    @endif

    <button
        type="button"
        x-ref="trigger"
        @if($id) id="{{ $id }}" @endif
        @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        aria-haspopup="listbox"
        :aria-expanded="open ? 'true' : 'false'"
        @if($disabled) disabled @endif
        @click="toggle()"
        class="flex w-full items-center justify-between gap-2.5 rounded-none border border-[#242B38] bg-[#0B0F17] text-left text-zinc-100 transition-colors hover:border-[#1C69D4] focus:border-[#1C69D4] focus:outline-none focus:ring-1 focus:ring-[#1C69D4] cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 {{ $triggerSizeClasses }}"
        :class="open ? 'border-[#1C69D4] ring-1 ring-[#1C69D4]' : ''"
    >
        <span class="truncate" x-text="selectedLabel">{{ collect($normalized)->flatMap(fn ($i) => ($i['type'] ?? '') === 'group' ? $i['options'] : [$i])->firstWhere('value', $initialValue)['label'] ?? ($placeholder ?? 'Chọn một mục...') }}</span>
        <svg
            class="h-3.5 w-3.5 shrink-0 text-zinc-400 transition-transform duration-200"
            :class="open ? 'rotate-180 text-[#70A7FF]' : ''"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            @click.outside="if (!$refs.trigger || !$refs.trigger.contains($event.target)) close()"
            :style="panelStyle"
            class="fixed z-[140] rounded-none border border-[#242B38] bg-[#0E131D] shadow-[0_22px_50px_rgba(0,0,0,0.85)]"
        >
            <ul
                role="listbox"
                class="sidebar-scroll max-h-60 overflow-y-auto divide-y divide-[#1A2230] py-1 m-0 list-none"
            >
                <template x-for="(entry, groupIdx) in groups" :key="'entry-' + groupIdx">
                    <li role="presentation" class="m-0 p-0">
                        <template x-if="entry.type === 'group'">
                            <div>
                                <div class="bg-[#080B11] px-3.5 py-2 text-[9px] font-black uppercase tracking-[0.22em] text-[#70A7FF]" x-text="entry.label"></div>
                                <ul role="group" class="divide-y divide-[#1A2230] m-0 p-0 list-none">
                                    <template x-for="(opt, optIdx) in entry.options" :key="'g-' + groupIdx + '-' + optIdx">
                                        <li
                                            role="option"
                                            :aria-selected="String(selected) === String(opt.value) ? 'true' : 'false'"
                                            @click="choose(opt.value)"
                                            class="flex items-center justify-between gap-2.5 border-l-2 px-3.5 py-2.5 text-xs transition-colors cursor-pointer"
                                            :class="String(selected) === String(opt.value)
                                                ? 'border-[#1C69D4] bg-[#162033] font-bold text-white'
                                                : 'border-transparent text-zinc-300 hover:bg-[#162033] hover:text-white'"
                                        >
                                            <span class="truncate" x-text="opt.label"></span>
                                            <svg
                                                x-show="String(selected) === String(opt.value)"
                                                class="h-3.5 w-3.5 shrink-0 text-[#1C69D4]"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <template x-if="entry.type !== 'group'">
                            <div
                                role="option"
                                :aria-selected="String(selected) === String(entry.value) ? 'true' : 'false'"
                                @click="choose(entry.value)"
                                class="flex items-center justify-between gap-2.5 border-l-2 px-3.5 py-2.5 text-xs transition-colors cursor-pointer"
                                :class="String(selected) === String(entry.value)
                                    ? 'border-[#1C69D4] bg-[#162033] font-bold text-white'
                                    : 'border-transparent text-zinc-300 hover:bg-[#162033] hover:text-white'"
                            >
                                <span class="truncate" x-text="entry.label"></span>
                                <svg
                                    x-show="String(selected) === String(entry.value)"
                                    class="h-3.5 w-3.5 shrink-0 text-[#1C69D4]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
        </div>
    </template>

    @if($showError && $resolvedErrorField)
        @error($resolvedErrorField)
            <p class="mt-1.5 text-xs font-bold text-rose-400">{{ $message }}</p>
        @enderror
    @endif
</div>
