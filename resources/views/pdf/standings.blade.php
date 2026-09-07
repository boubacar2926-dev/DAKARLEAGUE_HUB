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
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { background: #111; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        tr:nth-child(even) td { background: #fafafa; }
        .center { text-align: center; }
        .points { font-weight: bold; color: #4d8a00; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="brand">DAKARLEAGUE <span class="hub">HUB</span></td>
            <td class="meta">Généré le {{ now()->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>
    <h1>Classement — {{ $competition->name }}</h1>
    <p style="color:#666;margin:0;">Saison {{ $competition->season }} @if($competition->category) · {{ $competition->category }} @endif</p>

    @if ($isKnockout)
        <p style="color:#666;">Compétition à élimination directe : il n'existe pas de classement, consultez l'export du calendrier pour suivre le tableau.</p>
    @elseif ($standingsByGroup)
        @foreach ($standingsByGroup as $groupLabel => $groupStandings)
            <h2 style="font-size:13px;margin:16px 0 0 0;">Poule {{ $groupLabel }}</h2>
            @include('pdf._standings-table', ['standings' => $groupStandings])
        @endforeach
    @else
        @include('pdf._standings-table')
    @endif
</body>
</html>
