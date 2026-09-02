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

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Équipe</th>
                <th class="center">J</th>
                <th class="center">V</th>
                <th class="center">N</th>
                <th class="center">D</th>
                <th class="center">BP</th>
                <th class="center">BC</th>
                <th class="center">Diff</th>
                <th class="center">Pts</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($standings as $row)
                <tr>
                    <td>{{ $row['rank'] }}</td>
                    <td>{{ $row['team']->name }}</td>
                    <td class="center">{{ $row['played'] }}</td>
                    <td class="center">{{ $row['won'] }}</td>
                    <td class="center">{{ $row['drawn'] }}</td>
                    <td class="center">{{ $row['lost'] }}</td>
                    <td class="center">{{ $row['goals_for'] }}</td>
                    <td class="center">{{ $row['goals_against'] }}</td>
                    <td class="center">{{ $row['goal_difference'] >= 0 ? '+' : '' }}{{ $row['goal_difference'] }}</td>
                    <td class="center points">{{ $row['points'] }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Aucun match joué pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
