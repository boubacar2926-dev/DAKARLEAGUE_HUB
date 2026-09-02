<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('season');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('format', ['aller_simple', 'aller_retour', 'poules', 'elimination_directe'])
                ->default('aller_simple');
            $table->enum('status', ['brouillon', 'inscriptions_ouvertes', 'en_cours', 'terminee', 'archivee'])
                ->default('brouillon');
            $table->unsignedTinyInteger('points_win')->default(3);
            $table->unsignedTinyInteger('points_draw')->default(1);
            $table->unsignedTinyInteger('points_loss')->default(0);
            $table->json('tiebreaker_rules')->nullable();
            $table->boolean('allow_team_managers_to_manage_players')->default(false);
            // restrictOnDelete (et non cascade) : la suppression d'un compte ne doit jamais
            // entraîner la disparition en cascade d'une compétition et de toutes ses données
            // partagées (équipes, joueurs, matchs, résultats publics).
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
