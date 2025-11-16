<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    public function addRols(Request $request, $id)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user = User::findOrFail($id);
        $user->roles()->sync($request->roles);

        return response()->json([
            'message' => 'Roles asignados correctamente',
            'rols' => $user->rols()->with('permissions')->get(),
        ]);
    }

    public function list_user(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 5;

            $query = User::select('id', 'name', 'email', 'status', 'created_at', 'updated_at')
                ->with([
                    'roles.translations' => function ($q) use ($locale) {
                        $q->where('lang', $locale);
                    }
                ]);

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            $paginate->getCollection()->transform(function ($user) use ($locale) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'roles' => $user->roles->map(function ($role) use ($locale) {
                        // Si existe traducción → usar el title traducido
                        return $role->translations->first()->title ?? $role->code;
                    })->toArray(), // <<< ARRAY DE STRINGS
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return ApiResponse::success(
                __('messages.user.success.list'),
                [
                    'pagination' => $paginate,
                    'filters' => $request->only('search'),
                ]
            );
        } catch (\Throwable $th) {
            \Log::error("Error list_user: ", [$th]);

            return ApiResponse::error(
                __('messages.user.error.list'),
                ['error' => $th->getMessage()],
                500
            );
        }
    }

    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $user->update(['status' => $data['status']]);

            DB::commit();
            return ApiResponse::success(
                __('messages.user.success.updateStatus'),
                $user
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.user.error.updateStatus'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,code'], // usamos el código interno
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->filled('roles')) {
            $roles = Role::whereIn('code', $request->roles)->pluck('id');
            $user->roles()->sync($roles);
        } else {
            $defaultRole = Role::where('code', 'user')->first();
            if ($defaultRole) {
                $user->rols()->attach($defaultRole->id);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}
