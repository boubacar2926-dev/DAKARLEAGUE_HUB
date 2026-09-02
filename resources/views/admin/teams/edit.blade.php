<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Modifier « {{ $team->name }} »</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg p-6">
                <form method="POST" action="{{ route('admin.teams.update', $team) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('admin.teams._form', ['team' => $team])

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.competitions.teams.index', $team->competition_id) }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
