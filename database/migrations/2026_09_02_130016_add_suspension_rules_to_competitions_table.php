<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Règles de suspension pour accumulation/gravité de cartons (règlement disciplinaire standard
     * du football amateur) : carton rouge (direct ou 2e jaune dans le même match) = suspension
     * automatique ; cumul de cartons jaunes sur la compétition = suspension automatique.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedTinyInteger('yellow_card_suspension_threshold')->default(3)->after('allow_team_managers_to_manage_players');
            $table->unsignedTinyInteger('red_card_suspension_matches')->default(1)->after('yellow_card_suspension_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['yellow_card_suspension_threshold', 'red_card_suspension_matches']);
        });
    }
};
