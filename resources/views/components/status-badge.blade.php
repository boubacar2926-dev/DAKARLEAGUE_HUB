@props(['status'])

@php
$labels = [
    'brouillon' => 'Brouillon',
    'inscriptions_ouvertes' => 'Inscriptions ouvertes',
    'en_cours' => 'En cours',
    'terminee' => 'Terminée',
    'archivee' => 'Archivée',
    'programme' => 'Programmé',
    'en_cours_match' => 'En cours',
    'termine' => 'Terminé',
    'reporte' => 'Reporté',
    'annule' => 'Annulé',
    'en_attente' => 'En attente de validation',
    'validee' => 'Validée',
    'refusee' => 'Refusée',
];

$colors = [
    'brouillon' => 'bg-white/10 text-text-muted',
    'inscriptions_ouvertes' => 'bg-blue-500/10 text-blue-400',
    'en_cours' => 'bg-primary/10 text-primary',
    'terminee' => 'bg-white/10 text-text-muted',
    'archivee' => 'bg-white/10 text-text-muted',
    'programme' => 'bg-blue-500/10 text-blue-400',
    'termine' => 'bg-primary/10 text-primary',
    'reporte' => 'bg-yellow-500/10 text-yellow-400',
    'annule' => 'bg-danger/10 text-danger',
    'en_attente' => 'bg-yellow-500/10 text-yellow-400',
    'validee' => 'bg-primary/10 text-primary',
    'refusee' => 'bg-danger/10 text-danger',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($colors[$status] ?? 'bg-white/10 text-text-muted')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
