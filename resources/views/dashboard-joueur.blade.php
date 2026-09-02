<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Mon espace joueur</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if ($stats->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Aucun profil joueur n'est encore associé à votre compte. Contactez l'organisateur de votre compétition.
                </div>
            @else
                @foreach ($stats as $entry)
                    @php $player = $entry['player']; @endphp
                    <div class="bg-surface border border-border rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="font-display font-semibold text-white text-lg">{{ $player->fullName() }}</div>
                                <p class="text-sm text-text-muted">{{ $player->team->name }} · {{ $player->team->competition->name }}</p>
                            </div>
                            <a href="{{ route('public.competitions.player', [$player->team->competition, $player]) }}" target="_blank" class="text-xs text-primary hover:underline">Voir ma fiche publique</a>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-display font-bold text-primary">{{ $entry['goals'] }}</div>
                                <div class="text-xs text-text-muted uppercase">Buts</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-display font-bold text-yellow-400">{{ $entry['yellow'] }}</div>
                                <div class="text-xs text-text-muted uppercase">Cartons jaunes</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-display font-bold text-danger">{{ $entry['red'] }}</div>
                                <div class="text-xs text-text-muted uppercase">Cartons rouges</div>
                            </div>
                        </div>
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
