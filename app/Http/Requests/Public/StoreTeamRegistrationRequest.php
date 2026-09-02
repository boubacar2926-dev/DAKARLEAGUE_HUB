<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $competition = $this->route('competition');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('teams', 'name')->where('competition_id', $competition->id),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'home_ground' => ['nullable', 'string', 'max:150'],
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
