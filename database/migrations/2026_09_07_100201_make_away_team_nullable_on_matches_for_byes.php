<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un tableau à élimination directe dont le nombre d'équipes n'est pas une puissance de 2
     * a besoin d'"exemptés" (byes) : une équipe sans adversaire au 1er tour, qualifiée
     * d'office pour le tour suivant. On la représente comme un match réel (round, historique)
     * mais sans équipe adverse plutôt que d'inventer une fausse équipe.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('away_team_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('away_team_id')->nullable(false)->change();
        });
    }
};
