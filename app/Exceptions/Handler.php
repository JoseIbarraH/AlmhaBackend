<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Responses\ApiResponse;

class Handler extends ExceptionHandler
{
    /**
     * Tipos de excepciones que no se reportan.
     */
    protected $dontReport = [
        //
    ];

    /**
     * Campos que no se deben incluir en validaciones.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Renderiza una excepción en una respuesta HTTP.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                'Errores de validación',
                $e->errors(), // ← trae todos los errores de los campos
                422
            );
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('No autenticado', null, 401);
        }

        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::error('Recurso no encontrado', null, 404);
        }

        return ApiResponse::error(
            'Error interno del servidor',
            ['exception' => $e->getMessage()],
            500
        );
    }

}
