<?php

namespace App\Domains\Auth\Controllers;

use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;


class PasswordController extends Controller
{
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);

            return ApiResponse::success(
                message: __('messages.profile.success.updatePassword'),
                code: 200
            );

        } catch (ValidationException $e) {
            // Captura específica de errores de validación
            $errors = $e->validator->errors()->all();

            return ApiResponse::error(
                message: $errors[0] ?? __('messages.profile.error.updatePassword'),
                errors: $errors[0],
                code: 422
            );

        } catch (\Throwable $e) {
            // Cualquier otro error inesperado
            Log::error("Password Error: ", [$e]);

            return ApiResponse::error(
                message: __('messages.profile.error.updatePassword'),
                code: 500
            );
        }
    }
}
