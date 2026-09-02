<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1b1b18; font-size: 12px; }
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 3px solid #7ACC00; margin-bottom: 20px; }
        .header-table td { padding: 0 0 10px 0; border: none; }
        .brand { font-size: 18px; font-weight: bold; text-align: left; }
        .brand .hub { color: #7ACC00; }
        .meta { color: #555; font-size: 11px; text-align: right; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        h2 { font-size: 12px; margin: 16px 0 6px 0; color: #333; background: #f0f0f0; padding: 4px 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        td.teams { width: 70%; }
        td.score { width: 15%; text-align: center; font-weight: bold; color: #4d8a00; }
        td.date { width: 15%; text-align: right; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="brand">DAKARLEAGUE <span class="hub">HUB</span></td>
            <td class="meta">Généré le {{ now()->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>
    <h1>Calendrier — {{ $competition->name }}</h1>
    <p style="color:#666;margin:0;">Saison {{ $competition->season }} @if($competition->category) · {{ $competition->category }} @endif</p>

    @forelse ($matches as $round => $roundMatches)
        <h2>Journée {{ $round }}</h2>
        <table>
            @foreach ($roundMatches as $match)
                <tr>
                    <td class="teams">{{ $match->homeTeam->name }} — {{ $match->awayTeam->name }}</td>
                    <td class="score">
                        @if ($match->status === 'termine')
                            {{ $match->home_score }} - {{ $match->away_score }}
                        @else
                            vs
                        @endif
                    </td>
                    <td class="date">{{ $match->scheduled_at?->format('d/m/Y H:i') ?? 'À confirmer' }}</td>
                </tr>
            @endforeach
        </table>
    @empty
        <p>Aucun match programmé.</p>
    @endforelse
</body>
</html>
