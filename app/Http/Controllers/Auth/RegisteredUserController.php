<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,code'], // usamos el código interno
        ]);

        // 🧱 Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 🧩 Asignar roles (si vienen)
        if ($request->filled('roles')) {
            $roles = Role::whereIn('code', $request->roles)->pluck('id');
            $user->roles()->sync($roles);
        } else {
            // Rol por defecto (opcional)
            $defaultRole = Role::where('code', 'user')->first();
            if ($defaultRole) {
                $user->rols()->attach($defaultRole->id);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }


    /* public function store(Request $request): Response
    {
        $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class], 'password' => ['required', 'confirmed', Rules\Password::defaults()],]);
        $user = User::create(['name' => $request->name, 'email' => $request->email, 'password' => Hash::make($request->string('password')),]);
        event(new Registered($user));
        Auth::login($user);
        return response()->noContent();
    } */
}
