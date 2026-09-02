<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-avatar :path="$team->logo_path" :name="$team->name" size="xl" />
            <div>
                <p class="text-xs text-text-muted"><a href="{{ route('public.competitions.standings', $competition) }}" class="hover:text-primary">&larr; {{ $competition->name }}</a></p>
                <h2 class="font-display font-bold text-xl text-white">{{ $team->name }}</h2>
                <p class="text-sm text-text-muted mt-1">{{ $team->city }} @if($team->home_ground) · {{ $team->home_ground }} @endif</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @if ($team->players->isEmpty())
                    <div class="p-8 text-center text-text-muted">Effectif non publié.</div>
                @else
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead>
                            <tr class="text-text-muted uppercase text-xs">
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Joueur</th>
                                <th class="px-4 py-3 text-left">Poste</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($team->players as $player)
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-3 text-text-muted">{{ $player->jersey_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-white font-medium">
                                        <a href="{{ route('public.competitions.player', [$competition, $player]) }}" class="flex items-center gap-3 hover:text-primary">
                                            <x-avatar :path="$player->photo_path" :name="$player->fullName()" size="sm" />
                                            {{ $player->fullName() }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-text-muted capitalize">{{ $player->position ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
