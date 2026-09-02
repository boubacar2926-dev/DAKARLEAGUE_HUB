<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display font-bold text-xl text-white">Compétitions</h2>
            @can('create', \App\Models\Competition::class)
                <a href="{{ route('admin.competitions.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-black text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-primary-dark">
                    + Nouvelle compétition
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @if ($competitions->isEmpty())
                    <div class="p-8 text-center text-text-muted">
                        Aucune compétition pour le moment.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Saison</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Statut</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Équipes</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($competitions as $competition)
                                    <tr class="hover:bg-white/5">
                                        <td class="px-4 py-3 text-sm text-white font-medium">
                                            <div class="flex items-center gap-3">
                                                <x-avatar :path="$competition->logo_path" :name="$competition->name" size="sm" />
                                                {{ $competition->name }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $competition->season }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <x-status-badge :status="$competition->status" />
                                        </td>
                                        <td class="px-4 py-3 text-sm text-text-muted">{{ $competition->teams_count }}</td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <a href="{{ route('admin.competitions.show', $competition) }}" class="text-primary hover:underline">Gérer</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">
                        {{ $competitions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
