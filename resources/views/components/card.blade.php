@props(['variant' => 'default', 'bordered' => true, 'shadow' => false, 'hover' => false])

@php
    $variantClasses = match ($variant) {
        'default' => 'bg-' . ($bordered ? 'card' : 'transparent') . ' border-' . ($bordered ? 'border' : 'transparent'),
        'elevated' => 'bg-card shadow-md',
        'pinned' => 'bg-card shadow-lg',
        'glass' => 'glass-panel',
        default => 'bg-card border',
    };
    $hoverClass = $hover ? 'hover-shadow-md transition-shadow duration-200' : '';
    $shadowClass = $shadow ? 'shadow-md' : '';
@endphp

<div class="card {{ $variantClasses }} {{ $shadowClass }} {{ $hoverClass }}" {{ $attributes->merge(['class' => '']) }}>
    {{ $slot }}
</div>
