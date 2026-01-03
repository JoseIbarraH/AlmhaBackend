<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Helpers\ApiResponse;

class Handler extends ExceptionHandler
{
    /**
     * Tipos de excepciones que no se reportan.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Campos que no se deben incluir en validaciones.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Convierte la excepción de no-autenticación en una respuesta.
     * Este método anula la lógica predeterminada de Laravel que intenta
     * redirigir a una ruta 'login', previniendo el error "Route [login] not defined.".
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Si es una petición API, retornar JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success(
                'Unauthenticated.',
                'No autenticado',
            );
        }

        // Para peticiones web, redirigir al login (si existe)
        return redirect()->guest(route('login'));
    }

    /**
     * Renderiza una excepción en una respuesta HTTP.
     */
    public function render($request, Throwable $e)
    {
        // Asegurar que toda petición API o que espera JSON responda JSON
        if ($request->is('api/*') || $request->expectsJson()) {

            // 404 - Ruta no encontrada (Laravel normal manda vista)
            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error('Recurso no encontrado', 404);
            }

            // 405 - Método no permitido
            if ($e instanceof MethodNotAllowedHttpException) {
                return ApiResponse::error('Método HTTP no permitido', 405);
            }

            // 422 - Validación
            if ($e instanceof ValidationException) {
                return ApiResponse::error('Errores de validación', $e->errors(), 422);
            }

            // 403 - No autorizado
            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('No autorizado', 403);
            }

            // 401 - No autenticado
            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('No autenticado', 401);
            }

            // Error desconocido
            return ApiResponse::error(
                $e->getMessage(),
                500
            );
        }

        // Si NO es API, usa el comportamiento normal de Laravel
        return parent::render($request, $e);
    }


    /**
     * Registra las devoluciones de llamada de manejo de excepciones para la aplicación.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

}
