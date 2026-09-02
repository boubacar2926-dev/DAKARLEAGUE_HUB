<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->enum('type', ['but', 'but_penalty', 'but_contre_son_camp', 'carton_jaune', 'carton_rouge']);
            $table->unsignedTinyInteger('minute')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
