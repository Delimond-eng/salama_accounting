<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : plan_comptable
 * Plan comptable SYSCOHADA révisé — Classes 1 à 9.
 * Chaque société peut personnaliser le plan de base (ajout de sous-comptes).
 * Les comptes de base sont liés à societe_id = NULL (référentiel global).
 * Les comptes personnalisés ont un societe_id.
 *
 * SYSCOHADA Classes :
 *  1 - Comptes de ressources durables
 *  2 - Comptes d'actif immobilisé
 *  3 - Comptes de stocks
 *  4 - Comptes de tiers
 *  5 - Comptes de trésorerie
 *  6 - Comptes de charges des activités ordinaires
 *  7 - Comptes de produits des activités ordinaires
 *  8 - Comptes des autres charges et des autres produits
 *  9 - Comptes des engagements hors bilan et comptes de la comptabilité analytique
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_comptable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->nullable()->constrained('societes')->nullOnDelete();
            $table->string('num_compte', 15)->index();               // Ex: 401000, 6011, 521...
            $table->string('libelle', 255);
            $table->string('libelle_abrege', 60)->nullable();
            $table->tinyInteger('classe');                           // 1 à 9
            $table->string('num_compte_parent', 15)->nullable();    // Pour hiérarchie
            $table->tinyInteger('niveau')->default(1);              // 1=racine, 2=sous, 3=sous-sous...

            // Type de compte
            $table->enum('type_compte', [
                'bilan',        // Actif / Passif
                'gestion',      // Charges / Produits
                'hors_bilan',   // Classe 9
                'analytique'
            ]);

            $table->enum('sens_normal', ['debiteur', 'crediteur']);  // Sens du solde normal

            // Rattachement bilan/résultat
            $table->enum('categorie_bilan', [
                'actif_immobilise',
                'actif_circulant',
                'tresorerie_actif',
                'capitaux_propres',
                'dettes_financieres',
                'passif_circulant',
                'tresorerie_passif',
                'charges_ao',       // Activités ordinaires
                'produits_ao',
                'charges_hao',      // Hors activités ordinaires
                'produits_hao',
                'participation',
                'impots',
                'resultat',
                'non_applicable'
            ])->default('non_applicable');

            // Comportement comptable
            $table->boolean('est_compte_detail')->default(true);     // False = compte de regroupement
            $table->boolean('est_compte_tiers')->default(false);     // Nécessite un tiers
            $table->boolean('est_lettrable')->default(false);        // Peut être lettré
            $table->boolean('est_rapprochable')->default(false);     // Rapprochement bancaire
            $table->boolean('est_budgetaire')->default(false);       // Suivi budgétaire
            $table->boolean('exige_piece_jointe')->default(false);

            // Gestion devises
            $table->boolean('multi_devises')->default(false);

            // Gestion analytique
            $table->boolean('exige_analytique')->default(false);

            // TVA
            $table->enum('type_tva', [
                'collectee',
                'deductible',
                'non_soumis',
                'exonere'
            ])->default('non_soumis');

            $table->decimal('taux_tva_defaut', 5, 2)->nullable();

            // Statut
            $table->boolean('actif')->default(true);
            $table->boolean('est_systeme')->default(false);          // Compte SYSCOHADA non modifiable
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unicité par société
            $table->unique(['societe_id', 'num_compte']);
            $table->index(['classe', 'type_compte']);
            $table->index('num_compte_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_comptable');
    }
};
