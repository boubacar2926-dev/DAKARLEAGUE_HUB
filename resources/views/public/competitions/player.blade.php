<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-avatar :path="$player->photo_path" :name="$player->fullName()" size="lg" />
            <div>
                <p class="text-xs text-text-muted">
                    <a href="{{ route('public.competitions.team', [$competition, $player->team]) }}" class="hover:text-primary">&larr; {{ $player->team->name }}</a>
                </p>
                <h2 class="font-display font-bold text-xl text-white">
                    {{ $player->fullName() }}
                    @if ($suspension['suspended'] ?? false)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-danger/10 text-danger align-middle">
                            🚫 Suspendu
                        </span>
                    @endif
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($suspension['suspended'] ?? false)
                <div class="rounded-md border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
                    Suspendu pour le prochain match — motif : {{ $suspension['reason'] }}.
                </div>
            @endif

            <div class="bg-surface border border-border rounded-lg p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-text-muted text-xs uppercase">Équipe</div>
                        <div class="text-white">{{ $player->team->name }}</div>
                    </div>
                    <div>
                        <div class="text-text-muted text-xs uppercase">Poste</div>
                        <div class="text-white capitalize">{{ $player->position ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-text-muted text-xs uppercase">Numéro</div>
                        <div class="text-white">{{ $player->jersey_number ?? '—' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-border">
                    <div class="text-center">
                        <div class="text-2xl font-display font-bold text-primary">{{ $goals }}</div>
                        <div class="text-xs text-text-muted uppercase">Buts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-display font-bold text-yellow-400">{{ $yellowCards }}</div>
                        <div class="text-xs text-text-muted uppercase">Cartons jaunes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-display font-bold text-danger">{{ $redCards }}</div>
                        <div class="text-xs text-text-muted uppercase">Cartons rouges</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
