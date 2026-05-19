<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 40)->nullable();
            $table->string('libelle');
            $table->decimal('prix_unitaire', 18, 2)->default(0);
            $table->string('compte_vente', 20)->default('701100');
            $table->string('compte_achat', 20)->default('601100');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['societe_id', 'code']);
        });

        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('exercice_id')->nullable()->constrained('exercices')->nullOnDelete();
            $table->string('type_document', 30);
            $table->string('numero', 40);
            $table->foreignId('tiers_id')->constrained('tiers');
            $table->foreignId('facture_origine_id')->nullable()->constrained('factures')->nullOnDelete();
            $table->date('date_facture');
            $table->date('date_echeance')->nullable();
            $table->string('statut', 20)->default('brouillon');
            $table->string('objet')->nullable();
            $table->decimal('montant_ht', 18, 2)->default(0);
            $table->decimal('montant_tva', 18, 2)->default(0);
            $table->decimal('montant_ttc', 18, 2)->default(0);
            $table->decimal('taux_tva', 8, 2)->default(0);
            $table->boolean('tva_active')->default(false);
            $table->string('devise', 3)->default('CDF');
            $table->foreignId('ecriture_validation_id')->nullable()->constrained('ecritures')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_le')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['societe_id', 'numero']);
            $table->index(['societe_id', 'type_document', 'statut']);
            $table->index(['societe_id', 'date_echeance', 'statut']);
        });

        Schema::create('facture_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->foreignId('produit_id')->nullable()->constrained('produits')->nullOnDelete();
            $table->unsignedSmallInteger('ordre')->default(1);
            $table->string('libelle');
            $table->decimal('quantite', 18, 4)->default(1);
            $table->decimal('prix_unitaire', 18, 2)->default(0);
            $table->decimal('montant_ht', 18, 2)->default(0);
            $table->string('compte_comptable', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('libelle');
            $table->string('type_workflow', 30)->default('demande_fonds');
            $table->boolean('actif')->default(true);
            $table->boolean('est_defaut')->default(false);
            $table->timestamps();
            $table->unique(['societe_id', 'code']);
        });

        Schema::create('workflow_etapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordre');
            $table->string('code', 40);
            $table->string('libelle');
            $table->string('type_etape', 30);
            $table->string('role_requis')->nullable();
            $table->boolean('imputation_comptable')->default(false);
            $table->boolean('execution_paiement')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['workflow_definition_id', 'ordre']);
        });

        Schema::create('demandes_fonds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions');
            $table->foreignId('workflow_etape_courante_id')->nullable()->constrained('workflow_etapes')->nullOnDelete();
            $table->string('numero', 40);
            $table->foreignId('demandeur_id')->constrained('users');
            $table->decimal('montant', 18, 2);
            $table->string('devise', 3)->default('CDF');
            $table->text('motif');
            $table->foreignId('journal_id')->nullable()->constrained('journaux')->nullOnDelete();
            $table->string('statut', 20)->default('en_attente');
            $table->string('compte_debit', 20)->nullable();
            $table->string('compte_credit', 20)->nullable();
            $table->foreignId('ecriture_id')->nullable()->constrained('ecritures')->nullOnDelete();
            $table->text('motif_rejet')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['societe_id', 'numero']);
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('type_paiement', 30);
            $table->foreignId('facture_id')->nullable()->constrained('factures')->nullOnDelete();
            $table->foreignId('demande_fonds_id')->nullable()->constrained('demandes_fonds')->nullOnDelete();
            $table->string('numero', 40);
            $table->decimal('montant', 18, 2);
            $table->string('devise', 3)->default('CDF');
            $table->string('methode', 20);
            $table->string('compte_tresorerie', 20);
            $table->date('date_paiement');
            $table->string('statut', 20)->default('brouillon');
            $table->foreignId('ecriture_id')->nullable()->constrained('ecritures')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['societe_id', 'numero']);
        });

        Schema::create('demande_fonds_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_fonds_id')->constrained('demandes_fonds')->cascadeOnDelete();
            $table->foreignId('workflow_etape_id')->constrained('workflow_etapes');
            $table->foreignId('user_id')->constrained('users');
            $table->string('decision', 20);
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

        Schema::create('demande_fonds_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_fonds_id')->constrained('demandes_fonds')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('demande_fonds_historiques');
        Schema::dropIfExists('demande_fonds_validations');
        Schema::dropIfExists('demandes_fonds');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('workflow_etapes');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('facture_lignes');
        Schema::dropIfExists('factures');
        Schema::dropIfExists('produits');
    }
};
