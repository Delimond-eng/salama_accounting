<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLES COMPLÉMENTAIRES :
 *  - axes_analytiques / sections_analytiques  → Comptabilité analytique
 *  - budgets / lignes_budget                  → Contrôle budgétaire
 *  - pieces_jointes                           → GED intégrée
 *  - rapprochements_bancaires                 → Rapprochement bancaire
 *  - lettrage_groupes                         → Gestion du lettrage
 *  - modeles_ecritures                        → Écritures types répétitives
 *  - echeanciers                              → Suivi des échéances
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── COMPTABILITÉ ANALYTIQUE ─────────────────────────────────────────
        Schema::create('axes_analytiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('libelle', 150);
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['societe_id', 'code']);
        });

        Schema::create('sections_analytiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('axe_analytique_id')->constrained('axes_analytiques')->cascadeOnDelete();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('libelle', 150);
            $table->foreignId('parent_id')->nullable()->constrained('sections_analytiques')->nullOnDelete();
            $table->decimal('budget', 15, 2)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['axe_analytique_id', 'code']);
        });

        // ─── BUDGETS ─────────────────────────────────────────────────────────
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->string('libelle', 150);
            $table->enum('type', ['general', 'analytique', 'tresorerie'])->default('general');
            $table->enum('statut', ['brouillon', 'valide', 'archive'])->default('brouillon');
            $table->foreignId('valide_par')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lignes_budget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->string('num_compte', 15)->index();
            $table->foreignId('compte_id')->constrained('plan_comptable');
            $table->foreignId('section_analytique_id')->nullable()->constrained('sections_analytiques')->nullOnDelete();
            // Montant mensuel pour suivi mensuel
            $table->decimal('montant_janvier', 15, 2)->default(0);
            $table->decimal('montant_fevrier', 15, 2)->default(0);
            $table->decimal('montant_mars', 15, 2)->default(0);
            $table->decimal('montant_avril', 15, 2)->default(0);
            $table->decimal('montant_mai', 15, 2)->default(0);
            $table->decimal('montant_juin', 15, 2)->default(0);
            $table->decimal('montant_juillet', 15, 2)->default(0);
            $table->decimal('montant_aout', 15, 2)->default(0);
            $table->decimal('montant_septembre', 15, 2)->default(0);
            $table->decimal('montant_octobre', 15, 2)->default(0);
            $table->decimal('montant_novembre', 15, 2)->default(0);
            $table->decimal('montant_decembre', 15, 2)->default(0);
            $table->decimal('montant_annuel', 15, 2)->virtualAs(
                'montant_janvier + montant_fevrier + montant_mars + montant_avril +
                 montant_mai + montant_juin + montant_juillet + montant_aout +
                 montant_septembre + montant_octobre + montant_novembre + montant_decembre'
            );
            $table->timestamps();
        });

        // ─── PIÈCES JOINTES ──────────────────────────────────────────────────
        Schema::create('pieces_jointes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->morphs('pj_able');                               // Polymorphe : écriture, tiers, budget...
            $table->string('nom_fichier', 255);
            $table->string('nom_original', 255);
            $table->string('chemin', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('taille_octets');
            $table->enum('type_document', [
                'facture', 'bon_commande', 'releve_bancaire',
                'contrat', 'justificatif', 'autre'
            ])->default('autre');
            $table->foreignId('uploade_par')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

        });

        // ─── RAPPROCHEMENT BANCAIRE ───────────────────────────────────────────
        Schema::create('rapprochements_bancaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained('journaux');   // Journal banque
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->date('date_releve');
            $table->decimal('solde_releve', 15, 2);                 // Solde selon la banque
            $table->decimal('solde_comptable', 15, 2);              // Solde selon grand livre
            $table->decimal('ecart', 15, 2)->virtualAs('solde_releve - solde_comptable');
            $table->enum('statut', ['en_cours', 'valide', 'archive'])->default('en_cours');
            $table->foreignId('valide_par')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ─── GROUPES DE LETTRAGE ──────────────────────────────────────────────
        Schema::create('lettrage_groupes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('num_compte', 15);
            $table->foreignId('tiers_id')->nullable()->constrained('tiers')->nullOnDelete();
            $table->string('lettre', 10)->index();                   // La lettre de lettrage
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->decimal('solde_lettre', 15, 2)->default(0);     // 0 = lettrage parfait
            $table->enum('statut', ['partiel', 'complet'])->default('complet');
            $table->date('date_lettrage');
            $table->foreignId('lettre_par')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['societe_id', 'num_compte', 'lettre']);
        });

        // ─── MODÈLES D'ÉCRITURES (Écritures types) ───────────────────────────
        Schema::create('modeles_ecritures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('libelle', 150);
            $table->foreignId('journal_id')->nullable()->constrained('journaux')->nullOnDelete();
            $table->enum('frequence', [
                'ponctuel', 'quotidien', 'hebdomadaire',
                'mensuel', 'trimestriel', 'annuel'
            ])->default('ponctuel');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['societe_id', 'code']);
        });

        Schema::create('modeles_ecritures_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modele_id')->constrained('modeles_ecritures')->cascadeOnDelete();
            $table->string('num_compte', 15);
            $table->foreignId('compte_id')->constrained('plan_comptable');
            $table->string('libelle', 255)->nullable();
            $table->enum('sens', ['debit', 'credit']);
            $table->decimal('montant', 15, 2)->nullable();           // Null = montant à saisir
            $table->decimal('pourcentage', 5, 2)->nullable();        // Pour calculs auto
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        // ─── ÉCHÉANCIERS ──────────────────────────────────────────────────────
        Schema::create('echeanciers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
           // $table->foreignId('ecriture_id')->constrained('ecritures')->cascadeOnDelete();
            //$table->foreignId('ligne_ecriture_id')->constrained('lignes_ecritures')->cascadeOnDelete();
            $table->foreignId('tiers_id')->nullable()->constrained('tiers')->nullOnDelete();
            $table->date('date_echeance');
            $table->decimal('montant', 15, 2);
            $table->enum('sens', ['debit', 'credit']);               // À recevoir / À payer
            $table->enum('statut', [
                'en_attente',
                'partiellement_regle',
                'regle',
                'contentieux',
                'annule'
            ])->default('en_attente');
            $table->decimal('montant_regle', 15, 2)->default(0);
            $table->decimal('montant_restant', 15, 2)->virtualAs('montant - montant_regle');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['societe_id', 'date_echeance', 'statut']);
            $table->index(['tiers_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echeanciers');
        Schema::dropIfExists('modeles_ecritures_lignes');
        Schema::dropIfExists('modeles_ecritures');
        Schema::dropIfExists('lettrage_groupes');
        Schema::dropIfExists('rapprochements_bancaires');
        Schema::dropIfExists('pieces_jointes');
        Schema::dropIfExists('lignes_budget');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('sections_analytiques');
        Schema::dropIfExists('axes_analytiques');
    }
};
