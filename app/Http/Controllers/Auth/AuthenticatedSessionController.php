<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{

    public function store(Request $request)
    {
        DB::beginTransaction();

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'boolean'
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::guard('web')->attempt($credentials, $remember)) {
            DB::rollBack();
            return ApiResponse::error(
                message: 'Credenciales inválidas',
                code: 401
            );
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user()->load('roles.permissions');

        if ($user->status !== 'active') {
            Auth::guard('web')->logout();
            DB::rollBack();
            return ApiResponse::error(
                message: 'Este usuario se encuentra deshabilitado. Por favor contacte al administrador.',
                code: 403
            );
        }
        DB::commit();
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
