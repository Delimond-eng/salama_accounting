<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journaux', function (Blueprint $table) {
            if (! Schema::hasColumn('journaux', 'analytique_obligatoire')) {
                $table->boolean('analytique_obligatoire')->default(false)->after('saisie_tiers_obligatoire');
            }
        });

        Schema::table('axes_analytiques', function (Blueprint $table) {
            if (! Schema::hasColumn('axes_analytiques', 'ordre_affichage')) {
                $table->unsignedSmallInteger('ordre_affichage')->default(10)->after('actif');
            }
        });

        if (! Schema::hasTable('plan_comptable_axes')) {
            Schema::create('plan_comptable_axes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->foreignId('plan_comptable_id')->constrained('plan_comptable')->cascadeOnDelete();
                $table->foreignId('axe_analytique_id')->constrained('axes_analytiques')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['plan_comptable_id', 'axe_analytique_id'], 'pca_compte_axe_unique');
            });
        }

        if (! Schema::hasTable('lignes_ecritures_analytiques')) {
            Schema::create('lignes_ecritures_analytiques', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ligne_ecriture_id')->constrained('lignes_ecritures')->cascadeOnDelete();
                $table->foreignId('ecriture_id')->constrained('ecritures')->cascadeOnDelete();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->foreignId('exercice_id')->constrained('exercices');
                $table->foreignId('journal_id')->constrained('journaux');
                $table->foreignId('axe_analytique_id')->constrained('axes_analytiques')->restrictOnDelete();
                $table->foreignId('section_analytique_id')->constrained('sections_analytiques')->restrictOnDelete();
                $table->decimal('montant', 15, 2)->nullable();
                $table->decimal('pourcentage', 5, 2)->nullable();
                $table->timestamps();
                $table->unique(['ligne_ecriture_id', 'axe_analytique_id'], 'lea_ligne_axe_unique');
                $table->index(['societe_id', 'exercice_id', 'section_analytique_id'], 'lea_societe_exercice_section');
            });
        }

        Schema::table('factures', function (Blueprint $table) {
            if (! Schema::hasColumn('factures', 'section_analytique_id')) {
                $table->foreignId('section_analytique_id')->nullable()->after('tiers_id')
                    ->constrained('sections_analytiques')->nullOnDelete();
            }
        });

        Schema::table('facture_lignes', function (Blueprint $table) {
            if (! Schema::hasColumn('facture_lignes', 'section_analytique_id')) {
                $table->foreignId('section_analytique_id')->nullable()->after('compte_comptable')
                    ->constrained('sections_analytiques')->nullOnDelete();
            }
        });

        Schema::table('demandes_fonds', function (Blueprint $table) {
            if (! Schema::hasColumn('demandes_fonds', 'section_analytique_id')) {
                $table->foreignId('section_analytique_id')->nullable()->after('journal_id')
                    ->constrained('sections_analytiques')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('demandes_fonds', function (Blueprint $table) {
            if (Schema::hasColumn('demandes_fonds', 'section_analytique_id')) {
                $table->dropConstrainedForeignId('section_analytique_id');
            }
        });
        Schema::table('facture_lignes', function (Blueprint $table) {
            if (Schema::hasColumn('facture_lignes', 'section_analytique_id')) {
                $table->dropConstrainedForeignId('section_analytique_id');
            }
        });
        Schema::table('factures', function (Blueprint $table) {
            if (Schema::hasColumn('factures', 'section_analytique_id')) {
                $table->dropConstrainedForeignId('section_analytique_id');
            }
        });
        Schema::dropIfExists('lignes_ecritures_analytiques');
        Schema::dropIfExists('plan_comptable_axes');
        Schema::table('axes_analytiques', function (Blueprint $table) {
            if (Schema::hasColumn('axes_analytiques', 'ordre_affichage')) {
                $table->dropColumn('ordre_affichage');
            }
        });
        Schema::table('journaux', function (Blueprint $table) {
            if (Schema::hasColumn('journaux', 'analytique_obligatoire')) {
                $table->dropColumn('analytique_obligatoire');
            }
        });
    }
};
