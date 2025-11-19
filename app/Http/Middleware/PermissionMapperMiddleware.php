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
            /* Log::warning("🚫 Request sin autenticación", [
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]); */

            return response()->json(['error' => 'No autenticado'], 401);
        }

        $route = $request->route();
        $action = $route->getActionMethod(); // update_teamMember
        $controller = class_basename($route->getController()); // TeamMemberController

        /* Log::info('Info', [$route, $action, $controller]); */

        // Sacar módulo
        $module = str_replace('Controller', '', $controller);
        $module = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $module));

        // Buscar permiso
        $permission = config("permissions.$module.$action");

        // Si NO existe en el mapa de permisos
        if (!$permission) {
            return $next($request);
        }

        /* Log::info("🔍 Permission check initiated", [
            'user_id' => $user->id,
            'roles' => $user->roles->pluck('name'),
            'module' => $module,
            'method' => $action,
            'permission_required' => $permission,
            'route' => $request->path(),
            'http_method' => $request->method(),
        ]); */


        if (!$user->hasPermission($permission)) {

            /* Log::error("⛔ Permission denied", [
                'user_id' => $user->id,
                'user_permissions' => $user->permissions->pluck('name'),
                'missing_permission' => $permission,
                'module' => $module,
                'method' => $action,
            ]); */

            return response()->json(['error' => 'No autorizado'], 403);
        }

        /* Log::info("✅ Permission granted", [
            'user_id' => $user->id,
            'permission' => $permission
        ]); */

        $activeRole = $user->roles()->where('status', 'active')->exists();

        if (!$activeRole) {
            return response()->json(['error' => 'Rol inactivo'], 403);
        }

        return $next($request);
    }

}
