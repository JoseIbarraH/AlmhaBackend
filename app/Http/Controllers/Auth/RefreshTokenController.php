<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RefreshToken;
use Illuminate\Support\Str;

class RefreshTokenController extends Controller
{
    //

    public function refreshToken(Request $request)
    {
        $plainRefresh = $request->cookie('refresh_token');
        if (!$plainRefresh) {
            return response()->json(['message' => 'No refresh token'], 401);
        }

        $hash = hash('sha256', $plainRefresh);

        $refresh = RefreshToken::where('token_hash', $hash)->first();

        if (!$refresh || $refresh->isRevoked() || $refresh->isExpired()) {
            return response()->json(['message' => 'Refresh token inválido'], 401);
        }

        $user = $refresh->user;

        $refresh->revoked_at = now();
        $refresh->save();

        $newPlainRefresh = Str::random(64);
        $newHash = hash('sha256', $newPlainRefresh);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $newHash,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'expires_at' => now()->addDays(30),
        ]);

        // crear nuevo access token
        $accessTtlSeconds = 60 * 15;
        $newAccessTokenObj = $user->createToken('access-token');
        $newAccessToken = $newAccessTokenObj->accessToken;
        $newAccessToken->expires_at = now()->addSeconds($accessTtlSeconds);
        $newAccessToken->save();

        $cookie = cookie('refresh_token', $newPlainRefresh, 60 * 24 * 30, '/', null, config('app.env') === 'production', true, false, 'Strict');

        return response()->json([
            'access_token' => $newAccessTokenObj->plainTextToken,
            'expires_in' => $accessTtlSeconds,
        ])->withCookie($cookie);
    }
}
