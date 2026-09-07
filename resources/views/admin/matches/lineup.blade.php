@php
    $lineupsByPlayer = $match->lineups->keyBy('player_id');
    $statusFor = fn ($playerId) => $lineupsByPlayer[$playerId]->is_starting ? 'titulaire' : 'remplacant';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Composition — {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <p class="text-sm text-text-muted mb-6">
                Répartissez exactement {{ \App\Http\Controllers\Admin\LineupController::REQUIRED_STARTERS }} titulaires parmi les joueurs convoqués ; les autres seront remplaçants.
                <a href="{{ route('admin.matches.convocation.edit', $match) }}" class="text-primary hover:underline">Modifier la convocation</a>
            </p>

            <form method="POST" action="{{ route('admin.matches.lineup.update', $match) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input-error :messages="$errors->get('home')" />
                <x-input-error :messages="$errors->get('away')" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach ([['home', $match->homeTeam], ['away', $match->awayTeam]] as [$side, $team])
                        @php $convokedPlayers = $match->lineups->where('team_id', $team->id)->map->player->sortBy([fn ($p) => $p->positionOrder(), fn ($p) => $p->jersey_number ?? 99]); @endphp
                        <div class="bg-surface border border-border rounded-lg p-5">
                            <h3 class="font-display font-semibold text-white mb-4">{{ $team->name }}</h3>

                            <div class="space-y-2">
                                @foreach ($convokedPlayers as $player)
                                    @php $suspendedReason = $suspensions->get($player->id); @endphp
                                    <div class="flex items-center justify-between text-sm border-b border-border pb-2 last:border-0">
                                        <span class="text-white">
                                            <span class="text-text-muted">{{ $player->jersey_number ?? '—' }}</span>
                                            @if ($player->positionLabel())
                                                <span class="text-[10px] text-text-muted uppercase border border-border rounded px-1 py-0.5">{{ $player->positionLabel() }}</span>
                                            @endif
                                            {{ $player->fullName() }}
                                            @if ($suspendedReason)
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-danger/10 text-danger" title="Suspendu : {{ $suspendedReason }}">
                                                    🚫 suspendu
                                                </span>
                                            @endif
                                        </span>
                                        @if ($suspendedReason)
                                            <span class="text-xs text-danger italic">{{ ucfirst($suspendedReason) }}</span>
                                            <input type="hidden" name="{{ $side }}[{{ $player->id }}]" value="remplacant">
                                        @else
                                            <select name="{{ $side }}[{{ $player->id }}]" class="bg-background border-border text-white text-xs rounded-md focus:border-primary focus:ring-primary">
                                                <option value="remplacant" @selected($statusFor($player->id) === 'remplacant')>Remplaçant</option>
                                                <option value="titulaire" @selected($statusFor($player->id) === 'titulaire')>Titulaire</option>
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.competitions.matches.index', $match->competition_id) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                    <x-primary-button>Publier la composition</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
