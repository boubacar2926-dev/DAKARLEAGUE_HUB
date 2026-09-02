<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CalendarService::hasSchedulingConflict() filtre les matchs par competition_id +
     * scheduled_at à chaque création/déplacement de match, et MatchController::index() trie
     * par scheduled_at : aucun index ne couvrait cette combinaison (seul ['competition_id',
     * 'status'] existait), forçant un scan de tous les matchs de la compétition à chaque
     * vérification de conflit de créneau.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->index(['competition_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['competition_id', 'scheduled_at']);
        });
    }
};
