@php
    $tabs = [
        'public.competitions.show' => 'Aperçu',
        'public.competitions.standings' => 'Classement',
        'public.competitions.stats' => 'Statistiques',
        'public.competitions.calendar' => 'Calendrier',
        'public.competitions.results' => 'Résultats',
    ];
@endphp

<div class="flex gap-1 border-b border-border mb-6 overflow-x-auto">
    @foreach ($tabs as $route => $label)
        <a href="{{ route($route, $competition) }}"
            class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 {{ request()->routeIs($route) ? 'border-primary text-primary' : 'border-transparent text-text-muted hover:text-white' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
