<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Point d'entrée principal
 *
 * Ordre d'exécution OBLIGATOIRE (dépendances entre seeders) :
 *   1. DeviseSeeder       — pas de dépendance
 *   2. ParametresSeeder   — pas de dépendance
 *   3. RolesPermissionsSeeder — dépend de la table users (Laravel défaut)
 *   4. JournauxSeeder     — dépend de societes (lancé après création société)
 *
 * Usage :
 *   php artisan db:seed                        → Tout lancer
 *   php artisan db:seed --class=DeviseSeeder   → Un seeder spécifique
 *   php artisan migrate --seed                 → Migrate + seed en une commande
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
       
    }
}
