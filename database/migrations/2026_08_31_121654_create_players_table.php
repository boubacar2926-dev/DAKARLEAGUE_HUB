<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->enum('position', ['gardien', 'defenseur', 'milieu', 'attaquant'])->nullable();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('license_number')->nullable();
            $table->timestamps();

            // RG03 : un utilisateur joueur n'est enregistré qu'une seule fois par compétition/saison.
            $table->unique(['competition_id', 'user_id']);
            $table->unique(['team_id', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
