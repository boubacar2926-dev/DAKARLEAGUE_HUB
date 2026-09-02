<?php

namespace App\Http\Requests\Admin;

use App\Models\MatchEvent;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMatchResultRequest extends FormRequest
{
    /**
     * Doit s'exécuter AVANT les règles de validation (qui interrogent déjà le match/les joueurs
     * de ses deux équipes) pour éviter qu'un utilisateur non autorisé sur cette compétition ne
     * sonde ses effectifs via les messages d'erreur de validation.
     */
    public function authorize(): bool
    {
        $match = $this->route('match');

        return $match && (bool) $this->user()?->can('validateResult', $match);
    }

    public function rules(): array
    {
        $match = $this->route('match');

        return [
            'home_score' => ['required', 'integer', 'min:0', 'max:50'],
            'away_score' => ['required', 'integer', 'min:0', 'max:50'],
            'events' => ['array'],
            // Un évènement ne peut concerner qu'une des deux équipes qui disputent CE match
            // (empêche l'injection d'un but/carton pour une équipe d'une autre compétition).
            'events.*.team_id' => ['required', 'integer', Rule::in([$match->home_team_id, $match->away_team_id])],
            'events.*.player_id' => ['nullable', 'integer'],
            'events.*.type' => ['required', Rule::in([
                MatchEvent::TYPE_GOAL,
                MatchEvent::TYPE_PENALTY_GOAL,
                MatchEvent::TYPE_OWN_GOAL,
                MatchEvent::TYPE_YELLOW_CARD,
                MatchEvent::TYPE_RED_CARD,
            ])],
            'events.*.minute' => ['nullable', 'integer', 'min:0', 'max:130'],
        ];
    }

    /**
     * RG05 : refuse la validation lorsque les buts saisis ne correspondent pas au score final.
     * Le but contre son camp inscrit au score de l'ADVERSAIRE de l'équipe indiquée dans l'évènement.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $match = $this->route('match');
            $events = collect($this->input('events', []));

            $this->assertPlayersBelongToDeclaredTeam($validator, $match, $events);
            $this->assertYellowCardCapRespected($validator, $events);

            $homeGoals = $events->filter(fn ($e) => in_array($e['type'], [MatchEvent::TYPE_GOAL, MatchEvent::TYPE_PENALTY_GOAL])
                    && (int) $e['team_id'] === $match->home_team_id)
                ->count()
                + $events->filter(fn ($e) => $e['type'] === MatchEvent::TYPE_OWN_GOAL
                    && (int) $e['team_id'] === $match->away_team_id)
                    ->count();

            $awayGoals = $events->filter(fn ($e) => in_array($e['type'], [MatchEvent::TYPE_GOAL, MatchEvent::TYPE_PENALTY_GOAL])
                    && (int) $e['team_id'] === $match->away_team_id)
                ->count()
                + $events->filter(fn ($e) => $e['type'] === MatchEvent::TYPE_OWN_GOAL
                    && (int) $e['team_id'] === $match->home_team_id)
                    ->count();

            if ((int) $this->input('home_score') !== $homeGoals) {
                $validator->errors()->add('home_score', "Le nombre de buts saisis pour l'équipe à domicile ({$homeGoals}) ne correspond pas au score indiqué.");
            }

            if ((int) $this->input('away_score') !== $awayGoals) {
                $validator->errors()->add('away_score', "Le nombre de buts saisis pour l'équipe à l'extérieur ({$awayGoals}) ne correspond pas au score indiqué.");
            }
        });
    }

    /**
     * Empêche d'associer un évènement à un joueur qui n'appartient pas à l'équipe déclarée
     * sur cet évènement (sinon un but/carton pourrait être crédité à un joueur d'une autre équipe).
     */
    private function assertPlayersBelongToDeclaredTeam(Validator $validator, $match, $events): void
    {
        $playerIds = $events->pluck('player_id')->filter()->unique();

        if ($playerIds->isEmpty()) {
            return;
        }

        $validTeamIds = Player::whereIn('id', $playerIds)->pluck('team_id', 'id');

        foreach ($events as $index => $event) {
            $playerId = $event['player_id'] ?? null;

            if (! $playerId) {
                continue;
            }

            $playerTeamId = $validTeamIds->get((int) $playerId);

            if ($playerTeamId === null || (int) $playerTeamId !== (int) $event['team_id']) {
                $validator->errors()->add("events.{$index}.player_id", "Ce joueur n'appartient pas à l'équipe indiquée pour cet évènement.");
            }
        }
    }

    /**
     * Règlement du football : un joueur ayant reçu 2 cartons jaunes dans le même match est
     * expulsé — un 3e jaune pour le même joueur dans ce match n'a pas de sens et est refusé.
     */
    private function assertYellowCardCapRespected(Validator $validator, $events): void
    {
        $yellowsByPlayer = $events
            ->filter(fn ($e) => ($e['type'] ?? null) === MatchEvent::TYPE_YELLOW_CARD && ! empty($e['player_id']))
            ->groupBy('player_id');

        foreach ($yellowsByPlayer as $playerId => $playerYellows) {
            if ($playerYellows->count() > 2) {
                $player = Player::find($playerId);
                $validator->errors()->add('events', "Un joueur ne peut recevoir plus de 2 cartons jaunes dans le même match".($player ? " ({$player->fullName()})" : '').".");
            }
        }
    }
}
