<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : ecritures
 * En-tête de chaque écriture comptable. Une écriture = plusieurs lignes.
 * RÈGLE D'OR SYSCOHADA : Σ débits = Σ crédits (contrainte applicative + trigger possible)
 *
 * Cycle de vie d'une écriture :
 *   brouillon → validee → lettrée (partielle/totale) → extournée
 *
 * TABLE : lignes_ecritures
 * Chaque ligne = un mouvement sur un compte (débit OU crédit).
 * Design : deux colonnes séparées debit/credit (JAMAIS un montant signé).
 * Cela simplifie grand livre, balance, états financiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── EN-TÊTES D'ÉCRITURES ───────────────────────────────────────────
        Schema::create('ecritures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('journal_id')->constrained('journaux');

            // Numéro de pièce (généré automatiquement selon config journal)
            $table->string('num_piece', 50)->index();               // Ex: FA-2024-0042
            $table->string('num_piece_interne', 50)->nullable();    // Numéro séquentiel interne

            $table->date('date_ecriture');                          // Date comptable
            $table->date('date_piece')->nullable();                 // Date de la pièce justificative
            $table->date('date_valeur')->nullable();                // Date valeur (banque)
            $table->date('date_echeance')->nullable();              // Date d'échéance

            $table->string('libelle', 255);                         // Libellé général

            $table->enum('statut', [
                'brouillon',    // Saisie en cours, modifiable
                'validee',      // Comptabilisée, plus modifiable
                'extournee',    // Extournée (contre-passation)
                'simulee'       // Simulation / Budget
            ])->default('brouillon');

            $table->enum('type_ecriture', [
                'normale',
                'ouverture',        // Report à-nouveau
                'cloture',          // Écriture de clôture
                'inventaire',       // Amortissements, provisions, régularisation
                'extourne',         // Contre-passation
                'simulation',
                'budget'
            ])->default('normale');

            // Référence pièce justificative
            $table->string('reference_externe', 100)->nullable();   // N° facture fournisseur, etc.
            $table->string('reference_facture', 100)->nullable();

            // Totaux (dénormalisés pour performance)
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);

            // Devise
            $table->string('devise', 3)->default('XOF');
            $table->decimal('taux_change', 18, 6)->default(1);

            // Traçabilité
            $table->foreignId('cree_par')->nullable()->constrained('users');
            $table->foreignId('valide_par')->nullable()->constrained('users');
            $table->timestamp('valide_le')->nullable();
            $table->foreignId('modifie_par')->nullable()->constrained('users');

            // Extourne
            $table->foreignId('ecriture_origine_id')->nullable()->constrained('ecritures');
            $table->date('date_extourne')->nullable();

            // Import
            $table->boolean('est_import')->default(false);
            $table->string('source_import', 100)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['societe_id', 'exercice_id', 'date_ecriture']);
            $table->index(['societe_id', 'journal_id', 'date_ecriture']);
            $table->index(['societe_id', 'statut']);
         
        });

        // ─── LIGNES D'ÉCRITURES ─────────────────────────────────────────────
        Schema::create('lignes_ecritures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecriture_id')->constrained('ecritures')->cascadeOnDelete();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('journal_id')->constrained('journaux');

            // Compte
            $table->string('num_compte', 15)->index();
            $table->foreignId('compte_id')->constrained('plan_comptable');

            // Tiers (obligatoire pour comptes de tiers : 40x, 41x, 42x, 43x...)
            $table->foreignId('tiers_id')->nullable()->constrained('tiers')->nullOnDelete();

            // Date (héritée de l'écriture mais peut différer pour les échéances)
            $table->date('date_ecriture');

            $table->string('libelle', 255);

            // LE CŒUR : deux colonnes séparées, jamais de montant signé
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            // Devise (si multi-devises)
            $table->string('devise', 3)->default('XOF');
            $table->decimal('montant_devise', 15, 2)->nullable();   // Montant en devise étrangère
            $table->decimal('taux_change', 18, 6)->default(1);

            // Lettrage (rapprochement créances/dettes)
            $table->string('lettre', 10)->nullable()->index();      // Ex: "AA", "B12"
            $table->date('date_lettrage')->nullable();
            $table->foreignId('lettre_par')->nullable()->constrained('users');

            // Pointage (rapprochement bancaire)
            $table->string('pointage', 10)->nullable();
            $table->date('date_pointage')->nullable();

            // Analytique
            $table->foreignId('axe_analytique_id')->nullable()->constrained('axes_analytiques')->nullOnDelete();
            $table->foreignId('section_analytique_id')->nullable()->constrained('sections_analytiques')->nullOnDelete();

            // Numéro de séquence dans l'écriture
            $table->integer('ordre')->default(0);

            // Pièce jointe spécifique à la ligne
            $table->string('reference_ligne', 100)->nullable();

            $table->timestamps();

            $table->index(['societe_id', 'num_compte', 'date_ecriture']);
            $table->index(['societe_id', 'exercice_id', 'num_compte']);
            $table->index(['tiers_id', 'lettre']);
            $table->index(['num_compte', 'lettre']);
            $table->index('date_ecriture');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_ecritures');
        Schema::dropIfExists('ecritures');
    }
};
