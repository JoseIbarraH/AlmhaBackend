<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;

class PermissionMapperMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $route = $request->route();
        $action = $route->getActionMethod();
        $controller = class_basename($route->getController());

        $module = str_replace('Controller', '', $controller);
        $module = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $module));

        $permission = config("permissions.$module.$action");

        if (!$permission) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {

            return response()->json(['error' => 'No autorizado'], 403);
        }

        $activeRole = $user->roles()->where('status', 'active')->exists();

        if (!$activeRole) {
            return response()->json(['error' => 'Rol inactivo'], 403);
        }

        return $next($request);
    }

}
