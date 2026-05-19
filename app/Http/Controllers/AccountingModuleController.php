<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AccountingModuleController extends Controller
{
    public function show(string $module): View|RedirectResponse
    {
        $modules = config('accounting_menu.modules', []);
        $modulePermissions = config('accounting_route_permissions.modules', []);

        if (! isset($modules[$module])) {
            abort(404);
        }

        $permission = $modules[$module]['permission']
            ?? ($modulePermissions[$module] ?? "{$module}.view");

        if ($module === 'dashboard') {
            return redirect()->route('dashboard');
        }

        if (! auth()->user()?->can($permission)) {
            abort(403, 'Vous n\'avez pas accès à ce module.');
        }

        $moduleData = $modules[$module];
        $items = collect($moduleData['items'] ?? [])
            ->filter(fn (array $item) => $this->canAccessItem($item, $module))
            ->map(fn (array $item) => $this->hydrateItem($item))
            ->values()
            ->all();

        return view('accounting.module-hub', [
            'moduleKey' => $module,
            'module' => $moduleData,
            'items' => $items,
        ]);
    }

    public function placeholder(string $slug): View
    {
        if (! auth()->user()?->can('parametres.view')) {
            abort(403);
        }

        $label = str($slug)->replace('-', ' ')->title()->toString();

        return view('accounting.placeholder', [
            'slug' => $slug,
            'label' => $label,
        ]);
    }

    private function canAccessItem(array $item, string $moduleKey): bool
    {
        $permission = $this->resolveItemPermission($item, $moduleKey);

        return auth()->user()?->can($permission) ?? false;
    }

    private function resolveItemPermission(array $item, string $moduleKey): string
    {
        if (! empty($item['permission'])) {
            return $item['permission'];
        }

        $routeName = (string) ($item['route'] ?? '');

        $routeMap = [
            'dashboard' => 'dashboard.view',
            'accounting.saisie.' => 'saisie.view',
            'accounting.livres.' => 'livres.view',
            'accounting.etats.' => 'etats.view',
            'accounting.fiscalite.' => 'fiscalite.view',
            'accounting.exercices.' => 'exercices.view',
            'accounting.parametres.' => 'parametres.view',
            'admin.users' => 'users.view',
            'admin.roles' => 'roles.view',
        ];

        foreach ($routeMap as $prefix => $perm) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return $perm;
            }
        }

        return "{$moduleKey}.view";
    }

    private function hydrateItem(array $item): array
    {
        $params = $item['params'] ?? [];
        $routeName = $item['route'] ?? null;

        $item['url'] = ($routeName && Route::has($routeName))
            ? route($routeName, $params)
            : '#';

        $item['coming_soon'] = $routeName === 'accounting.placeholder';

        return $item;
    }
}
