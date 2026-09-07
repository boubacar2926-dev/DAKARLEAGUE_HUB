<?php

namespace App\Http\Requests\Admin;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        if ($competition) {
            return (bool) $this->user()?->can('update', $competition);
        }

        return (bool) $this->user()?->can('create', Competition::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'season' => ['required', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'format' => ['required', Rule::in([
                Competition::FORMAT_SINGLE_ROUND,
                Competition::FORMAT_DOUBLE_ROUND,
                Competition::FORMAT_GROUPS,
                Competition::FORMAT_KNOCKOUT,
            ])],
            // Nombre de poules : uniquement pertinent (et requis) pour le format "poules".
            'number_of_groups' => ['required_if:format,'.Competition::FORMAT_GROUPS, 'nullable', 'integer', 'min:2', 'max:8'],
            'status' => ['required', Rule::in([
                Competition::STATUS_DRAFT,
                Competition::STATUS_REGISTRATION_OPEN,
                Competition::STATUS_IN_PROGRESS,
                Competition::STATUS_FINISHED,
                Competition::STATUS_ARCHIVED,
            ])],
            'points_win' => ['required', 'integer', 'min:0', 'max:10'],
            'points_draw' => ['required', 'integer', 'min:0', 'max:10'],
            'points_loss' => ['required', 'integer', 'min:0', 'max:10'],
            'yellow_card_suspension_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'red_card_suspension_matches' => ['required', 'integer', 'min:1', 'max:10'],
            'allow_team_managers_to_manage_players' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Le nom de la compétition est obligatoire.",
            'season.required' => "La saison est obligatoire.",
            'format.required' => "Le format est obligatoire.",
            'end_date.after_or_equal' => "La date de fin doit être postérieure ou égale à la date de début.",
            'number_of_groups.required_if' => "Indiquez le nombre de poules souhaité.",
            'number_of_groups.min' => "Il faut au moins 2 poules.",
        ];
    }
}
