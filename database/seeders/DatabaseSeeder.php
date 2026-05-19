<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('');
        $this->command?->info('═══ Rôles & permissions SYSCOHADA ═══');
        $this->call(AccountingRolesPermissionsSeeder::class);

        User::updateOrCreate(
            ['email' => 'demo@gmail.com'],
            [
                'name' => 'Compte Démo',
                'password' => Hash::make('demo@2025'),
                'role' => 'super_admin',
            ]
        )->syncRoles(['super_admin']);

        $admin = config('accounting_roles.default_admin');
        $this->command?->info('');
        $this->command?->info('╔══════════════════════════════════════════╗');
        $this->command?->info('║   SYSCOHADA — Initialisation base        ║');
        $this->command?->info('╚══════════════════════════════════════════╝');
        $this->command?->info('');

        $this->command?->info('① Devises...');
        $this->call(DeviseSeeder::class);

        $this->command?->info('② Paramètres système...');
        $this->call(ParametresSeeder::class);

        $this->command?->info('③ Société démo...');
        $this->call(SocieteDemoSeeder::class);

        $this->command?->info('④ Plan comptable SYSCOHADA...');
        $this->call(PlanComptableSyscohadaSeeder::class);

        $this->command?->info('⑤ Journaux comptables...');
        $this->call(JournauxSeeder::class);

        $this->command?->info('⑥ Workflows demandes de fonds...');
        $this->call(WorkflowDemandeFondsSeeder::class);

        $this->command?->info('');
        $this->command?->info('╔══════════════════════════════════════════╗');
        $this->command?->info('║   ✅ Initialisation terminée             ║');
        $this->command?->info('╚══════════════════════════════════════════╝');
        $this->command?->info('');
        $this->command?->info("Super admin : {$admin['email']} / {$admin['password']}");
        $this->command?->info('Compte démo (super admin) : demo@gmail.com / demo@2025');
        $this->command?->info('Paramètres : /accounting/parametres/societe');
    }
}
