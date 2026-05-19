<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function getActions()
    {
        $actions = [];
        $actionLabels = config('accounting_permissions.action_labels', []);

        foreach ((array) config('actions') as $item) {
            $entity = (string) ($item['entity'] ?? '');
            $entityActions = [];

            foreach ((array) ($item['actions'] ?? []) as $actionName) {
                $actionName = (string) $actionName;
                if ($entity === '' || $actionName === '') {
                    continue;
                }

                $entityActions[] = [
                    'name' => "{$entity}.{$actionName}",
                    'action' => $actionName,
                    'label' => $actionLabels[$actionName] ?? $this->translateAction($actionName),
                    'entity' => $entity,
                ];
            }

            $actions[] = [
                'entity' => $entity,
                'label' => (string) ($item['label'] ?? $entity),
                'actions' => $entityActions,
            ];
        }

        return response()->json([
            'modules' => $actions,
            'role_labels' => config('accounting_roles.labels', []),
            'role_descriptions' => config('accounting_roles.descriptions', []),
            'protected_roles' => config('accounting_roles.protected', []),
            'permission_columns' => ['view', 'create', 'update', 'validate', 'delete', 'export', 'process'],
        ]);
    }

    private function translateAction(string $action): string
    {
        return config('accounting_permissions.action_labels')[$action] ?? ucfirst($action);
    }

    private function roleHasFullAccess(string $roleName): bool
    {
        return in_array($roleName, config('accounting_roles.full_access', ['super_admin']), true)
            || in_array($roleName, ['admin', 'manager'], true);
    }

    public function createOrUpdateRole(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'permissions' => 'required|array',
                'role_id' => 'nullable|exists:roles,id',
            ]);

            DB::beginTransaction();

            if (!empty($data['role_id'])) {
                $role = Role::findOrFail($data['role_id']);
                if (in_array($role->name, config('accounting_roles.protected', []), true)) {
                    return response()->json(['errors' => 'Ce rôle système ne peut pas être modifié.'], 403);
                }
                $role->update([
                    'name' => $data['name'],
                ]);
            } else {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web',
                ]);
            }

            $permissionNames = [];
            $roleName = Str::lower((string) ($role->name ?? $data['name']));
            if ($this->roleHasFullAccess($roleName)) {
                $permissionNames = $this->allPermissionNamesFromActions();
            } else {
                foreach ((array) $data['permissions'] as $permission) {
                    $permission = trim((string) $permission);
                    if ($permission === '') {
                        continue;
                    }

                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                    $permissionNames[] = $permission;
                }

                $permissionNames = array_values(array_unique($permissionNames));
            }

            $role->syncPermissions($permissionNames);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'message' => !empty($data['role_id']) ? 'Role mis a jour avec succes' : 'Role cree avec succes',
                'role' => $role,
                'permissions' => $permissionNames,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function getAllRoles()
    {
        $labels = config('accounting_roles.labels', []);
        $descriptions = config('accounting_roles.descriptions', []);
        $roles = Role::with('permissions')->get()->map(function ($role) use ($labels, $descriptions) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $labels[$role->name] ?? $role->name,
                'description' => $descriptions[$role->name] ?? '',
                'permissions' => $role->permissions->pluck('name'),
                'permissions_count' => $role->permissions->count(),
                'created_at' => DateTimeFormat::format($role->created_at),
                'updated_at' => DateTimeFormat::format($role->updated_at),
            ];
        });

        return response()->json([
            'status' => 'success',
            'roles' => $roles,
            'role_labels' => $labels,
            'protected_roles' => config('accounting_roles.protected', []),
        ]);
    }

    public function getAllUsers()
    {
        $labels = config('accounting_roles.labels', []);
        $users = User::query()
            ->with(['roles.permissions', 'permissions'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($labels) {
                $roleName = $user->roles->first()?->name ?? $user->role;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $roleName,
                    'role_label' => $labels[$roleName] ?? $roleName,
                    'roles' => $user->roles->map(fn ($r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                        'permissions' => $r->permissions->pluck('name'),
                    ]),
                    'permissions' => $user->permissions->pluck('name'),
                    'created_at' => DateTimeFormat::format($user->created_at),
                    'updated_at' => DateTimeFormat::format($user->updated_at),
                ];
            });

        return response()->json([
            'status' => 'success',
            'users' => $users,
            'role_labels' => $labels,
        ]);
    }

    public function createOrUpdateUser(Request $request)
    {
        try {
            $userId = $request->user_id;

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,'.$userId,
                'password' => $userId ? 'nullable|string|min:6' : 'required|string|min:6',
                'role' => 'required|string|exists:roles,name',
                'user_id' => 'nullable|exists:users,id',
                'permissions' => 'nullable|array',
            ]);

            DB::beginTransaction();

            if ($userId) {
                $user = User::findOrFail($userId);

                $updateData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ];
                if (!empty($data['password'])) {
                    $updateData['password'] = Hash::make($data['password']);
                }
                $user->update($updateData);
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'password' => Hash::make($data['password']),
                ]);
            }

            $newRole = (string) $data['role'];

            if ($userId) {
                $wasSuper = $user->hasRole('super_admin');
                if ($wasSuper && $newRole !== 'super_admin') {
                    $count = User::role('super_admin')->count();
                    if ($count <= 1) {
                        DB::rollBack();

                        return response()->json([
                            'errors' => 'Impossible de modifier le rôle : seul super administrateur du système.',
                        ]);
                    }
                }
            }

            $user->syncRoles([$newRole]);
            $user->role = $newRole;
            $user->save();

            if (! array_key_exists('permissions', $data)) {
                $user->syncPermissions([]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            } elseif (! empty($data['permissions'])) {
                $permissionNames = [];
                foreach ((array) $data['permissions'] as $permission) {
                    $permission = trim((string) $permission);
                    if ($permission === '') {
                        continue;
                    }

                    $permissionNames[] = $permission;
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                }

                if ($permissionNames !== []) {
                    $user->syncPermissions(array_values(array_unique($permissionNames)));
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                }
            }

            DB::commit();

            return response()->json([
                'message' => $userId ? 'Utilisateur mis a jour avec succes' : 'Utilisateur cree avec succes',
                'user' => $user->load(['roles']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function attributeAccess(Request $request)
    {
        try {
            $userId = (int) $request->user_id;

            $validated = $request->validate([
                'user_id' => 'required|int|exists:users,id',
                'permissions' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $user = User::findOrFail($userId);
            if ($user->hasRole('super_admin')) {
                DB::rollBack();

                return response()->json(['errors' => 'Les permissions du super administrateur ne peuvent pas être modifiées individuellement.'], 403);
            }

            if (array_key_exists('permissions', $validated)) {
                $permissionNames = [];
                foreach ((array) ($validated['permissions'] ?? []) as $permission) {
                    $permission = trim((string) $permission);
                    if ($permission === '') {
                        continue;
                    }

                    $permissionNames[] = $permission;
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                }

                $user->syncPermissions(array_values(array_unique($permissionNames)));
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur mis a jour avec succes',
                'user' => $user->load(['roles', 'permissions']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    private function allPermissionNamesFromActions(): array
    {
        $modules = config('actions', []);
        $permissionNames = [];

        foreach ((array) $modules as $key => $moduleConfig) {
            if (!is_array($moduleConfig)) {
                continue;
            }

            $entity = trim((string) ($moduleConfig['entity'] ?? $key));
            if ($entity === '') {
                continue;
            }

            foreach ((array) ($moduleConfig['actions'] ?? []) as $action) {
                $action = trim((string) $action);
                if ($action === '') {
                    continue;
                }

                $name = $entity . '.' . $action;
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
                $permissionNames[] = $name;
            }
        }

        return array_values(array_unique($permissionNames));
    }

}
