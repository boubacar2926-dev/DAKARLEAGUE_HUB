<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Résultats</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            @if ($matches->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Aucun résultat publié pour le moment.
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($matches as $match)
                        <div class="bg-surface border border-border rounded-lg p-4" x-data="{ open: false }">
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="flex items-center gap-2 text-white font-medium">
                                    <x-avatar :path="$match->homeTeam->logo_path" :name="$match->homeTeam->name" size="md" />
                                    {{ $match->homeTeam->name }}
                                </span>
                                <span class="font-display font-bold text-primary text-lg">{{ $match->home_score }} - {{ $match->away_score }}</span>
                                <span class="flex items-center gap-2 text-white font-medium">
                                    {{ $match->awayTeam->name }}
                                    <x-avatar :path="$match->awayTeam->logo_path" :name="$match->awayTeam->name" size="md" />
                                </span>
                            </div>
                            @if ($match->events->isNotEmpty())
                                @php
                                    $eventsByTeam = $match->events->sortBy('minute');
                                    $homeEvents = $eventsByTeam->where('team_id', $match->home_team_id)->values();
                                    $awayEvents = $eventsByTeam->where('team_id', $match->away_team_id)->values();
                                @endphp
                                <div class="grid grid-cols-2 gap-4 text-xs text-text-muted border-t border-border pt-2 mt-2">
                                    <div class="space-y-1.5">
                                        @foreach ($homeEvents as $event)
                                            <div class="flex items-center gap-2">
                                                <x-avatar :path="$event->player?->photo_path" :name="$event->player?->fullName() ?? '?'" size="sm" />
                                                <span>
                                                    @switch($event->type)
                                                        @case('but') ⚽ @break
                                                        @case('but_penalty') ⚽ (pen.) @break
                                                        @case('but_contre_son_camp') ⚽ (csc) @break
                                                        @case('carton_jaune') 🟨 @break
                                                        @case('carton_rouge') 🟥 @break
                                                    @endswitch
                                                    {{ $event->player?->fullName() ?? '' }}
                                                    @if($event->minute) {{ $event->minute }}' @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="space-y-1.5">
                                        @foreach ($awayEvents as $event)
                                            <div class="flex items-center justify-end gap-2 text-right">
                                                <span>
                                                    {{ $event->player?->fullName() ?? '' }}
                                                    @if($event->minute) {{ $event->minute }}' @endif
                                                    @switch($event->type)
                                                        @case('but') ⚽ @break
                                                        @case('but_penalty') ⚽ (pen.) @break
                                                        @case('but_contre_son_camp') ⚽ (csc) @break
                                                        @case('carton_jaune') 🟨 @break
                                                        @case('carton_rouge') 🟥 @break
                                                    @endswitch
                                                </span>
                                                <x-avatar :path="$event->player?->photo_path" :name="$event->player?->fullName() ?? '?'" size="sm" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($match->lineups->isNotEmpty())
                                <div class="text-center mt-2 pt-2 border-t border-border">
                                    <button type="button" @click="open = !open" class="text-xs text-primary hover:underline">
                                        <span x-show="!open">Voir la composition</span>
                                        <span x-show="open">Masquer la composition</span>
                                    </button>
                                </div>
                                <div x-show="open" x-cloak class="grid grid-cols-2 gap-4 mt-3 text-xs">
                                    @foreach ([$match->home_team_id => $match->homeTeam->name, $match->away_team_id => $match->awayTeam->name] as $teamId => $teamName)
                                        <div>
                                            <div class="text-text-muted uppercase mb-1">{{ $teamName }}</div>
                                            @php $teamLineups = $match->lineups->where('team_id', $teamId); @endphp
                                            <div class="text-white">
                                                <strong class="text-text-muted font-normal">Titulaires :</strong>
                                                {{ $teamLineups->where('is_starting', true)->map(fn ($l) => $l->player?->fullName())->filter()->join(', ') ?: '—' }}
                                            </div>
                                            <div class="text-white mt-1">
                                                <strong class="text-text-muted font-normal">Remplaçants :</strong>
                                                {{ $teamLineups->where('is_starting', false)->map(fn ($l) => $l->player?->fullName())->filter()->join(', ') ?: '—' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $matches->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
