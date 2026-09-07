<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-text-muted"><a href="{{ route('admin.competitions.teams.index', $team->competition_id) }}" class="hover:text-primary">&larr; {{ $team->competition->name }}</a></p>
                <h2 class="font-display font-bold text-xl text-white">Effectif — {{ $team->name }}</h2>
            </div>
            @can('update', $team)
                <a href="{{ route('admin.teams.players.create', $team) }}" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                    + Ajouter un joueur
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @if ($players->isEmpty())
                    <div class="p-8 text-center text-text-muted">Aucun joueur enregistré.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Poste</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Âge</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($players as $player)
                                    <tr class="hover:bg-white/5">
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $player->jersey_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-white font-medium">
                                            <div class="flex items-center gap-3">
                                                <x-avatar :path="$player->photo_path" :name="$player->fullName()" size="sm" />
                                                {{ $player->fullName() }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $player->positionLabel() ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $player->age() !== null ? $player->age().' ans' : '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-right space-x-3">
                                            @can('update', $player)
                                                <a href="{{ route('admin.players.edit', $player) }}" class="text-text-muted hover:text-white">Modifier</a>
                                                <form action="{{ route('admin.players.destroy', $player) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce joueur ?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                                </form>
                                            @endcan
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
