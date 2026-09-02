<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">Compétitions</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($competitions->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Aucune compétition publiée pour le moment.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($competitions as $competition)
                        <a href="{{ route('public.competitions.show', $competition) }}" class="bg-surface border border-border rounded-lg p-5 hover:border-primary transition">
                            <div class="flex items-center justify-between mb-2">
                                <x-status-badge :status="$competition->status" />
                                <span class="text-xs text-text-muted">{{ $competition->season }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-avatar :path="$competition->logo_path" :name="$competition->name" size="lg" />
                                <div class="font-display font-semibold text-white text-lg">{{ $competition->name }}</div>
                            </div>
                            <p class="text-sm text-text-muted mt-1">{{ $competition->category }}</p>
                            <div class="text-xs text-text-muted mt-3">{{ $competition->teams_count }} équipes</div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $competitions->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
