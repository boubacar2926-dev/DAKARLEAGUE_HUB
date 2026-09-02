<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Tableau de bord organisateur</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="flex items-center justify-between">
                <h3 class="font-display font-semibold text-lg text-white">Mes compétitions</h3>
                @can('create', \App\Models\Competition::class)
                    <a href="{{ route('admin.competitions.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                        + Nouvelle compétition
                    </a>
                @endcan
            </div>

            @if ($competitions->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Vous n'organisez encore aucune compétition.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($competitions as $competition)
                        <a href="{{ route('admin.competitions.show', $competition) }}" class="bg-surface border border-border rounded-lg p-5 hover:border-primary transition">
                            <div class="flex items-center justify-between mb-2">
                                <x-status-badge :status="$competition->status" />
                                <span class="text-xs text-text-muted">{{ $competition->season }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-avatar :path="$competition->logo_path" :name="$competition->name" size="xs" />
                                <div class="font-display font-semibold text-white">{{ $competition->name }}</div>
                            </div>
                            <div class="text-xs text-text-muted mt-2">{{ $competition->teams_count }} équipes · {{ $competition->matches_count }} matchs</div>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-border rounded-lg p-6">
                    <h3 class="font-display font-semibold text-white mb-4">Prochaines rencontres</h3>
                    @forelse ($upcomingMatches as $match)
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0 text-sm">
                            <span class="text-white">{{ $match->homeTeam->name }} — {{ $match->awayTeam->name }}</span>
                            <span class="text-text-muted">{{ $match->scheduled_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-text-muted">Aucune rencontre programmée avec une date.</p>
                    @endforelse
                </div>

                <div class="bg-surface border border-border rounded-lg p-6">
                    <h3 class="font-display font-semibold text-white mb-4">Résultats à saisir</h3>
                    @forelse ($pendingResults as $match)
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0 text-sm">
                            <span class="text-white">{{ $match->homeTeam->name }} — {{ $match->awayTeam->name }}</span>
                            <a href="{{ route('admin.matches.result.edit', $match) }}" class="text-primary hover:underline">Saisir</a>
                        </div>
                    @empty
                        <p class="text-sm text-text-muted">Aucun résultat en attente.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
