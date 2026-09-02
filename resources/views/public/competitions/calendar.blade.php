<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Calendrier</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            @if ($matches->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Le calendrier n'est pas encore publié.
                </div>
            @else
                <div class="space-y-8">
                    @foreach ($matches as $round => $roundMatches)
                        <div>
                            <h3 class="font-display font-semibold text-white mb-3">Journée {{ $round }}</h3>
                            <div class="bg-surface border border-border rounded-lg overflow-hidden divide-y divide-border">
                                @foreach ($roundMatches as $match)
                                    <div class="px-4 py-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="flex items-center gap-2 text-white w-2/5">
                                                <x-avatar :path="$match->homeTeam->logo_path" :name="$match->homeTeam->name" size="xs" />
                                                {{ $match->homeTeam->name }}
                                            </span>
                                            <span class="font-display font-semibold">
                                                @if ($match->status === 'termine')
                                                    <span class="text-primary">{{ $match->home_score }} - {{ $match->away_score }}</span>
                                                @else
                                                    <span class="text-text-muted">vs</span>
                                                @endif
                                            </span>
                                            <span class="flex items-center justify-end gap-2 text-white w-2/5 text-right">
                                                {{ $match->awayTeam->name }}
                                                <x-avatar :path="$match->awayTeam->logo_path" :name="$match->awayTeam->name" size="xs" />
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center justify-between text-xs text-text-muted">
                                            <span>{{ $match->scheduled_at?->format('d/m/Y H:i') ?? 'Date à confirmer' }} @if($match->venue) · {{ $match->venue }} @endif</span>
                                            <x-status-badge :status="$match->status" />
                                        </div>
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
