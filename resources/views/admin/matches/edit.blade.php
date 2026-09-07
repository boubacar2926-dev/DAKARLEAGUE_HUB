<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $match->homeTeam->name }} — {{ $match->awayTeam->name ?? 'Exempté' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg p-6">
                <form id="match-edit-form" method="POST" action="{{ route('admin.matches.update', $match) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="scheduled_at" value="Date et heure" />
                            <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local"
                                class="mt-1 block w-full"
                                value="{{ old('scheduled_at', $match->scheduled_at?->format('Y-m-d\TH:i')) }}" />
                            <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="venue" value="Lieu / terrain" />
                            <x-text-input id="venue" name="venue" type="text" class="mt-1 block w-full" value="{{ old('venue', $match->venue) }}" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" value="Statut" />
                        <select id="status" name="status" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">
                            @foreach ([
                                'programme' => 'Programmé',
                                'reporte' => 'Reporté',
                                'annule' => 'Annulé',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $match->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="postponed_reason" value="Motif du report (si applicable)" />
                        <textarea id="postponed_reason" name="postponed_reason" rows="2" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">{{ old('postponed_reason', $match->postponed_reason) }}</textarea>
                        <x-input-error :messages="$errors->get('postponed_reason')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="cancellation_reason" value="Motif de l'annulation (si applicable)" />
                        <textarea id="cancellation_reason" name="cancellation_reason" rows="2" class="mt-1 block w-full bg-background border-border text-white rounded-md shadow-sm focus:border-primary focus:ring-primary">{{ old('cancellation_reason', $match->cancellation_reason) }}</textarea>
                        <x-input-error :messages="$errors->get('cancellation_reason')" class="mt-2" />
                    </div>

                </form>

                <div class="flex items-center justify-between mt-6">
                    <form action="{{ route('admin.matches.destroy', $match) }}" method="POST" onsubmit="return confirm('Supprimer ce match ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-danger hover:underline">Supprimer le match</button>
                    </form>

                    <div class="flex gap-3">
                        <a href="{{ route('admin.competitions.matches.index', $match->competition_id) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                        <x-primary-button form="match-edit-form">Enregistrer</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
