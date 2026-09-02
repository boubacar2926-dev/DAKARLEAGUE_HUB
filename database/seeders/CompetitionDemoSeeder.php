<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompetitionDemoSeeder extends Seeder
{
    private const TEAM_NAMES = [
        'AS Médina',
        'Jaraaf de Dakar',
        'ASC Niarry Tally',
        'Étoile de Pikine',
        'US Gorée',
        'FC Parcelles',
    ];

    /**
     * Peuple la base avec une compétition de démonstration complète :
     * organisateurs, équipes, joueurs, calendrier et résultats cohérents.
     */
    public function run(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Administrateur',
            'email' => 'admin@dakarleague.sn',
        ]);

        $organisateur = User::factory()->organisateur()->create([
            'name' => 'Organisateur DakarLeague',
            'email' => 'organisateur@dakarleague.sn',
        ]);

        $competition = Competition::factory()->create([
            'name' => 'Championnat Ligue Amateur de Dakar',
            'season' => '2026',
            'category' => 'Senior',
            'format' => Competition::FORMAT_SINGLE_ROUND,
            'status' => Competition::STATUS_IN_PROGRESS,
            'created_by' => $organisateur->id,
        ]);

        $competition->organizers()->attach([$superAdmin->id, $organisateur->id]);

        $teams = collect(self::TEAM_NAMES)->map(function (string $name) use ($competition) {
            $manager = User::factory()->responsable()->create();

            $team = Team::factory()->create([
                'competition_id' => $competition->id,
                'manager_user_id' => $manager->id,
                'name' => $name,
                'manager_name' => $manager->name,
            ]);

            $this->seedPlayers($team, $competition);

            return $team;
        });

        $this->seedRoundRobinMatches($competition, $teams);

        $this->command?->info('Compétition de démonstration créée : '.$competition->name);
    }

    private function seedPlayers(Team $team, Competition $competition): void
    {
        $positions = [
            Player::POSITION_GOALKEEPER,
            Player::POSITION_DEFENDER, Player::POSITION_DEFENDER, Player::POSITION_DEFENDER, Player::POSITION_DEFENDER,
            Player::POSITION_MIDFIELDER, Player::POSITION_MIDFIELDER, Player::POSITION_MIDFIELDER,
            Player::POSITION_FORWARD, Player::POSITION_FORWARD, Player::POSITION_FORWARD,
        ];

        foreach ($positions as $index => $position) {
            Player::factory()->create([
                'team_id' => $team->id,
                'competition_id' => $competition->id,
                'position' => $position,
                'jersey_number' => $index + 1,
            ]);
        }
    }

    private function seedRoundRobinMatches(Competition $competition, $teams): void
    {
        $teamIds = $teams->pluck('id')->all();

        if (count($teamIds) % 2 !== 0) {
            $teamIds[] = null; // "bye" fictif si nombre impair d'équipes
        }

        $rounds = count($teamIds) - 1;
        $half = count($teamIds) / 2;
        $schedule = [];

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $teamIds[$i];
                $away = $teamIds[count($teamIds) - 1 - $i];

                if ($home !== null && $away !== null) {
                    $schedule[] = ['round' => $round + 1, 'home' => $home, 'away' => $away];
                }
            }

            // Rotation façon "round-robin" (le premier reste fixe).
            array_splice($teamIds, 1, 0, array_splice($teamIds, -1, 1));
        }

        $playersByTeam = Player::whereIn('team_id', $teams->pluck('id'))
            ->get()
            ->groupBy('team_id');

        foreach ($schedule as $fixture) {
            $isPlayed = $fixture['round'] <= 3;

            $match = GameMatch::factory()->create([
                'competition_id' => $competition->id,
                'home_team_id' => $fixture['home'],
                'away_team_id' => $fixture['away'],
                'round' => $fixture['round'],
                'status' => $isPlayed ? GameMatch::STATUS_FINISHED : GameMatch::STATUS_SCHEDULED,
                'home_score' => $isPlayed ? rand(0, 4) : null,
                'away_score' => $isPlayed ? rand(0, 3) : null,
                'validated_at' => $isPlayed ? now() : null,
            ]);

            if ($isPlayed) {
                $this->seedMatchEvents($match, $playersByTeam);
            }
        }
    }

    private function seedMatchEvents(GameMatch $match, $playersByTeam): void
    {
        $this->seedTeamGoals($match, $match->home_team_id, $match->home_score, $playersByTeam);
        $this->seedTeamGoals($match, $match->away_team_id, $match->away_score, $playersByTeam);

        // Quelques cartons pour la démonstration, sans impact sur le score.
        foreach ([$match->home_team_id, $match->away_team_id] as $teamId) {
            if (rand(0, 1) === 1) {
                $player = $playersByTeam->get($teamId)?->random();

                MatchEvent::create([
                    'match_id' => $match->id,
                    'team_id' => $teamId,
                    'player_id' => $player?->id,
                    'type' => MatchEvent::TYPE_YELLOW_CARD,
                    'minute' => rand(1, 90),
                ]);
            }
        }
    }

    private function seedTeamGoals(GameMatch $match, int $teamId, int $goalCount, $playersByTeam): void
    {
        $players = $playersByTeam->get($teamId);

        for ($i = 0; $i < $goalCount; $i++) {
            MatchEvent::create([
                'match_id' => $match->id,
                'team_id' => $teamId,
                'player_id' => $players?->random()?->id,
                'type' => MatchEvent::TYPE_GOAL,
                'minute' => rand(1, 90),
            ]);
        }
    }
}
