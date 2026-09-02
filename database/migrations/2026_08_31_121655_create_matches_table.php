<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('round')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->enum('status', ['programme', 'en_cours', 'termine', 'reporte', 'annule'])
                ->default('programme');
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->text('postponed_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['competition_id', 'status']);
        });

        // RG04 : un match oppose deux équipes distinctes.
        // ALTER TABLE ... ADD CONSTRAINT CHECK n'est pas supporté par SQLite (utilisé en tests) ;
        // on ne l'applique donc que sur le driver de production (MySQL).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `matches` ADD CONSTRAINT chk_matches_distinct_teams CHECK (home_team_id <> away_team_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
