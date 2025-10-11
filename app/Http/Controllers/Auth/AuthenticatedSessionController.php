<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\NewAccessToken;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

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

        // Intentar autenticar con las credenciales
        if (!Auth::guard('web')->attempt($credentials, $remember)) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        // Regenerar sesión para prevenir session fixation
        $request->session()->regenerate();

        // Retornar usuario autenticado
        return response()->json([
            'message' => 'Autenticación exitosa',
            'user' => Auth::user()
        ]);
    }

    /* public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    } */

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
