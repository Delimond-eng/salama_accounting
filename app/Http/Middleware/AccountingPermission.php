<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountingPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $routeName = $request->route()?->getName();
        $permission = $this->resolvePermission($routeName);

        if ($permission === null) {
            return $next($request);
        }

        if (! $user->can($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['Accès refusé. Permission requise : '.$permission]], 403);
            }
            abort(403, 'Vous n\'avez pas accès à cette fonctionnalité.');
        }

        return $next($request);
    }

    private function resolvePermission(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $routes = config('accounting_route_permissions.routes', []);
        if (array_key_exists($routeName, $routes)) {
            return $routes[$routeName];
        }

        foreach (config('accounting_route_permissions.prefixes', []) as $prefix => $permission) {
            if (str_starts_with($routeName, $prefix)) {
                return $permission;
            }
        }

        return null;
    }
}
