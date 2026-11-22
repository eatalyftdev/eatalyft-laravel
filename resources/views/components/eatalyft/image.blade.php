@props([
    'src' => null,
    'manifestKey' => null,
    'fallbackKey' => null,
    'alt' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'decoding' => 'async',
    'class' => '',
    'objectFit' => 'cover',
    'priority' => false,
])

@php
    use Illuminate\Support\Facades\Config;

    $assetConfig = config('eatalyft_assets', []);
    $basePath = $assetConfig['path'] ?? 'assets/eatalyft';

    // Resolve the source URL
    $resolvedSrc = null;

    if ($manifestKey) {
        $segments = explode('.', $manifestKey);
        $value = $assetConfig;
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                $value = null;
                break;
            }
            $value = $value[$segment];
        }
        if (is_string($value) && $value !== '') {
            $resolvedSrc = asset($basePath . '/' . $value);
        }
    }

    if ($resolvedSrc === null && $src) {
        $resolvedSrc = $src;
    }

    // Resolve fallback
    $resolvedFallback = null;
    if ($fallbackKey) {
        $segments = explode('.', $fallbackKey);
        $value = $assetConfig;
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                $value = null;
                break;
            }
            $value = $value[$segment];
        }
        if (is_string($value) && $value !== '') {
            $resolvedFallback = asset($basePath . '/' . $value);
        }
    }

    if ($resolvedFallback === null) {
        // Default fallback: destination placeholder
        $defaultFallback = $assetConfig['placeholders']['destination'] ?? 'placeholders/destination.svg';
        $resolvedFallback = asset($basePath . '/' . $defaultFallback);
    }

    // Determine loading attribute (eager for priority/hero images)
    $loadingAttr = ($priority || $loading === 'eager') ? 'eager' : 'lazy';

    // Build class list
    $classes = trim('ely-img ' . $class);

    // Decode alt: empty string means decorative (aria-hidden)
    $isDecorative = $alt === '';
    $ariaAttr = $isDecorative ? 'aria-hidden="true"' : 'alt="' . e($alt) . '"';
@endphp

<img
    src="{{ $resolvedSrc }}"
    {{ $ariaAttr }}
    width="{{ $width }}"
    height="{{ $height }}"
    loading="{{ $loadingAttr }}"
    decoding="{{ $decoding }}"
    class="{{ $classes }}"
    style="object-fit: {{ $objectFit }}; {{ $priority ? '' : '' }}"
    onerror="this.onerror=null; this.src='{{ $resolvedFallback }}';"
>
