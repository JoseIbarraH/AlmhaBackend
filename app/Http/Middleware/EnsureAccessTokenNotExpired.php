<?php

// app/Http/Middleware/EnsureAccessTokenNotExpired.php
namespace App\Http\Middleware;

use Closure;

class EnsureAccessTokenNotExpired
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // currentAccessToken can be null if auth failed; guard earlier ensures auth
        $token = $user->currentAccessToken();
        if ($token && $token->expires_at && now()->gt($token->expires_at)) {
            // Token expirado
            return response()->json(['message' => 'Access token expired'], 401);
        }

        return $next($request);
    }
}

