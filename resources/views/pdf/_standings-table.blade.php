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
