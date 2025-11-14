<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next, $permissionCode)
    {
        $user = $request->user();

        Log::info('Middleware CheckPermission iniciado', [
            'user_id' => $user?->id,
            'permission_required' => $permissionCode
        ]);

        if (!$user) {
            Log::warning('Usuario no autenticado');
            return response()->json(['error' => 'No autenticado'], 401);
        }

        Log::info('Roles del usuario', [
            'roles' => $user->roles->pluck('code'),
            'statuses' => $user->roles->pluck('status')
        ]);

        if ($user->roles->isEmpty()) {
            Log::warning('Usuario sin roles asignados', ['user_id' => $user->id]);
            return response()->json(['error' => 'Tu cuenta no tiene roles asignados'], 403);
        }

        // ¿Tiene algún rol activo?
        $hasActiveRole = $user->roles->contains('status', 'active');
        Log::info('¿Tiene rol activo?', ['result' => $hasActiveRole]);

        if (!$hasActiveRole) {
            Log::warning('Usuario sin roles activos', ['user_id' => $user->id]);
            return response()->json(['error' => 'Todos tus roles están desactivados'], 403);
        }

        // Verificar permiso
        $hasPermission = $user->hasPermission($permissionCode);
        Log::info('¿Usuario tiene el permiso requerido?', [
            'result' => $hasPermission,
            'permission' => $permissionCode
        ]);

        if (!$hasPermission) {
            Log::warning('Permiso denegado', [
                'user_id' => $user->id,
                'permission_required' => $permissionCode
            ]);

            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('Acceso concedido', [
            'user_id' => $user->id,
            'permission' => $permissionCode
        ]);

        return $next($request);
    }



}
