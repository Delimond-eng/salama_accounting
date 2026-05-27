<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facture_lignes') && ! Schema::hasColumn('facture_lignes', 'est_rubrique')) {
            Schema::table('facture_lignes', function (Blueprint $table) {
                $table->boolean('est_rubrique')->default(false)->after('rubrique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('facture_lignes') && Schema::hasColumn('facture_lignes', 'est_rubrique')) {
            Schema::table('facture_lignes', function (Blueprint $table) {
                $table->dropColumn('est_rubrique');
            });
        }
    }
};
