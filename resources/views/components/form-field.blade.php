@props(['label' => null, 'name', 'type' => 'text', 'value' => old($name), 'errors' => $errors->get($name), 'required' => false, 'placeholder' => null, 'helper' => null, 'disabled' => false])

<div class="mb-3" {{ $attributes->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label required">{{ $label }}@if ($required) <span class="text-danger ms-1">*</span>@endif</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        class="form-control {{ $errors->first($name) ? 'is-invalid' : '' }} {{ $disabled ? 'disabled' : '' }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
    >

    @if ($errors->first($name))
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif

    @if ($helper)
        <div class="form-text">{{ $helper }}</div>
    @endif
</div>
