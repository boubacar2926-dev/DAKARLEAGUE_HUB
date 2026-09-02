@props(['path' => null, 'name' => '', 'size' => 'md', 'shape' => 'circle'])

@php
    $sizeClasses = match ($size) {
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-16 h-16 text-lg',
        'xl' => 'w-24 h-24 text-2xl',
        default => 'w-10 h-10 text-sm',
    };

    $shapeClass = $shape === 'square' ? 'rounded-md' : 'rounded-full';

    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
@endphp

@if ($path)
    <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="{{ $name }}"
        {{ $attributes->merge(['class' => "{$sizeClasses} {$shapeClass} block aspect-square object-cover object-center overflow-hidden shrink-0 bg-surface border border-border"]) }}>
@else
    <div {{ $attributes->merge(['class' => "{$sizeClasses} {$shapeClass} aspect-square overflow-hidden shrink-0 bg-surface border border-border flex items-center justify-center leading-none text-text-muted font-display font-semibold"]) }}>
        {{ strtoupper($initials) ?: '?' }}
    </div>
@endif
