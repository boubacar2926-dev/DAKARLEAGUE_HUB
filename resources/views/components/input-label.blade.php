@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-text-muted']) }}>
    {{ $value ?? $slot }}
</label>
