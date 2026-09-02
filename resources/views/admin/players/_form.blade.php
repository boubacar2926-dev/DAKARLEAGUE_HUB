@php
    $old = fn ($field, $default = null) => old($field, $player?->{$field} ?? $default);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="first_name" value="Prénom" />
        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" value="{{ $old('first_name') }}" required autofocus />
        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="last_name" value="Nom" />
        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" value="{{ $old('last_name') }}" required />
        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="birth_date" value="Date de naissance" />
        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" value="{{ $old('birth_date') ? \Illuminate\Support\Carbon::parse($old('birth_date'))->format('Y-m-d') : '' }}" />
        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="position" value="Poste" />
        <select id="position" name="position" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">
            <option value="">—</option>
            @foreach ([
                'gardien' => 'Gardien',
                'defenseur' => 'Défenseur',
                'milieu' => 'Milieu',
                'attaquant' => 'Attaquant',
            ] as $value => $label)
                <option value="{{ $value }}" @selected($old('position') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="jersey_number" value="Numéro de maillot" />
        <x-text-input id="jersey_number" name="jersey_number" type="number" min="1" max="99" class="mt-1 block w-full" value="{{ $old('jersey_number') }}" />
        <x-input-error :messages="$errors->get('jersey_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="license_number" value="N° de licence (facultatif)" />
        <x-text-input id="license_number" name="license_number" type="text" class="mt-1 block w-full" value="{{ $old('license_number') }}" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="photo" value="Photo (optionnel)" />
        <div class="mt-1 flex items-center gap-3">
            @if ($player?->photo_path)
                <x-avatar :path="$player->photo_path" :name="$player->fullName()" size="lg" />
            @endif
            <input id="photo" name="photo" type="file" accept="image/*" class="block w-full text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-primary file:text-black file:text-xs file:font-semibold file:uppercase" />
        </div>
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
    </div>
</div>
