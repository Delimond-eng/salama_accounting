<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : tiers
 * Clients, fournisseurs, salariés, actionnaires, organismes sociaux, banques, etc.
 * Un tiers peut être à la fois client ET fournisseur (type 'both').
 * Liaison avec le plan comptable : compte collectif (401xxx, 411xxx, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('code', 30)->index();                     // Code unique par société
            $table->string('nom', 255);
            $table->string('nom_abrege', 60)->nullable();

            $table->enum('type', [
                'client',
                'fournisseur',
                'client_fournisseur',
                'salarie',
                'actionnaire',
                'banque',
                'organisme_social',  // CNPS, CNAM, CNRPS...
                'administration',    // Fisc, douanes...
                'autre'
            ]);

            // Compte comptable rattaché
            $table->string('num_compte_collectif', 15)->nullable(); // Ex: 401000, 411000

            // Identification légale
            $table->enum('forme_juridique', [
                'personne_physique',
                'sarl', 'sa', 'snc', 'sci', 'sas',
                'ong', 'association', 'etat', 'autre'
            ])->nullable();
            $table->string('rccm', 100)->nullable();
            $table->string('num_contribuable', 100)->nullable();
            $table->string('num_cnps', 100)->nullable();

            // Coordonnées
            $table->text('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('site_web', 150)->nullable();
            $table->string('contact_principal', 150)->nullable();

            // Conditions commerciales (client / fournisseur)
            $table->integer('delai_paiement_jours')->nullable();    // Délai de règlement
            $table->enum('mode_paiement_defaut', [
                'virement', 'cheque', 'especes', 'mobile_money',
                'effet', 'compensation', 'autre'
            ])->nullable();
            $table->decimal('plafond_credit', 15, 2)->nullable();   // Plafond d'encours

            // Devise
            $table->string('devise', 3)->nullable();                 // Devise de facturation

            // Statut
            $table->boolean('actif')->default(true);
            $table->boolean('bloque')->default(false);              // Tiers bloqué (contentieux)
            $table->text('motif_blocage')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['societe_id', 'code']);
            $table->index(['societe_id', 'type']);
            $table->index('num_compte_collectif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
