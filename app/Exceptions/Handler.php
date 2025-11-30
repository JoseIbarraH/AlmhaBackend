<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Responses\ApiResponse; // Asumo que esta clase está correctamente importada

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
        // Eliminamos el manejo de AuthenticationException de aquí,
        // ya que ahora se maneja de forma más robusta en el método unauthenticated().

        if ($e instanceof ValidationException) {
            return ApiResponse::success(
                'Errores de validación',
                $e->errors(), // ← trae todos los errores de los campos
                422
            );
        }

        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::success('Recurso no encontrado', null, 404);
        }

        if ($e instanceof AuthorizationException) {
             return ApiResponse::success('No autorizado', null, 403);
        }


        // Manejo de errores generales si no se capturó antes
        return ApiResponse::success(
            'Error interno del servidor',
            ['exception' => $e->getMessage()],
            500
        );
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
