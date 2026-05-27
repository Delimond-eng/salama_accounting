<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('statut', ['ouverte', 'en_cours', 'terminee', 'annulee'])->default('ouverte');
            $table->foreignId('cree_par')->constrained('users')->cascadeOnDelete();
            $table->date('date_echeance')->nullable();
            $table->timestamps();
            $table->index(['societe_id', 'statut']);
            $table->index(['cree_par', 'societe_id']);
        });

        Schema::create('tache_etapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained('taches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('libelle');
            $table->unsignedSmallInteger('ordre')->default(1);
            $table->boolean('est_terminee')->default(false);
            $table->timestamp('terminee_le')->nullable();
            $table->foreignId('terminee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tache_id', 'user_id']);
        });

        Schema::create('tache_rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained('taches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('contenu');
            $table->timestamps();
        });

        Schema::create('tache_fichiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tache_id')->constrained('taches')->cascadeOnDelete();
            $table->foreignId('rapport_id')->nullable()->constrained('tache_rapports')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('chemin');
            $table->string('nom_fichier');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('taille')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tache_fichiers');
        Schema::dropIfExists('tache_rapports');
        Schema::dropIfExists('tache_etapes');
        Schema::dropIfExists('taches');
    }
};
