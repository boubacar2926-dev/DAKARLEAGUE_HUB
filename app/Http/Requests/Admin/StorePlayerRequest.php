<?php

namespace App\Http\Requests\Admin;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    /**
     * Doit s'exécuter AVANT les règles de validation (qui interrogent déjà l'équipe/le joueur
     * ciblé) pour éviter qu'un utilisateur non autorisé ne sonde son contenu via les messages
     * d'erreur de validation (ex. numéro de maillot déjà pris).
     */
    public function authorize(): bool
    {
        $player = $this->route('player');

        if ($player) {
            return (bool) $this->user()?->can('update', $player);
        }

        $team = $this->route('team');

        return $team && (bool) $this->user()?->can('update', $team);
    }

    public function rules(): array
    {
        $player = $this->route('player');
        $team = $this->route('team') ?? $player?->team;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'position' => ['nullable', Rule::in([
                Player::POSITION_GOALKEEPER,
                Player::POSITION_DEFENDER,
                Player::POSITION_MIDFIELDER,
                Player::POSITION_FORWARD,
            ])],
            'jersey_number' => [
                'nullable', 'integer', 'min:1', 'max:99',
                Rule::unique('players', 'jersey_number')
                    ->where('team_id', $team?->id)
                    ->ignore($player?->id),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'license_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => "Le prénom est obligatoire.",
            'last_name.required' => "Le nom est obligatoire.",
            'jersey_number.unique' => "Ce numéro de maillot est déjà attribué dans cette équipe.",
            'birth_date.before' => "La date de naissance doit être antérieure à aujourd'hui.",
        ];
    }
}
