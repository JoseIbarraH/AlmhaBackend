<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromAcceptLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = config('languages.supported'); // Idiomas soportados
        $defaultLocale = 'es';

        // Prioridad 1: Header Accept-Language
        $lang = $request->header('Accept-Language');

        // Prioridad 2: Query parameter ?lang=es
        if (!$lang) {
            $lang = $request->query('lang');
        }

        // Prioridad 3: Header personalizado X-Locale
        if (!$lang) {
            $lang = $request->header('X-Locale');
        }

        // Extraer solo los primeros 2 caracteres (es-ES → es)
        if ($lang) {
            $lang = substr($lang, 0, 2);

            if (in_array($lang, $available)) {
                App::setLocale($lang);
            } else {
                App::setLocale($defaultLocale);
            }
        } else {
            // Si no hay idioma, usar el default
            App::setLocale($defaultLocale);
        }

        $response = $next($request);
        $response->headers->set('Content-Language', App::getLocale());

        return $response;
    }
}
