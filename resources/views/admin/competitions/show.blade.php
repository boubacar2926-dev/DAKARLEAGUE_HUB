<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <x-avatar :path="$competition->logo_path" :name="$competition->name" size="xl" />
                <div>
                    <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }}</h2>
                    <p class="text-sm text-text-muted mt-1">Saison {{ $competition->season }} · <x-status-badge :status="$competition->status" /></p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('public.competitions.show', $competition) }}" target="_blank" class="px-4 py-2 text-xs font-semibold uppercase tracking-widest text-text-muted border border-border rounded-md hover:text-white">
                    Voir la page publique
                </a>
                @can('update', $competition)
                    <a href="{{ route('admin.competitions.edit', $competition) }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-widest text-black bg-primary rounded-md hover:bg-primary-dark">
                        Modifier
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-surface border border-border rounded-lg p-4">
                    <div class="text-2xl font-display font-bold text-primary">{{ $competition->teams_count }}</div>
                    <div class="text-xs text-text-muted uppercase tracking-wide">Équipes</div>
                </div>
                <div class="bg-surface border border-border rounded-lg p-4">
                    <div class="text-2xl font-display font-bold text-primary">{{ $matchesPlayed }}</div>
                    <div class="text-xs text-text-muted uppercase tracking-wide">Matchs joués</div>
                </div>
                <div class="bg-surface border border-border rounded-lg p-4">
                    <div class="text-2xl font-display font-bold text-primary">{{ $matchesScheduled }}</div>
                    <div class="text-xs text-text-muted uppercase tracking-wide">Matchs programmés</div>
                </div>
                <div class="bg-surface border border-border rounded-lg p-4">
                    <div class="text-2xl font-display font-bold text-primary">{{ $totalGoals }}</div>
                    <div class="text-xs text-text-muted uppercase tracking-wide">Buts marqués</div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('admin.competitions.teams.index', $competition) }}" class="bg-surface border border-border rounded-lg p-6 hover:border-primary transition">
                    <div class="font-display font-semibold text-white">Équipes &amp; joueurs</div>
                    <p class="text-sm text-text-muted mt-1">Gérer les équipes inscrites et leurs effectifs.</p>
                </a>
                <a href="{{ route('admin.competitions.matches.index', $competition) }}" class="bg-surface border border-border rounded-lg p-6 hover:border-primary transition">
                    <div class="font-display font-semibold text-white">Calendrier &amp; résultats</div>
                    <p class="text-sm text-text-muted mt-1">Générer le calendrier, saisir les résultats.</p>
                </a>
                <a href="{{ route('public.competitions.standings', $competition) }}" target="_blank" class="bg-surface border border-border rounded-lg p-6 hover:border-primary transition">
                    <div class="font-display font-semibold text-white">Classement public</div>
                    <p class="text-sm text-text-muted mt-1">Aperçu du classement visible par les visiteurs.</p>
                </a>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.competitions.export.calendar', $competition) }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white border border-border rounded-md hover:border-primary">
                    Exporter le calendrier (PDF)
                </a>
                <a href="{{ route('admin.competitions.export.standings', $competition) }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white border border-border rounded-md hover:border-primary">
                    Exporter le classement (PDF)
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
