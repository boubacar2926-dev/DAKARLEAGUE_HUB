<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Résultat — {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg p-6"
                x-data="matchResultForm({
                    homeTeamId: {{ $match->home_team_id }},
                    awayTeamId: {{ $match->away_team_id }},
                    homeTeamName: @js($match->homeTeam->name),
                    awayTeamName: @js($match->awayTeam->name),
                    homePlayers: @js($homePlayers->map->only(['id', 'first_name', 'last_name'])),
                    awayPlayers: @js($awayPlayers->map->only(['id', 'first_name', 'last_name'])),
                    events: @js($match->events->map->only(['team_id', 'player_id', 'type', 'minute'])),
                    isKnockout: {{ $match->competition->isKnockoutFormat() ? 'true' : 'false' }},
                })">
                <form method="POST" action="{{ route('admin.matches.result.update', $match) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-6 items-end">
                        <div>
                            <x-input-label value="Score — {{ $match->homeTeam->name }}" />
                            <x-text-input name="home_score" type="number" min="0" x-model.number="homeScoreDisplay" class="mt-1 block w-full text-center text-2xl font-display font-bold" value="{{ old('home_score', $match->home_score) }}" required />
                            <p class="text-xs text-text-muted mt-1">Buts saisis : <span x-text="homeGoalsFromEvents"></span></p>
                        </div>
                        <div>
                            <x-input-label value="Score — {{ $match->awayTeam->name }}" />
                            <x-text-input name="away_score" type="number" min="0" x-model.number="awayScoreDisplay" class="mt-1 block w-full text-center text-2xl font-display font-bold" value="{{ old('away_score', $match->away_score) }}" required />
                            <p class="text-xs text-text-muted mt-1">Buts saisis : <span x-text="awayGoalsFromEvents"></span></p>
                        </div>
                    </div>

                    <x-input-error :messages="$errors->get('home_score')" />
                    <x-input-error :messages="$errors->get('away_score')" />

                    <div x-show="isKnockout && homeScoreDisplay === awayScoreDisplay" x-cloak class="bg-background border border-border rounded-lg p-4">
                        <p class="text-sm text-white mb-3">Score nul en élimination directe : séance de tirs au but pour désigner le vainqueur.</p>
                        <div class="grid grid-cols-2 gap-6 items-end">
                            <div>
                                <x-input-label value="Tirs au but — {{ $match->homeTeam->name }}" />
                                <x-text-input name="home_penalties" type="number" min="0" class="mt-1 block w-full text-center text-xl font-display font-bold" value="{{ old('home_penalties', $match->home_penalties) }}" />
                            </div>
                            <div>
                                <x-input-label value="Tirs au but — {{ $match->awayTeam->name }}" />
                                <x-text-input name="away_penalties" type="number" min="0" class="mt-1 block w-full text-center text-xl font-display font-bold" value="{{ old('away_penalties', $match->away_penalties) }}" />
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('home_penalties')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-display font-semibold text-white">Buts et cartons</h3>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <button type="button" @click="addEvent('but')" class="text-xs px-3 py-1.5 border border-border rounded-md text-white hover:border-primary">⚽ + But</button>
                            <button type="button" @click="addEvent('but_penalty')" class="text-xs px-3 py-1.5 border border-border rounded-md text-white hover:border-primary">⚽ + But sur penalty</button>
                            <button type="button" @click="addEvent('but_contre_son_camp')" class="text-xs px-3 py-1.5 border border-border rounded-md text-white hover:border-primary">⚽ + Contre son camp</button>
                            <button type="button" @click="addEvent('carton_jaune')" class="text-xs px-3 py-1.5 border border-yellow-500/40 rounded-md text-yellow-400 hover:bg-yellow-500/10">🟨 + Carton jaune</button>
                            <button type="button" @click="addEvent('carton_rouge')" class="text-xs px-3 py-1.5 border border-danger/40 rounded-md text-danger hover:bg-danger/10">🟥 + Carton rouge</button>
                        </div>

                        <div class="space-y-3">
                            <div x-show="events.length > 0" class="hidden sm:grid grid-cols-12 gap-2 px-3 text-xs text-text-muted uppercase tracking-wide">
                                <div class="col-span-3">Équipe</div>
                                <div class="col-span-4">Joueur</div>
                                <div class="col-span-3">Type d'évènement</div>
                                <div class="col-span-1">Minute</div>
                            </div>

                            <template x-for="(event, index) in events" :key="index">
                                <div class="grid grid-cols-12 gap-2 items-center bg-background border rounded-md p-3"
                                    :class="{
                                        'border-yellow-500/40': event.type === 'carton_jaune',
                                        'border-danger/40': event.type === 'carton_rouge',
                                        'border-border': !['carton_jaune', 'carton_rouge'].includes(event.type),
                                    }">
                                    <select :name="'events['+index+'][team_id]'" x-model.number="event.team_id" @change="event.player_id = ''" class="col-span-3 bg-surface border-border text-white text-sm rounded-md focus:border-primary focus:ring-primary">
                                        <option :value="homeTeamId" x-text="homeTeamName"></option>
                                        <option :value="awayTeamId" x-text="awayTeamName"></option>
                                    </select>

                                    <select :name="'events['+index+'][player_id]'" x-model.number="event.player_id" class="col-span-4 bg-surface border-border text-white text-sm rounded-md focus:border-primary focus:ring-primary">
                                        <option value="">Joueur (optionnel)</option>
                                        <template x-for="player in (event.team_id == homeTeamId ? homePlayers : awayPlayers)" :key="player.id">
                                            <option :value="player.id" x-text="player.first_name + ' ' + player.last_name"></option>
                                        </template>
                                    </select>

                                    <select :name="'events['+index+'][type]'" x-model="event.type" class="col-span-3 bg-surface border-border text-white text-sm rounded-md focus:border-primary focus:ring-primary">
                                        <option value="but">⚽ But</option>
                                        <option value="but_penalty">⚽ But sur penalty</option>
                                        <option value="but_contre_son_camp">⚽ Contre son camp</option>
                                        <option value="carton_jaune">🟨 Carton jaune</option>
                                        <option value="carton_rouge">🟥 Carton rouge</option>
                                    </select>

                                    <input :name="'events['+index+'][minute]'" x-model.number="event.minute" type="number" min="1" max="90" placeholder="Min." required class="col-span-1 bg-surface border-border text-white text-sm rounded-md focus:border-primary focus:ring-primary">

                                    <button type="button" @click="removeEvent(index)" class="col-span-1 text-danger hover:underline text-sm text-center">✕</button>
                                </div>
                            </template>

                            <p x-show="events.length === 0" class="text-sm text-text-muted">Aucun évènement ajouté — utilisez les boutons ci-dessus pour ajouter un but ou un carton.</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.competitions.matches.index', $match->competition_id) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                        <x-primary-button>Valider le résultat</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush

    <script>
        function matchResultForm(config) {
            return {
                homeTeamId: config.homeTeamId,
                awayTeamId: config.awayTeamId,
                homeTeamName: config.homeTeamName,
                awayTeamName: config.awayTeamName,
                homePlayers: config.homePlayers,
                awayPlayers: config.awayPlayers,
                isKnockout: config.isKnockout,
                events: config.events.length ? config.events : [],
                homeScoreDisplay: @js((int) old('home_score', $match->home_score ?? 0)),
                awayScoreDisplay: @js((int) old('away_score', $match->away_score ?? 0)),
                get homeGoalsFromEvents() {
                    return this.events.filter(e => ['but', 'but_penalty'].includes(e.type) && e.team_id == this.homeTeamId).length
                        + this.events.filter(e => e.type === 'but_contre_son_camp' && e.team_id == this.awayTeamId).length;
                },
                get awayGoalsFromEvents() {
                    return this.events.filter(e => ['but', 'but_penalty'].includes(e.type) && e.team_id == this.awayTeamId).length
                        + this.events.filter(e => e.type === 'but_contre_son_camp' && e.team_id == this.homeTeamId).length;
                },
                addEvent(type = 'but') {
                    this.events.push({ team_id: this.homeTeamId, player_id: '', type: type, minute: null });
                },
                removeEvent(index) {
                    this.events.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
