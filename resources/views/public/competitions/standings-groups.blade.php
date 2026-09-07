<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white">{{ $competition->name }} — Classement</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('public.competitions._tabs')

            @if ($standingsByGroup->isEmpty())
                <div class="bg-surface border border-border rounded-lg p-8 text-center text-text-muted">
                    Les poules n'ont pas encore été tirées au sort.
                </div>
            @else
                <div class="space-y-8">
                    @foreach ($standingsByGroup as $groupLabel => $standings)
                        <div>
                            <h3 class="font-display font-semibold text-white mb-3">Poule {{ $groupLabel }}</h3>
                            <div class="bg-surface border border-border rounded-lg overflow-hidden">
                                @include('public.competitions._standings-table')
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
