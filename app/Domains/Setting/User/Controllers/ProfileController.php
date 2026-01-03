<?php

namespace App\Domains\Setting\User\Controllers;

use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update the user's profile information.
     */
    public function update_account(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            ]);

            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return ApiResponse::success(
                __('messages.profile.infoProfile'),
                $user
            );
        } catch (\Throwable $e) {
            Log::error('Error al actualizar perfil', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                __('messages.profile.updateError') ?? 'Ocurrió un error al actualizar el perfil.',
                500
            );
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy_account(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

            Auth::guard('web')->logout();

            $user->update([
                'status' => 'inactive',
            ]);

            $user->tokens()->delete();

            // Invalidar sesión
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            DB::commit();

            return ApiResponse::success(
                message: __('messages.profile.success.deactivateAccount'),
                code: 200
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            \Log::error("❌ Deactivate User Error 1:", [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error(
                message: __('messages.profile.error.invalidPassword'),
                code: 422,
                errors: $e->errors()
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("❌ Deactivate User Error 2:", [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiResponse::error(
                message: __('messages.profile.error.deactivateAccount'),
                code: 500
            );
        }
    }

    public function change_password(Request $request)
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
