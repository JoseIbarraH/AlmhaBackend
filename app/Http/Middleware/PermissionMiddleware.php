<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permiso)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermission($permiso)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
