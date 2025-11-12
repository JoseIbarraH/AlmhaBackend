<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Responses\ApiResponse;

use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean'
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Intentar autenticar con el guard correcto
        if (!Auth::guard('web')->attempt($credentials, $remember)) {
            return ApiResponse::error(
                message: 'Credenciales inválidas',
                code: 401
            );
        }

        // Regenerar sesión para prevenir session fixation
        $request->session()->regenerate();

        // Cargar usuario con roles y permisos
        $user = Auth::guard('web')->user()->load('roles.permissions');

        // Validar si el usuario está activo
        if ($user->status !== 'active') {
            Auth::guard('web')->logout(); // usar el guard correcto

            return ApiResponse::error(
                message: 'Este usuario se encuentra deshabilitado. Por favor contacte al administrador.',
                code: 403
            );
        }

        // Retornar usuario autenticado con ApiResponse
        return ApiResponse::success(
            'Autenticación exitosa',
            $user
        );
    }



    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        // No regenerar token, ni devolver nada que use la sesión
        return response()->json([
            'message' => 'Sesión cerrada completamente',
        ])->withoutCookie(cookie()->forget(config('session.cookie')));
    }


}
