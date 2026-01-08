<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;

use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{

    public function store(LoginRequest $request)
    {
        DB::beginTransaction();

        try {
            $request->authenticate();
            $request->session()->regenerate();

            $user = Auth::guard('web')->user()->load('roles.permissions');

            if ($user->status !== 'active') {
                Auth::guard('web')->logout();

                return ApiResponse::error(
                    'Este usuario se encuentra deshabilitado. Por favor contacte al administrador.',
                    403
                );
            }

            DB::commit();
            return ApiResponse::success(
                'Autenticación exitosa',
                $user
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                'Credenciales inválidas',
                401
            );
        }
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
