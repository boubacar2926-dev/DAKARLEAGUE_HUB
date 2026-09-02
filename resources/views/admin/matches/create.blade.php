<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Ajouter un match — {{ $competition->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg p-6">
                <form method="POST" action="{{ route('admin.competitions.matches.store', $competition) }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="home_team_id" value="Équipe à domicile" />
                            <select id="home_team_id" name="home_team_id" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                                <option value="">—</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected(old('home_team_id') == $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('home_team_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="away_team_id" value="Équipe à l'extérieur" />
                            <select id="away_team_id" name="away_team_id" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                                <option value="">—</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected(old('away_team_id') == $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('away_team_id')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="round" value="Journée" />
                            <x-text-input id="round" name="round" type="number" min="1" class="mt-1 block w-full" value="{{ old('round') }}" />
                        </div>
                        <div>
                            <x-input-label for="scheduled_at" value="Date et heure" />
                            <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full" value="{{ old('scheduled_at') }}" />
                            <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="venue" value="Lieu / terrain" />
                        <x-text-input id="venue" name="venue" type="text" class="mt-1 block w-full" value="{{ old('venue') }}" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.competitions.matches.index', $competition) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                        <x-primary-button>Ajouter le match</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
