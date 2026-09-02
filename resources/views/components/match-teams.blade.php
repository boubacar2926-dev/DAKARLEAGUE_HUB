@props(['match', 'size' => 'sm'])

<span class="inline-flex items-center gap-3 text-white">
    <span class="inline-flex items-center gap-1.5">
        <x-avatar :path="$match->homeTeam->logo_path" :name="$match->homeTeam->name" :size="$size" />
        {{ $match->homeTeam->name }}
    </span>
    <span class="text-text-muted">—</span>
    <span class="inline-flex items-center gap-1.5">
        <x-avatar :path="$match->awayTeam->logo_path" :name="$match->awayTeam->name" :size="$size" />
        {{ $match->awayTeam->name }}
    </span>
</span>
