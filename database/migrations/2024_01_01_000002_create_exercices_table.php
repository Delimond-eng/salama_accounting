<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : exercices
 * Gestion des exercices comptables par société.
 * Supporte les exercices décalés (ex: 01/04 → 31/03).
 * Un exercice fermé est immuable — aucune écriture ne peut y être modifiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('libelle', 100);                          // Ex: "Exercice 2024"
            $table->year('annee');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut', [
                'ouvert',       // Saisie libre
                'pre_cloture',  // Écritures d'inventaire uniquement
                'cloture',      // Verrouillé, lecture seule
                'archive'       // Archivé, consultation uniquement
            ])->default('ouvert');
            $table->boolean('est_courant')->default(false);         // L'exercice actif
            $table->date('date_ouverture')->nullable();             // Date effective ouverture
            $table->date('date_cloture')->nullable();               // Date effective clôture
            $table->foreignId('cloture_par')->nullable()->constrained('users');
            $table->text('notes_cloture')->nullable();
            $table->boolean('report_a_nouveau_genere')->default(false);
            $table->boolean('bilan_ouverture_genere')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Un seul exercice courant par société
            $table->unique(['societe_id', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercices');
    }
};
