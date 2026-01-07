<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($message = 'Operación exitosa', $data = null, $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message = 'Ocurrió un error inesperado', $errors = null, $code = 400): JsonResponse
    {
        // Si $errors es una excepción, extraemos el mensaje real solo en modo debug
        if ($errors instanceof \Throwable) {
            $errors = config('app.debug')
                ? $errors->getMessage()
                : 'Error interno del servidor';
        }

        if ($errors !== 'Error interno del servidor') {
            \Log::error("API Error: $message", ['context' => $errors]);
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $errors,
        ], $code);
    }
}
