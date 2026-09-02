<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Nouvelle compétition</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg p-6">
                <form method="POST" action="{{ route('admin.competitions.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @include('admin.competitions._form', ['competition' => null])

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.competitions.index') }}" class="px-4 py-2 text-sm text-text-muted hover:text-white">Annuler</a>
                        <x-primary-button>Créer la compétition</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
