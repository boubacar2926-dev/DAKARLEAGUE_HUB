<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * documents.uploaded_by était en cascadeOnDelete() : la suppression d'un compte utilisateur
     * effaçait silencieusement les documents qu'il avait générés/exportés (calendriers,
     * classements, rapports PDF), y compris pour des compétitions actives partagées par d'autres
     * organisateurs. Comme pour competitions.created_by (restrictOnDelete), on protège désormais
     * ces enregistrements à valeur de traçabilité : un compte ayant des documents associés ne
     * peut plus être supprimé sans traiter ces documents au préalable.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
