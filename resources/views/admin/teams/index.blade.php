<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-text-muted"><a href="{{ route('admin.competitions.show', $competition) }}" class="hover:text-primary">&larr; {{ $competition->name }}</a></p>
                <h2 class="font-display font-bold text-xl text-white">Équipes</h2>
            </div>
            <a href="{{ route('admin.competitions.teams.create', $competition) }}" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                + Ajouter une équipe
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @if ($teams->isEmpty())
                    <div class="p-8 text-center text-text-muted">Aucune équipe inscrite pour le moment.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Équipe</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Ville</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Joueurs</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Statut</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($teams as $team)
                                    <tr class="hover:bg-white/5">
                                        <td class="px-4 py-3 text-sm text-white font-medium">
                                            <div class="flex items-center gap-3">
                                                <x-avatar :path="$team->logo_path" :name="$team->name" size="md" />
                                                {{ $team->name }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $team->city ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $team->players_count }}</td>
                                        <td class="px-4 py-3 text-sm"><x-status-badge :status="$team->registration_status" /></td>
                                        <td class="px-4 py-3 text-sm text-right space-x-3 whitespace-nowrap">
                                            @if ($team->isPending())
                                                <form action="{{ route('admin.teams.approve', $team) }}" method="POST" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="text-primary hover:underline">Valider</button>
                                                </form>
                                                <form action="{{ route('admin.teams.reject', $team) }}" method="POST" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="text-danger hover:underline">Refuser</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('admin.teams.players.index', $team) }}" class="text-primary hover:underline">Joueurs</a>
                                            <a href="{{ route('admin.teams.edit', $team) }}" class="text-text-muted hover:text-white">Modifier</a>
                                            <form action="{{ route('admin.teams.destroy', $team) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette équipe ?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
