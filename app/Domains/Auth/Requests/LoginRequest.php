<?php

namespace App\Domains\Auth\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember' => ['boolean'],
        ];
    }

    public function authenticate(): void
    {
        if (!Auth::guard('web')->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}

