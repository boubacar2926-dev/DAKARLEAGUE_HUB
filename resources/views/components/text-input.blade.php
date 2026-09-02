@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-background border-border text-white placeholder-text-muted focus:border-primary focus:ring-primary rounded-md shadow-sm']) }}>
