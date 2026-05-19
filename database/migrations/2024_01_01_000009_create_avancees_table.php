<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLES AVANCÉES :
 *  - declarations_fiscales     → TVA, IS, DSF, IRCM...
 *  - etats_financiers_sauv     → Sauvegarde des états générés
 *  - parametres_systeme        → Configuration dynamique globale
 *  - parametres_societe        → Configuration par société
 *  - roles_utilisateurs        → RBAC (accès par module)
 *  - journal_audit             → Traçabilité complète (qui a fait quoi)
 *  - notifications             → Alertes et rappels
 *  - imports_logs              → Log des imports de données
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── DÉCLARATIONS FISCALES ────────────────────────────────────────────
        Schema::create('declarations_fiscales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->enum('type', [
                'tva_mensuelle',
                'tva_trimestrielle',
                'is',               // Impôt sur les sociétés
                'dsf',              // Déclaration Statistique et Fiscale
                'ircm',             // Impôt sur Revenu des Capitaux Mobiliers
                'patente',
                'cnps_mensuel',
                'other'
            ]);
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->date('date_limite_depot');
            $table->date('date_depot_effectif')->nullable();

            // Montants
            $table->decimal('base_imposable', 15, 2)->default(0);
            $table->decimal('tva_collectee', 15, 2)->default(0);
            $table->decimal('tva_deductible', 15, 2)->default(0);
            $table->decimal('tva_nette', 15, 2)->default(0);
            $table->decimal('montant_impot', 15, 2)->default(0);
            $table->decimal('credit_reporte', 15, 2)->default(0);

            $table->enum('statut', [
                'a_declarer',
                'brouillon',
                'deposee',
                'payee',
                'en_contentieux'
            ])->default('a_declarer');

            $table->string('num_quittance', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('etabli_par')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['societe_id', 'type', 'periode_debut']);
        });

        // ─── SAUVEGARDE DES ÉTATS FINANCIERS GÉNÉRÉS ─────────────────────────
        Schema::create('etats_financiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->enum('type', [
                'bilan',
                'compte_resultat',
                'tafire',                   // Tableau des flux de trésorerie
                'variation_capitaux_propres',
                'balance_generale',
                'balance_auxiliaire',
                'grand_livre',
                'balance_agee',
                'autre'
            ]);
            $table->date('date_arrete');                             // Date d'arrêté
            $table->json('donnees');                                 // Les chiffres en JSON
            $table->string('fichier_path', 500)->nullable();        // PDF généré
            $table->boolean('est_definitif')->default(false);
            $table->foreignId('genere_par')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['societe_id', 'exercice_id', 'type']);
        });

        // ─── PARAMÈTRES SYSTÈME DYNAMIQUES ───────────────────────────────────
        Schema::create('parametres_systeme', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 100)->unique();                    // Ex: 'syscohada_version'
            $table->text('valeur')->nullable();
            $table->string('type_valeur', 30)->default('string');   // string, int, bool, json
            $table->string('groupe', 50)->nullable();               // Regroupement
            $table->string('libelle', 200)->nullable();
            $table->boolean('modifiable', )->default(true);
            $table->timestamps();
        });

        Schema::create('parametres_societe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('cle', 100);
            $table->text('valeur')->nullable();
            $table->string('type_valeur', 30)->default('string');
            $table->timestamps();
            $table->unique(['societe_id', 'cle']);
        });

        // ─── RÔLES ET PERMISSIONS (RBAC) ──────────────────────────────────────
      /*  Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->unique();                    // admin, comptable, lecteur...
            $table->string('libelle', 150);
            $table->text('description')->nullable();
            $table->boolean('est_systeme')->default(false);         // Rôle non supprimable
            $table->timestamps();
        });

       /* Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);                            // saisie, livres, etats...
            $table->string('action', 50);                            // lire, creer, modifier, supprimer, valider
            $table->string('libelle', 150);
            $table->timestamps();
            $table->unique(['module', 'action']);
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });*/

        Schema::create('utilisateur_societe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
           // $table->foreignId('role_id')->constrained('roles');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'societe_id']);
        });

        // ─── JOURNAL D'AUDIT (Traçabilité totale) ────────────────────────────
        Schema::create('journal_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->nullable()->constrained('societes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);                            // create, update, delete, validate, close...
            $table->string('module', 50);                            // ecritures, plan_comptable, tiers...
            $table->string('entite_type', 100)->nullable();
            $table->unsignedBigInteger('entite_id')->nullable();
            $table->json('avant')->nullable();                       // État avant modification
            $table->json('apres')->nullable();                       // État après modification
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['societe_id', 'module', 'created_at']);
            $table->index(['entite_type', 'entite_id']);
            $table->index('user_id');
        });

        // ─── NOTIFICATIONS & ALERTES ──────────────────────────────────────────
        Schema::create('notifications_compta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', [
                'echeance_proche',      // Échéance dans X jours
                'echeance_depassee',    // Échéance dépassée
                'declaration_due',      // Déclaration fiscale à déposer
                'lettrage_ecart',       // Écart de lettrage
                'rapprochement_ecart',  // Écart rapprochement bancaire
                'exercice_non_cloture', // Exercice toujours ouvert
                'depassement_budget',   // Budget dépassé
                'autre'
            ]);
            $table->string('titre', 200);
            $table->text('message');
            $table->string('lien', 500)->nullable();                 // Route vers l'entité concernée
            $table->boolean('lue')->default(false);
            $table->timestamp('lue_le')->nullable();
            $table->enum('priorite', ['basse', 'normale', 'haute', 'critique'])->default('normale');
            $table->timestamps();

            $table->index(['user_id', 'lue', 'created_at']);
        });

        // ─── LOG DES IMPORTS ──────────────────────────────────────────────────
        Schema::create('imports_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type_import', [
                'releve_bancaire',      // Import relevé bancaire (OFX, CSV)
                'plan_comptable',
                'tiers',
                'ecritures_csv',
                'factures'
            ]);
            $table->string('nom_fichier', 255);
            $table->integer('total_lignes')->default(0);
            $table->integer('lignes_importees')->default(0);
            $table->integer('lignes_erreurs')->default(0);
            $table->json('erreurs')->nullable();
            $table->enum('statut', ['en_cours', 'termine', 'echec'])->default('en_cours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports_logs');
        Schema::dropIfExists('notifications_compta');
        Schema::dropIfExists('journal_audit');
        Schema::dropIfExists('utilisateur_societe');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('parametres_societe');
        Schema::dropIfExists('parametres_systeme');
        Schema::dropIfExists('etats_financiers');
        Schema::dropIfExists('declarations_fiscales');
    }
};
