<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;


class PasswordController extends Controller
{
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed']
            ]);

            $request->user()->update([
                'password' => Hash::make($validated['password'])
            ]);

            return ApiResponse::success(
                message: __('messages.profile.success.updatePassword'),
                code: 200
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                message: __('messages.profile.error.updatePassword'),
                code: 500
            );
        }

    }
}
