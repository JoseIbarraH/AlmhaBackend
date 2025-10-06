<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Buscar usuario
        $user = User::where('email', $request->email)->first();

        // Verificar credenciales
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $accessTtlSeconds = 60 * 15; // 15 min
        /** @var NewAccessToken $accessTokenObj */
        $accessTokenObj = $user->createToken('access-token');

        $accessToken = $accessTokenObj->accessToken; // modelo PersonalAccessToken
        $accessToken->expires_at = now()->addSeconds($accessTtlSeconds);
        $accessToken->save();

        $plainAccessToken = $accessTokenObj->plainTextToken;

        // 2) Crear refresh token (largo) -> devolvemos el token *plano* al cliente, pero guardamos hash
        $plainRefresh = Str::random(64);
        $refreshHash = hash('sha256', $plainRefresh);

        $refresh = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $refreshHash,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'expires_at' => now()->addDays(30),
        ]);

        // 3) (Recomendado) Setear refresh token en cookie httpOnly
        $cookie = cookie('refresh_token', $plainRefresh, 60 * 24 * 30, '/', null, config('app.env') === 'production', true, false, 'Strict');

        // Devolver respuesta
        return response()->json([
            'user' => $user,
            'access_token' => $plainAccessToken,
            'expires_in' => $accessTtlSeconds,
        ])->withCookie($cookie);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        // revocar access token actual
        $current = $user->currentAccessToken();
        if ($current) {
            $current->delete();
        }

        // revocar refresh tokens del user + borrar cookie
        RefreshToken::where('user_id', $user->id)->update(['revoked_at' => now()]);

        $cookie = cookie()->forget('refresh_token');

        return response()->json(['message' => 'Logged out'])->withCookie($cookie);
    }

}
