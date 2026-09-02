<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Classement</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                @if ($standings->isEmpty())
                    <div class="p-8 text-center text-text-muted">Le classement sera disponible dès le premier match joué.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead>
                                <tr class="text-text-muted uppercase text-xs">
                                    <th class="px-3 py-3 text-left">#</th>
                                    <th class="px-3 py-3 text-left">Équipe</th>
                                    <th class="px-3 py-3 text-center">J</th>
                                    <th class="px-3 py-3 text-center">V</th>
                                    <th class="px-3 py-3 text-center">N</th>
                                    <th class="px-3 py-3 text-center">D</th>
                                    <th class="px-3 py-3 text-center">BP</th>
                                    <th class="px-3 py-3 text-center">BC</th>
                                    <th class="px-3 py-3 text-center">Diff</th>
                                    <th class="px-3 py-3 text-center font-bold">Pts</th>
                                    <th class="px-3 py-3 text-center">Forme</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($standings as $row)
                                    <tr class="hover:bg-white/5">
                                        <td class="px-3 py-3 text-text-muted">{{ $row['rank'] }}</td>
                                        <td class="px-3 py-3 text-white font-medium">
                                            <a href="{{ route('public.competitions.team', [$competition, $row['team']]) }}" class="flex items-center gap-2 hover:text-primary">
                                                <x-avatar :path="$row['team']->logo_path" :name="$row['team']->name" size="sm" />
                                                {{ $row['team']->name }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['played'] }}</td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['won'] }}</td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['drawn'] }}</td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['lost'] }}</td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['goals_for'] }}</td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['goals_against'] }}</td>
                                        <td class="px-3 py-3 text-center text-text-muted">{{ $row['goal_difference'] >= 0 ? '+' : '' }}{{ $row['goal_difference'] }}</td>
                                        <td class="px-3 py-3 text-center font-display font-bold text-primary">{{ $row['points'] }}</td>
                                        <td class="px-3 py-3 text-center">
                                            @foreach ($row['form'] as $result)
                                                <span class="inline-block w-5 h-5 leading-5 text-[10px] rounded-full mr-0.5
                                                    {{ $result === 'V' ? 'bg-primary/20 text-primary' : ($result === 'N' ? 'bg-white/10 text-text-muted' : 'bg-danger/20 text-danger') }}">
                                                    {{ $result }}
                                                </span>
                                            @endforeach
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
