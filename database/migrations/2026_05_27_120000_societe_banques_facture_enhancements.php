<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('societes') && ! Schema::hasColumn('societes', 'identification_nationale')) {
            Schema::table('societes', function (Blueprint $table) {
                $table->string('identification_nationale', 100)->nullable()->after('num_contribuable');
            });
        }

        if (! Schema::hasTable('societe_banques')) {
            Schema::create('societe_banques', function (Blueprint $table) {
                $table->id();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->string('banque', 150);
                $table->string('numero_compte', 80);
                $table->string('devise', 3)->default('CDF');
                $table->boolean('est_defaut')->default(false);
                $table->unsignedSmallInteger('ordre')->default(0);
                $table->timestamps();
                $table->index(['societe_id', 'ordre']);
            });
        }

        if (Schema::hasTable('mouvements_stock') && ! Schema::hasColumn('mouvements_stock', 'numero')) {
            Schema::table('mouvements_stock', function (Blueprint $table) {
                $table->string('numero', 40)->nullable()->after('id');
            });
        }

        if (Schema::hasTable('facture_lignes') && ! Schema::hasColumn('facture_lignes', 'rubrique')) {
            Schema::table('facture_lignes', function (Blueprint $table) {
                $table->string('rubrique', 120)->nullable()->after('libelle');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('facture_lignes') && Schema::hasColumn('facture_lignes', 'rubrique')) {
            Schema::table('facture_lignes', function (Blueprint $table) {
                $table->dropColumn('rubrique');
            });
        }

        if (Schema::hasTable('mouvements_stock') && Schema::hasColumn('mouvements_stock', 'numero')) {
            Schema::table('mouvements_stock', function (Blueprint $table) {
                $table->dropColumn('numero');
            });
        }

        Schema::dropIfExists('societe_banques');

        if (Schema::hasTable('societes') && Schema::hasColumn('societes', 'identification_nationale')) {
            Schema::table('societes', function (Blueprint $table) {
                $table->dropColumn('identification_nationale');
            });
        }
    }
};
