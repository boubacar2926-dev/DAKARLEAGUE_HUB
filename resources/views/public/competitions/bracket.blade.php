<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Tableau</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            @if ($bracket->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Le tableau n'a pas encore été tiré au sort.
                </div>
            @else
                <div class="space-y-8">
                    @foreach ($bracket as $roundMatches)
                        <div>
                            <h3 class="font-display font-semibold text-white mb-3">
                                {{ \App\Models\Competition::knockoutRoundLabel($roundMatches->count()) }}
                            </h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($roundMatches as $match)
                                    @php $winnerId = $match->winnerTeamId(); @endphp
                                    <div class="bg-surface border border-border rounded-lg p-4">
                                        <div class="flex items-center justify-between gap-2 {{ $winnerId && $winnerId === $match->home_team_id ? 'text-primary font-semibold' : 'text-white' }}">
                                            <span class="flex items-center gap-2">
                                                <x-avatar :path="$match->homeTeam->logo_path" :name="$match->homeTeam->name" size="sm" />
                                                {{ $match->homeTeam->name }}
                                            </span>
                                            <span>{{ ($match->status === 'termine' && ! $match->isBye()) ? $match->home_score : '—' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 mt-2 {{ $winnerId && $winnerId === $match->away_team_id ? 'text-primary font-semibold' : 'text-white' }}">
                                            @if ($match->isBye())
                                                <span class="text-text-muted italic">Exempté</span>
                                            @else
                                                <span class="flex items-center gap-2">
                                                    <x-avatar :path="$match->awayTeam->logo_path" :name="$match->awayTeam->name" size="sm" />
                                                    {{ $match->awayTeam->name }}
                                                </span>
                                                <span>{{ $match->status === 'termine' ? $match->away_score : '—' }}</span>
                                            @endif
                                        </div>
                                        @if (! $match->isBye() && $match->status === 'termine' && $match->home_score === $match->away_score && $match->home_penalties !== null)
                                            <p class="text-xs text-text-muted mt-2 text-center">Tirs au but : {{ $match->home_penalties }} - {{ $match->away_penalties }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
