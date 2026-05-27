<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            if (! Schema::hasColumn('produits', 'type_article')) {
                $table->string('type_article', 20)->default('produit')->after('libelle');
            }
            if (! Schema::hasColumn('produits', 'unite')) {
                $table->string('unite', 20)->default('U')->after('libelle');
            }
            if (! Schema::hasColumn('produits', 'prix_unitaire_cdf')) {
                $table->decimal('prix_unitaire_cdf', 18, 2)->default(0)->after('prix_unitaire');
            }
            if (! Schema::hasColumn('produits', 'prix_unitaire_usd')) {
                $table->decimal('prix_unitaire_usd', 18, 4)->default(0)->after('prix_unitaire');
            }
            if (! Schema::hasColumn('produits', 'gestion_stock')) {
                $table->boolean('gestion_stock')->default(false)->after('actif');
            }
            if (! Schema::hasColumn('produits', 'stock_actuel')) {
                $table->decimal('stock_actuel', 18, 4)->default(0)->after('gestion_stock');
            }
            if (! Schema::hasColumn('produits', 'stock_minimum')) {
                $table->decimal('stock_minimum', 18, 4)->default(0)->after('stock_actuel');
            }
        });

        if (Schema::hasColumn('produits', 'prix_unitaire_cdf')) {
            DB::table('produits')->whereNull('prix_unitaire_cdf')->orWhere('prix_unitaire_cdf', 0)
                ->update(['prix_unitaire_cdf' => DB::raw('prix_unitaire')]);
        }

        if (! Schema::hasTable('entrepots')) {
            Schema::create('entrepots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->string('code', 20);
                $table->string('libelle');
                $table->string('adresse')->nullable();
                $table->boolean('actif')->default(true);
                $table->timestamps();
                $table->unique(['societe_id', 'code']);
            });
        }

        if (! Schema::hasTable('bons_commande')) {
            Schema::create('bons_commande', function (Blueprint $table) {
                $table->id();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->foreignId('tiers_id')->constrained('tiers');
                $table->string('numero', 40);
                $table->date('date_commande');
                $table->date('date_livraison_prevue')->nullable();
                $table->string('statut', 20)->default('brouillon');
                $table->string('devise', 3)->default('CDF');
                $table->decimal('montant_ht', 18, 2)->default(0);
                $table->decimal('montant_ttc', 18, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['societe_id', 'numero']);
            });
        }

        if (! Schema::hasTable('bon_commande_lignes')) {
            Schema::create('bon_commande_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bon_commande_id')->constrained('bons_commande')->cascadeOnDelete();
                $table->foreignId('produit_id')->nullable()->constrained('produits')->nullOnDelete();
                $table->unsignedSmallInteger('ordre')->default(1);
                $table->string('libelle');
                $table->decimal('quantite', 18, 4)->default(1);
                $table->decimal('prix_unitaire', 18, 4)->default(0);
                $table->decimal('montant_ht', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bons_livraison')) {
            Schema::create('bons_livraison', function (Blueprint $table) {
                $table->id();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->foreignId('bon_commande_id')->nullable()->constrained('bons_commande')->nullOnDelete();
                $table->foreignId('tiers_id')->constrained('tiers');
                $table->foreignId('entrepot_id')->nullable()->constrained('entrepots')->nullOnDelete();
                $table->string('numero', 40);
                $table->date('date_livraison');
                $table->string('statut', 20)->default('brouillon');
                $table->string('devise', 3)->default('CDF');
                $table->text('notes')->nullable();
                $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['societe_id', 'numero']);
            });
        }

        if (! Schema::hasTable('bon_livraison_lignes')) {
            Schema::create('bon_livraison_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bon_livraison_id')->constrained('bons_livraison')->cascadeOnDelete();
                $table->foreignId('produit_id')->nullable()->constrained('produits')->nullOnDelete();
                $table->unsignedSmallInteger('ordre')->default(1);
                $table->string('libelle');
                $table->decimal('quantite', 18, 4)->default(1);
                $table->decimal('prix_unitaire', 18, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mouvements_stock')) {
            Schema::create('mouvements_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();
                $table->foreignId('produit_id')->constrained('produits');
                $table->foreignId('entrepot_id')->nullable()->constrained('entrepots')->nullOnDelete();
                $table->string('type_mouvement', 20);
                $table->decimal('quantite', 18, 4);
                $table->decimal('stock_avant', 18, 4)->default(0);
                $table->decimal('stock_apres', 18, 4)->default(0);
                $table->string('reference_type', 40)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('libelle');
                $table->date('date_mouvement');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['societe_id', 'produit_id', 'date_mouvement']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
        Schema::dropIfExists('bon_livraison_lignes');
        Schema::dropIfExists('bons_livraison');
        Schema::dropIfExists('bon_commande_lignes');
        Schema::dropIfExists('bons_commande');
        Schema::dropIfExists('entrepots');

        Schema::table('produits', function (Blueprint $table) {
            foreach (['type_article', 'unite', 'prix_unitaire_cdf', 'prix_unitaire_usd', 'gestion_stock', 'stock_actuel', 'stock_minimum'] as $col) {
                if (Schema::hasColumn('produits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
