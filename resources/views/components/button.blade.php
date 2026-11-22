@props(['variant' => 'primary', 'size' => 'md', 'block' => false, 'loading' => false, 'disabled' => false, 'type' => 'button', 'href' => null])

@php
    $tag = $href ? 'a' : 'button';
    $variantClasses = match ($variant) {
        'primary' => 'btn btn-primary',
        'secondary' => 'btn btn-outline-secondary',
        'success' => 'btn btn-success',
        'danger' => 'btn btn-danger',
        'ghost' => 'btn btn-link',
        default => 'btn btn-primary',
    };
    $sizeClasses = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };
    $blockClass = $block ? 'w-100' : '';
    $disabledAttr = $disabled || $loading ? 'disabled' : '';
@endphp

@if ($tag === 'a')
    <{{ $tag }}
        href="{{ $href }}"
        class="{{ $variantClasses }} {{ $sizeClasses }} {{ $blockClass }} {{ $disabled ? 'opacity-' . ($disabled ? 50 : 100) : '' }}"
        {{ $disabledAttr ? 'aria-disabled="true"' : '' }}
        {{ $loading ? 'aria-busy="true"' : '' }}
        {{ $attributes->merge(['class' => $blockClass]) }}
    >
        @if ($loading)
            <span class="loading-spinner" aria-hidden="true"></span>
        @endif
        {{ $slot }}
    </{{ $tag }}>
@else
    <{{ $tag }}
        type="{{ $type }}"
        class="{{ $variantClasses }} {{ $sizeClasses }} {{ $blockClass }}"
        {{ $disabledAttr }}
        {{ $loading ? 'aria-busy="true"' : '' }}
        {{ $attributes->merge(['class' => $blockClass]) }}
    >
        @if ($loading)
            <span class="loading-spinner" aria-hidden="true"></span>
        @endif
        {{ $slot }}
    </{{ $tag }}>
@endif
