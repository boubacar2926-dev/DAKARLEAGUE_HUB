@php
    $actionLabels = [
        'competition.created' => ['Compétition créée', 'text-primary'],
        'competition.updated' => ['Compétition modifiée', 'text-blue-400'],
        'competition.deleted' => ['Compétition supprimée', 'text-danger'],
        'team.created' => ['Équipe ajoutée', 'text-primary'],
        'team.updated' => ['Équipe modifiée', 'text-blue-400'],
        'team.deleted' => ['Équipe supprimée', 'text-danger'],
        'player.created' => ['Joueur ajouté', 'text-primary'],
        'player.updated' => ['Joueur modifié', 'text-blue-400'],
        'player.deleted' => ['Joueur supprimé', 'text-danger'],
        'match.created' => ['Match ajouté', 'text-primary'],
        'match.updated' => ['Match modifié (report/annulation)', 'text-yellow-400'],
        'match.deleted' => ['Match supprimé', 'text-danger'],
        'match.result_validated' => ['Résultat validé', 'text-primary'],
        'calendar.generated' => ['Calendrier généré', 'text-primary'],
        'document.exported' => ['Document exporté (PDF)', 'text-blue-400'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Journal d'activité</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <form method="GET" class="flex items-center gap-3">
                <select name="competition_id" onchange="this.form.submit()" class="bg-surface border-border text-white text-sm rounded-md focus:border-primary focus:ring-primary">
                    <option value="">Toutes les compétitions</option>
                    @foreach ($competitions as $competition)
                        <option value="{{ $competition->id }}" @selected(request('competition_id') == $competition->id)>{{ $competition->name }}</option>
                    @endforeach
                </select>
                @if (request('competition_id'))
                    <a href="{{ route('admin.activity-logs.index') }}" class="text-xs text-text-muted hover:text-white">Réinitialiser</a>
                @endif
            </form>

            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @if ($logs->isEmpty())
                    <div class="p-8 text-center text-text-muted">Aucune action enregistrée.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-text-muted uppercase text-xs">
                                    <th class="px-4 py-3 text-left">Date</th>
                                    <th class="px-4 py-3 text-left">Utilisateur</th>
                                    <th class="px-4 py-3 text-left">Action</th>
                                    <th class="px-4 py-3 text-left">Détail</th>
                                    <th class="px-4 py-3 text-left">Compétition</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($logs as $log)
                                    @php $meta = $actionLabels[$log->action] ?? [$log->action, 'text-text-muted']; @endphp
                                    <tr class="hover:bg-white/5">
                                        <td class="px-4 py-3 text-text-muted whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-white">{{ $log->user?->name ?? 'Système' }}</td>
                                        <td class="px-4 py-3 {{ $meta[1] }} font-medium whitespace-nowrap">{{ $meta[0] }}</td>
                                        <td class="px-4 py-3 text-text-muted">{{ $log->description }}</td>
                                        <td class="px-4 py-3 text-text-muted">{{ $log->competition?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
