<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
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
    public function destroy(Request $request)
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


}
