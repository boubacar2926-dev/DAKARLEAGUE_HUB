<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Convocation — {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8" x-data="{
            homeCount: {{ collect($convokedIds)->intersect($match->homeTeam->players->pluck('id'))->count() }},
            awayCount: {{ collect($convokedIds)->intersect($match->awayTeam->players->pluck('id'))->count() }},
        }">
            <p class="text-sm text-text-muted mb-6">
                Sélectionnez au moins {{ \App\Http\Controllers\Admin\ConvocationController::MIN_CONVOKED }} joueurs par équipe. La composition (titulaires/remplaçants) se fera à l'étape suivante, uniquement parmi les joueurs convoqués ici.
            </p>

            <form method="POST" action="{{ route('admin.matches.convocation.update', $match) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input-error :messages="$errors->get('home')" />
                <x-input-error :messages="$errors->get('away')" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach ([['home', $match->homeTeam], ['away', $match->awayTeam]] as [$side, $team])
                        <div class="bg-surface border border-border rounded-lg p-5">
                            <h3 class="font-display font-semibold text-white mb-1">{{ $team->name }}</h3>
                            <p class="text-xs mb-4" :class="{{ $side }}Count >= {{ \App\Http\Controllers\Admin\ConvocationController::MIN_CONVOKED }} ? 'text-primary' : 'text-danger'">
                                <span x-text="{{ $side }}Count"></span> / {{ \App\Http\Controllers\Admin\ConvocationController::MIN_CONVOKED }} minimum
                            </p>

                            @if ($team->players->isEmpty())
                                <p class="text-sm text-text-muted">Aucun joueur dans l'effectif.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($team->players->sortBy([fn ($p) => $p->positionOrder(), fn ($p) => $p->jersey_number ?? 99]) as $player)
                                        @php $suspendedReason = $suspensions->get($player->id); @endphp
                                        <label class="flex items-center justify-between text-sm border-b border-border pb-2 last:border-0 {{ $suspendedReason ? 'opacity-60' : 'cursor-pointer' }}">
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
                                            @else
                                                <input type="checkbox" name="{{ $side }}[]" value="{{ $player->id }}"
                                                    @checked(in_array($player->id, $convokedIds, true))
                                                    x-on:change="{{ $side }}Count += $event.target.checked ? 1 : -1"
                                                    class="rounded border-border bg-background text-primary focus:ring-primary">
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.competitions.matches.index', $match->competition_id) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                    <x-primary-button>Publier la convocation</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
