<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : journaux
 * Journaux comptables SYSCOHADA obligatoires + journaux personnalisés.
 * Journaux de base : HA (Achats), VT (Ventes), BQ (Banque), CA (Caisse), OD (Opérations Diverses)
 * Possibilité de créer autant de journaux que nécessaire (multi-banques, multi-caisses, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 10);                              // HA, VT, BQ, CA, OD...
            $table->string('libelle', 150);
            $table->enum('type', [
                'achats',           // Journal des achats
                'ventes',           // Journal des ventes
                'banque',           // Journal de banque
                'caisse',           // Journal de caisse
                'operations_diverses', // OD / Opérations diverses
                'salaires',         // Journal de paie
                'stocks',           // Journal des stocks
                'effets',           // Effets à recevoir / à payer
                'immobilisations',  // Journal des immobilisations
                'ouverture',        // Journal d'ouverture
                'cloture',          // Journal de clôture
                'simulation'        // Brouillard / simulation
            ]);

            // Compte de contrepartie automatique (ex: 521 pour banque)
            $table->string('compte_contrepartie', 15)->nullable();

            // Numérotation automatique des pièces
            $table->string('prefixe_piece', 10)->nullable();        // Ex: "FA-", "BQ-"
            $table->integer('prochain_numero')->default(1);
            $table->enum('format_numerotation', [
                'annuel',           // Repart à 1 chaque année : FA-2024-0001
                'mensuel',          // Repart à 1 chaque mois
                'continu'           // Ne repart jamais
            ])->default('annuel');
            $table->integer('padding_numero')->default(4);          // Nb de zéros ex: 0001

            // Comportement
            $table->boolean('saisie_tiers_obligatoire')->default(false);
            $table->boolean('saisie_lettrage_auto')->default(false);
            $table->boolean('mode_brouillard')->default(false);     // Écritures non validées

            // Devise
            $table->string('devise_defaut', 3)->nullable();

            $table->boolean('actif')->default(true);
            $table->integer('ordre_affichage')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['societe_id', 'code']);
            $table->index(['societe_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journaux');
    }
};
