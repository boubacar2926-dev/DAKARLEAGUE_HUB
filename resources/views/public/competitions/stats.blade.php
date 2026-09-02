<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Statistiques</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                <div class="bg-surface border border-border rounded-lg p-5">
                    <div class="text-xs text-text-muted uppercase tracking-wide mb-1">Meilleure attaque</div>
                    @if ($bestAttack)
                        <div class="font-display font-semibold text-white">{{ $bestAttack['team']->name }}</div>
                        <div class="text-primary text-2xl font-display font-bold">{{ $bestAttack['goals_for'] }} <span class="text-sm text-text-muted font-sans">buts marqués</span></div>
                    @else
                        <p class="text-text-muted text-sm">Pas encore de données.</p>
                    @endif
                </div>
                <div class="bg-surface border border-border rounded-lg p-5">
                    <div class="text-xs text-text-muted uppercase tracking-wide mb-1">Meilleure défense</div>
                    @if ($bestDefense)
                        <div class="font-display font-semibold text-white">{{ $bestDefense['team']->name }}</div>
                        <div class="text-primary text-2xl font-display font-bold">{{ $bestDefense['goals_against'] }} <span class="text-sm text-text-muted font-sans">buts encaissés</span></div>
                    @else
                        <p class="text-text-muted text-sm">Pas encore de données.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-border rounded-lg overflow-hidden">
                    <h3 class="font-display font-semibold text-white p-5 pb-3">Top buteurs</h3>
                    @if ($topScorers->isEmpty())
                        <p class="px-5 pb-5 text-sm text-text-muted">Aucun but inscrit pour le moment.</p>
                    @else
                        <table class="min-w-full divide-y divide-border text-sm">
                            <tbody class="divide-y divide-border">
                                @foreach ($topScorers as $row)
                                    <tr class="hover:bg-white/5">
                                        <td class="px-5 py-2 text-text-muted w-8">{{ $loop->iteration }}</td>
                                        <td class="px-2 py-2 text-white">
                                            <a href="{{ route('public.competitions.player', [$competition, $row['player']]) }}" class="flex items-center gap-2 hover:text-primary">
                                                <x-avatar :path="$row['player']->photo_path" :name="$row['player']->fullName()" size="sm" />
                                                {{ $row['player']->fullName() }}
                                                <span class="text-text-muted text-xs">({{ $row['player']->team->name }})</span>
                                            </a>
                                        </td>
                                        <td class="px-5 py-2 text-right font-display font-bold text-primary">{{ $row['goals'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="bg-surface border border-border rounded-lg overflow-hidden">
                    <h3 class="font-display font-semibold text-white p-5 pb-3">Discipline (cartons)</h3>
                    @if ($topCards->isEmpty())
                        <p class="px-5 pb-5 text-sm text-text-muted">Aucun carton distribué pour le moment.</p>
                    @else
                        <table class="min-w-full divide-y divide-border text-sm">
                            <tbody class="divide-y divide-border">
                                @foreach ($topCards as $row)
                                    <tr class="hover:bg-white/5">
                                        <td class="px-5 py-2 text-white">
                                            <a href="{{ route('public.competitions.player', [$competition, $row['player']]) }}" class="flex items-center gap-2 hover:text-primary">
                                                <x-avatar :path="$row['player']->photo_path" :name="$row['player']->fullName()" size="sm" />
                                                {{ $row['player']->fullName() }}
                                                <span class="text-text-muted text-xs">({{ $row['player']->team->name }})</span>
                                            </a>
                                        </td>
                                        <td class="px-5 py-2 text-right space-x-2">
                                            @if ($row['yellow'])
                                                <span class="text-yellow-400">🟨 {{ $row['yellow'] }}</span>
                                            @endif
                                            @if ($row['red'])
                                                <span class="text-danger">🟥 {{ $row['red'] }}</span>
                                            @endif
                                            @if ($row['expelled'])
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-danger/10 text-danger" title="Expulsion (carton rouge ou 2 jaunes dans un même match)">
                                                    expulsé
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
