@php
    $old = fn ($field, $default = null) => old($field, $team?->{$field} ?? $default);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Nom de l'équipe" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ $old('name') }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="city" value="Ville" />
        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" value="{{ $old('city') }}" />
    </div>

    <div>
        <x-input-label for="home_ground" value="Terrain / stade" />
        <x-text-input id="home_ground" name="home_ground" type="text" class="mt-1 block w-full" value="{{ $old('home_ground') }}" />
    </div>

    <div>
        <x-input-label for="manager_name" value="Responsable" />
        <x-text-input id="manager_name" name="manager_name" type="text" class="mt-1 block w-full" value="{{ $old('manager_name') }}" />
    </div>

    <div>
        <x-input-label for="contact_phone" value="Téléphone de contact" />
        <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full" value="{{ $old('contact_phone') }}" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="contact_email" value="Email de contact" />
        <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full" value="{{ $old('contact_email') }}" />
        <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="primary_color" value="Couleur principale" />
        <input id="primary_color" name="primary_color" type="color" value="{{ $old('primary_color', '#B6FF3B') }}" class="mt-1 block w-16 h-10 bg-background border-border rounded-md">
    </div>

    <div>
        <x-input-label for="secondary_color" value="Couleur secondaire" />
        <input id="secondary_color" name="secondary_color" type="color" value="{{ $old('secondary_color', '#0D0D0D') }}" class="mt-1 block w-16 h-10 bg-background border-border rounded-md">
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="logo" value="Logo (optionnel)" />
        <div class="mt-1 flex items-center gap-3">
            @if ($team?->logo_path)
                <x-avatar :path="$team->logo_path" :name="$team->name" size="lg" />
            @endif
            <input id="logo" name="logo" type="file" accept="image/*" class="block w-full text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-primary file:text-black file:text-xs file:font-semibold file:uppercase" />
        </div>
        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
    </div>
</div>
