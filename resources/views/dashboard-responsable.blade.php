<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Mon espace responsable</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if ($teams->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Vous n'êtes désigné responsable d'aucune équipe pour le moment.
                </div>
            @else
                @foreach ($teams as $team)
                    <div class="bg-surface border border-border rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="font-display font-semibold text-white text-lg">{{ $team->name }}</div>
                                <p class="text-sm text-text-muted">{{ $team->competition->name }} · {{ $team->players->count() }} joueurs</p>
                            </div>
                            <a href="{{ route('public.competitions.team', [$team->competition, $team]) }}" target="_blank" class="text-xs text-primary hover:underline">Voir la page publique</a>
                        </div>

                        @if ($team->competition->allow_team_managers_to_manage_players)
                            <a href="{{ route('admin.teams.players.index', $team) }}" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                                Gérer l'effectif
                            </a>
                        @else
                            <p class="text-xs text-text-muted italic">La gestion de l'effectif n'est pas activée par l'organisateur pour cette compétition.</p>
                        @endif
                    </div>
                @endforeach
            @endif

            <div class="bg-surface border border-border rounded-lg p-6">
                <h3 class="font-display font-semibold text-white mb-4">Prochaines rencontres</h3>
                @forelse ($upcomingMatches as $match)
                    <div class="flex items-center justify-between py-2 border-b border-border last:border-0 text-sm">
                        <span class="text-white">{{ $match->homeTeam->name }} — {{ $match->awayTeam->name }}</span>
                        <span class="text-text-muted">{{ $match->scheduled_at?->format('d/m/Y H:i') ?? 'À confirmer' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-text-muted">Aucune rencontre programmée.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
