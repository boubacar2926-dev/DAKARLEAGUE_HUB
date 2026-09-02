<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-text-muted"><a href="{{ route('admin.competitions.show', $competition) }}" class="hover:text-primary">&larr; {{ $competition->name }}</a></p>
                <h2 class="font-display font-bold text-xl text-white">Calendrier &amp; résultats</h2>
            </div>
            <div class="flex gap-3">
                @if ($matches->isEmpty())
                    <form action="{{ route('admin.competitions.matches.generate', $competition) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                            Générer le calendrier
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.competitions.matches.create', $competition) }}" class="inline-flex items-center px-4 py-2 border border-border text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:border-primary">
                    + Ajouter un match
                </a>
                <a href="{{ route('admin.competitions.export.calendar', $competition) }}" class="inline-flex items-center px-4 py-2 border border-border text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:border-primary">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if ($matches->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Aucun match programmé. Générez le calendrier automatiquement ou ajoutez un match manuellement.
                </div>
            @else
                @foreach ($matches as $round => $roundMatches)
                    <div>
                        <h3 class="font-display font-semibold text-white mb-3">Journée {{ $round }}</h3>
                        <div class="bg-surface border border-border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-border">
                                <tbody class="divide-y divide-border">
                                    @foreach ($roundMatches as $match)
                                        <tr class="hover:bg-white/5">
                                            <td class="px-4 py-3 text-sm text-white w-1/3">
                                                <div class="flex items-center gap-2">
                                                    <x-avatar :path="$match->homeTeam->logo_path" :name="$match->homeTeam->name" size="sm" />
                                                    {{ $match->homeTeam->name }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center font-display font-semibold">
                                                @if ($match->status === 'termine')
                                                    <span class="text-primary">{{ $match->home_score }} - {{ $match->away_score }}</span>
                                                @else
                                                    <span class="text-text-muted">vs</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-white w-1/3">
                                                <div class="flex items-center gap-2">
                                                    <x-avatar :path="$match->awayTeam->logo_path" :name="$match->awayTeam->name" size="sm" />
                                                    {{ $match->awayTeam->name }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-text-muted whitespace-nowrap">
                                                {{ $match->scheduled_at?->format('d/m/Y H:i') ?? 'Non planifié' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm"><x-status-badge :status="$match->status" /></td>
                                            <td class="px-4 py-3 text-sm text-right space-x-3 whitespace-nowrap">
                                                @if ($match->status !== 'termine')
                                                    <a href="{{ route('admin.matches.result.edit', $match) }}" class="text-primary hover:underline">Saisir résultat</a>
                                                @else
                                                    <a href="{{ route('admin.matches.result.edit', $match) }}" class="text-text-muted hover:text-white">Modifier résultat</a>
                                                @endif
                                                <a href="{{ route('admin.matches.lineup.edit', $match) }}" class="text-text-muted hover:text-white">Composition</a>
                                                <a href="{{ route('admin.matches.edit', $match) }}" class="text-text-muted hover:text-white">Modifier</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
