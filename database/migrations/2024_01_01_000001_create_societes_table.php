<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLE : societes
 * Multi-société : une instance peut gérer plusieurs entités juridiques.
 * Chaque société a son propre plan comptable, ses propres exercices, ses propres utilisateurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('societes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                    // Code interne ex: SOCT001
            $table->string('raison_sociale', 255);
            $table->string('forme_juridique', 50)->nullable();       // SA, SARL, SNC, ETS, ONG...
            $table->string('sigle', 50)->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->default('RDC');
            $table->string('telephone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('site_web', 150)->nullable();
            $table->string('rccm', 100)->nullable();                 // Registre Commerce
            $table->string('num_contribuable', 100)->nullable();     // NIF / IFU
            $table->string('num_cnps', 100)->nullable();
            $table->string('regime_fiscal', 50)->nullable();         // RSI, RNI, Forfait...
            $table->string('devise_principale', 3)->default('XOF'); // ISO 4217
            $table->string('logo_path', 255)->nullable();
            $table->enum('statut', ['active', 'inactive', 'suspendue'])->default('active');
            $table->json('parametres')->nullable();                  // Config dynamique future
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('societes');
    }
};
