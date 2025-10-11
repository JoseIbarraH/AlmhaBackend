<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleFromAcceptLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Accept-Language');
        $available = ['es', 'en'];

        if ($header) {
            $lang = strtolower(substr($header, 0, 2));
            if (in_array($lang, $available)) {
                App::setLocale($lang);
            }
        }

        $response = $next($request);
        $response->headers->set('Content-Language', App::getLocale());
        return $response;
    }
}
