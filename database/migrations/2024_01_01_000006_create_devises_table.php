<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : devises
 * Référentiel ISO 4217 des devises.
 * La devise de référence (XOF/XAF/GNF...) a taux = 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devises', function (Blueprint $table) {
            $table->id();
            $table->string('code_iso', 3)->unique();                 // XOF, EUR, USD, GBP...
            $table->string('libelle', 100);
            $table->string('symbole', 10)->nullable();               // FCFA, €, $...
            $table->string('pays', 100)->nullable();
            $table->integer('nb_decimales')->default(0);            // XOF = 0, EUR = 2
            $table->boolean('est_devise_reference')->default(false); // La devise de la société
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        /**
         * TABLE : taux_change
         * Historique des taux de change pour chaque devise.
         * Un taux par jour permet les conversions historiques fidèles.
         * Les écarts de conversion fin d'exercice → comptes 476/477 SYSCOHADA.
         */
        Schema::create('taux_change', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('devise_code', 3)->index();
            $table->date('date_taux');
            $table->decimal('taux', 18, 6);                         // 1 USD = X devises locales
            $table->decimal('taux_achat', 18, 6)->nullable();       // Taux d'achat banque
            $table->decimal('taux_vente', 18, 6)->nullable();       // Taux de vente banque
            $table->enum('source', [
                'manuel',
                'bceao',        // Banque Centrale
                'beac',
                'banque_centrale',
                'api_automatique'
            ])->default('manuel');
            $table->timestamps();

            $table->unique(['societe_id', 'devise_code', 'date_taux']);
            $table->index(['devise_code', 'date_taux']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taux_change');
        Schema::dropIfExists('devises');
    }
};
