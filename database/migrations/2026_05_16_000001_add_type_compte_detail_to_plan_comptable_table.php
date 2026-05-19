<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_comptable', function (Blueprint $table) {
            $table->string('type_compte_detail', 100)->nullable()->after('type_compte');
        });
    }

    public function down(): void
    {
        Schema::table('plan_comptable', function (Blueprint $table) {
            $table->dropColumn('type_compte_detail');
        });
    }
};
