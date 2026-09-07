@php
    $old = fn ($field, $default = null) => old($field, $competition?->{$field} ?? $default);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Nom de la compétition" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ $old('name') }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="season" value="Saison" />
        <x-text-input id="season" name="season" type="text" class="mt-1 block w-full" value="{{ $old('season', date('Y')) }}" required />
        <x-input-error :messages="$errors->get('season')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" value="Catégorie" />
        <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ $old('category') }}" placeholder="Senior, U20, Vétérans…" />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_date" value="Date de début" />
        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" value="{{ $old('start_date') ? \Illuminate\Support\Carbon::parse($old('start_date'))->format('Y-m-d') : '' }}" />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_date" value="Date de fin" />
        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" value="{{ $old('end_date') ? \Illuminate\Support\Carbon::parse($old('end_date'))->format('Y-m-d') : '' }}" />
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>

    <div x-data="{ format: '{{ $old('format', 'aller_simple') }}' }">
        <x-input-label for="format" value="Format" />
        <select id="format" name="format" x-model="format" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">
            @foreach ([
                'aller_simple' => 'Championnat aller simple',
                'aller_retour' => 'Championnat aller-retour',
                'poules' => 'Poules',
                'elimination_directe' => 'Élimination directe',
            ] as $value => $label)
                <option value="{{ $value }}" @selected($old('format', 'aller_simple') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('format')" class="mt-2" />

        <template x-if="format === 'poules'">
            <div class="mt-3">
                <x-input-label for="number_of_groups" value="Nombre de poules" />
                <x-text-input id="number_of_groups" name="number_of_groups" type="number" min="2" max="8" class="mt-1 block w-full" value="{{ $old('number_of_groups', 2) }}" />
                <p class="text-xs text-text-muted mt-1">Les équipes sont réparties par tirage au sort au moment de la génération du calendrier.</p>
                <x-input-error :messages="$errors->get('number_of_groups')" class="mt-2" />
            </div>
        </template>
        <template x-if="format === 'elimination_directe'">
            <p class="text-xs text-text-muted mt-3">Le nombre d'équipes validées devra être une puissance de 2 (2, 4, 8, 16…) au moment du tirage au sort.</p>
        </template>
    </div>

    <div>
        <x-input-label for="status" value="Statut" />
        <select id="status" name="status" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">
            @foreach ([
                'brouillon' => 'Brouillon',
                'inscriptions_ouvertes' => 'Inscriptions ouvertes',
                'en_cours' => 'En cours',
                'terminee' => 'Terminée',
                'archivee' => 'Archivée',
            ] as $value => $label)
                <option value="{{ $value }}" @selected($old('status', 'brouillon') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">{{ $old('description') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="logo" value="Logo (optionnel)" />
        <div class="mt-1 flex items-center gap-3">
            @if ($competition?->logo_path)
                <x-avatar :path="$competition->logo_path" :name="$competition->name" size="xl" />
            @endif
            <input id="logo" name="logo" type="file" accept="image/*" class="block w-full text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-primary file:text-black file:text-xs file:font-semibold file:uppercase" />
        </div>
        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="points_win" value="Points victoire" />
        <x-text-input id="points_win" name="points_win" type="number" min="0" max="10" class="mt-1 block w-full" value="{{ $old('points_win', 3) }}" required />
    </div>

    <div>
        <x-input-label for="points_draw" value="Points nul" />
        <x-text-input id="points_draw" name="points_draw" type="number" min="0" max="10" class="mt-1 block w-full" value="{{ $old('points_draw', 1) }}" required />
    </div>

    <div>
        <x-input-label for="points_loss" value="Points défaite" />
        <x-text-input id="points_loss" name="points_loss" type="number" min="0" max="10" class="mt-1 block w-full" value="{{ $old('points_loss', 0) }}" required />
    </div>

    <div class="sm:col-span-2 pt-2 border-t border-border">
        <h3 class="font-display font-semibold text-white text-sm mb-1">Règlement disciplinaire</h3>
        <p class="text-xs text-text-muted mb-3">Suspension automatique en cas de carton rouge ou de cumul de cartons jaunes.</p>
    </div>

    <div>
        <x-input-label for="yellow_card_suspension_threshold" value="Cartons jaunes cumulés avant suspension" />
        <x-text-input id="yellow_card_suspension_threshold" name="yellow_card_suspension_threshold" type="number" min="1" max="10" class="mt-1 block w-full" value="{{ $old('yellow_card_suspension_threshold', 3) }}" required />
        <x-input-error :messages="$errors->get('yellow_card_suspension_threshold')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="red_card_suspension_matches" value="Matchs de suspension après carton rouge" />
        <x-text-input id="red_card_suspension_matches" name="red_card_suspension_matches" type="number" min="1" max="10" class="mt-1 block w-full" value="{{ $old('red_card_suspension_matches', 1) }}" required />
        <x-input-error :messages="$errors->get('red_card_suspension_matches')" class="mt-2" />
    </div>

    <div class="sm:col-span-2 flex items-center gap-2">
        <input id="allow_team_managers_to_manage_players" name="allow_team_managers_to_manage_players" type="checkbox" value="1"
            @checked($old('allow_team_managers_to_manage_players', false))
            class="rounded border-border bg-background text-primary focus:ring-primary">
        <x-input-label for="allow_team_managers_to_manage_players" value="Autoriser les responsables d'équipe à gérer leurs joueurs" class="!mb-0" />
    </div>
</div>
