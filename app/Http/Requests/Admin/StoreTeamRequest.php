<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    /**
     * Doit s'exécuter AVANT les règles de validation (qui interrogent déjà la compétition/
     * l'équipe ciblée) pour éviter qu'un utilisateur non autorisé sur cette compétition ne
     * puisse sonder son contenu via les messages d'erreur de validation (ex. unicité du nom).
     */
    public function authorize(): bool
    {
        $team = $this->route('team');

        if ($team) {
            return (bool) $this->user()?->can('update', $team);
        }

        $competition = $this->route('competition');

        return $competition
            && $this->user()
            && ($this->user()->isSuperAdmin() || $competition->isOrganizedBy($this->user()));
    }

    public function rules(): array
    {
        $team = $this->route('team');
        $competition = $this->route('competition') ?? $team?->competition;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('teams', 'name')
                    ->where('competition_id', $competition?->id)
                    ->ignore($team?->id),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
            'city' => ['nullable', 'string', 'max:100'],
            'home_ground' => ['nullable', 'string', 'max:150'],
            'manager_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Le nom de l'équipe est obligatoire.",
            'name.unique' => "Une équipe porte déjà ce nom dans cette compétition.",
        ];
    }
}
