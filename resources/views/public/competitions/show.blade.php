<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-avatar :path="$competition->logo_path" :name="$competition->name" size="xl" />
                <div>
                    <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }}</h2>
                    <p class="text-sm text-text-muted mt-1">Saison {{ $competition->season }} · {{ $competition->category }} · {{ $competition->teams_count }} équipes</p>
                </div>
            </div>
            <div class="text-right">
                <x-status-badge :status="$competition->status" />
                @if ($competition->status === \App\Models\Competition::STATUS_REGISTRATION_OPEN)
                    <div class="mt-2">
                        <a href="{{ route('public.competitions.register-team', $competition) }}" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                            Inscrire mon équipe
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            @if ($competition->description)
                <p class="text-sm text-text-muted mb-8 max-w-3xl">{{ $competition->description }}</p>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-border rounded-lg p-6">
                    <h3 class="font-display font-semibold text-white mb-4">Prochaines rencontres</h3>
                    @forelse ($nextMatches as $match)
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0 text-sm">
                            <x-match-teams :match="$match" />
                            <span class="text-text-muted">{{ $match->scheduled_at?->format('d/m/Y H:i') ?? 'À confirmer' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-text-muted">Aucune rencontre programmée.</p>
                    @endforelse
                </div>

                <div class="bg-surface border border-border rounded-lg p-6">
                    <h3 class="font-display font-semibold text-white mb-4">Derniers résultats</h3>
                    @forelse ($lastResults as $match)
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0 text-sm">
                            <x-match-teams :match="$match" />
                            <span class="text-primary font-display font-semibold">{{ $match->home_score }} - {{ $match->away_score }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-text-muted">Aucun résultat disponible.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
