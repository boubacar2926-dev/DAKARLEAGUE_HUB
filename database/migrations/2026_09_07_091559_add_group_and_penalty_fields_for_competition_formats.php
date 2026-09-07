<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonnes nécessaires pour activer réellement les formats poules et élimination directe
     * (jusqu'ici acceptés en base mais non générés par CalendarService) :
     *  - teams.group_label : poule tirée au sort (A, B, C…) pour le format "poules".
     *  - competitions.number_of_groups : nombre de poules souhaité, configurable par l'organisateur.
     *  - matches.home_penalties / away_penalties : séance de tirs au but, seul moyen de départager
     *    un match à élimination directe terminé sur un score nul (un tour suivant a besoin d'un vainqueur).
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('group_label', 2)->nullable()->after('registration_status');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedTinyInteger('number_of_groups')->nullable()->after('format');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedTinyInteger('home_penalties')->nullable()->after('away_score');
            $table->unsignedTinyInteger('away_penalties')->nullable()->after('home_penalties');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('group_label');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('number_of_groups');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['home_penalties', 'away_penalties']);
        });
    }
};
