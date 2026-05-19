<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccountingRolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = $this->syncAllPermissions();

        $map = $this->rolePermissionMap();

        foreach ($map as $roleName => $perms) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                []
            );
            $resolved = $perms === '*' ? $all : array_values(array_intersect($perms, $all));
            $role->syncPermissions($resolved);
        }

        $admin = config('accounting_roles.default_admin');
        $user = User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => Hash::make($admin['password']),
                'role' => $admin['role'],
            ]
        );
        $user->syncRoles([$admin['role']]);

        $demo = User::updateOrCreate(
            ['email' => 'demo@gmail.com'],
            [
                'name' => 'Compte Démo',
                'password' => Hash::make('demo@2025'),
                'role' => 'super_admin',
            ]
        );
        $demo->syncRoles(['super_admin']);

        $this->command?->info('Rôles SYSCOHADA : '.count($map).' rôles, '.count($all).' permissions.');
        $this->command?->info("Super admin : {$admin['email']} / {$admin['password']}");
    }

    /** @return array<int, string> */
    protected function syncAllPermissions(): array
    {
        $names = [];
        foreach (config('actions', []) as $key => $module) {
            if (! is_array($module)) {
                continue;
            }
            $entity = $module['entity'] ?? $key;
            foreach ((array) ($module['actions'] ?? []) as $action) {
                $name = "{$entity}.{$action}";
                Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web'], []);
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /** @return array<string, array<int, string>|string> */
    protected function rolePermissionMap(): array
    {
        $v = fn (string $e, array $a) => array_map(fn ($x) => "{$e}.{$x}", $a);

        $lectureEtats = [
            ...$v('dashboard', ['view']),
            ...$v('livres', ['view']),
            ...$v('etats', ['view', 'export']),
            ...$v('saisie', ['view']),
            ...$v('tresorerie', ['view']),
            ...$v('exercices', ['view']),
            ...$v('fiscalite', ['view']),
            ...$v('facturation', ['view']),
        ];

        $comptableBase = [
            ...$v('dashboard', ['view']),
            ...$v('saisie', ['view', 'create', 'update', 'validate']),
            ...$v('livres', ['view', 'export']),
            ...$v('etats', ['view', 'export']),
            ...$v('tresorerie', ['view']),
            ...$v('exercices', ['view']),
            ...$v('parametres', ['view']),
            ...$v('fiscalite', ['view']),
            ...$v('facturation', ['view', 'create', 'update', 'validate', 'export']),
        ];

        return [
            'super_admin' => '*',

            'admin_comptable' => [
                ...$v('dashboard', ['view']),
                ...$v('saisie', ['view', 'create', 'update', 'validate', 'delete']),
                ...$v('livres', ['view', 'export']),
                ...$v('etats', ['view', 'export']),
                ...$v('tresorerie', ['view', 'create', 'update', 'export']),
                ...$v('exercices', ['view', 'create', 'update', 'process']),
                ...$v('parametres', ['view', 'create', 'update']),
                ...$v('fiscalite', ['view', 'export', 'process']),
                ...$v('facturation', ['view', 'create', 'update', 'validate', 'delete', 'export', 'process']),
                ...$v('audit', ['view']),
            ],

            'comptable' => $comptableBase,

            'caissier' => [
                ...$v('dashboard', ['view']),
                ...$v('saisie', ['view', 'create', 'update']),
                ...$v('tresorerie', ['view', 'create']),
                ...$v('livres', ['view']),
                ...$v('facturation', ['view', 'create', 'validate', 'process']),
            ],

            'tresorier' => [
                ...$v('dashboard', ['view']),
                ...$v('saisie', ['view', 'create', 'update', 'validate']),
                ...$v('tresorerie', ['view', 'create', 'update', 'export']),
                ...$v('livres', ['view', 'export']),
                ...$v('etats', ['view']),
                ...$v('facturation', ['view', 'create', 'validate', 'process', 'export']),
            ],

            'auditeur' => [
                ...$lectureEtats,
                ...$v('audit', ['view']),
                ...$v('parametres', ['view']),
            ],

            'direction' => [
                ...$v('dashboard', ['view']),
                ...$v('etats', ['view', 'export']),
                ...$v('tresorerie', ['view']),
                ...$v('exercices', ['view']),
                ...$v('livres', ['view']),
            ],
        ];
    }
}
