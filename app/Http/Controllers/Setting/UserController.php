<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    public function list_user(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 5;
            $authId = auth()->id();

            $query = User::select('id', 'name', 'email', 'status', 'created_at', 'updated_at')
                ->with([
                    'roles.translations' => function ($q) use ($locale) {
                        $q->where('lang', $locale);
                    }
                ])->where('id', '!=', $authId);

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
                        $translation = $role->translations->first();

                        return [
                            'id' => $role->id,
                            'code' => $role->code,
                            'title' => $translation->title ?? $role->code,
                            'description' => $translation->description ?? '',
                        ];
                    })->toArray(),
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

    public function create_user(Request $request)
    {
        try {
            $request->merge([
                'email' => strtolower($request->email)
            ]);

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'status' => 'required|in:active,inactive',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'roles' => ['nullable', 'array'],
                'roles.*' => ['string', 'exists:roles,code'], // usamos el código interno
            ]);

            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'status' => $request->status,
                'password' => Hash::make($request->password),
            ]);

            if ($request->filled('roles')) {
                $roles = Role::whereIn('code', $request->roles)->pluck('id');
                $user->roles()->sync($roles);
            } else {
                $defaultRole = Role::where('code', 'default')->first();
                if ($defaultRole) {
                    $user->roles()->attach($defaultRole->id);
                }
            }

            event(new Registered($user));

            DB::commit();
            return ApiResponse::success(
                __('messages.user.success.createUser'),
                $user
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.user.error.createUser'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function update_user(Request $request, $id)
    {
        try {
            $request->merge([
                'email' => strtolower($request->email),
            ]);

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255',
                    Rule::unique('users', 'email')->ignore($id)
                ],
                'status' => 'required|in:active,inactive',
                'password' => ['sometimes', 'nullable', 'confirmed', Rules\Password::defaults()],
                'roles' => ['nullable', 'array'],
                'roles.*' => ['string', 'exists:roles,code'],
            ]);

            DB::beginTransaction();

            $user = User::findOrFail($id);
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'status' => $request->status,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            if ($request->filled('roles')) {
                // Buscar los roles por código y asignar
                $roles = Role::whereIn('code', $request->roles)->pluck('id');
                $user->roles()->sync($roles);

            } else {
                // Si no se envían roles → dejar sin roles o asignar default
                $defaultRole = Role::where('code', 'user')->first();

                $user->roles()->sync(
                    $defaultRole ? [$defaultRole->id] : []
                );
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.user.success.updateUser'),
                $user
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.user.error.updateUser'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function delete_user($id)
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($id);

            $user->roles()->detach();

            $user->delete();
            DB::commit();
            return ApiResponse::success(
                __('messages.user.success.deleteUser')
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.user.error.deleteUser'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function list_role(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());

            $roles = Role::join('role_translations as r', function ($join) use ($locale) {
                $join->on('roles.id', '=', 'r.role_id')->where('r.lang', $locale);
            })->select(
                    'roles.id',
                    'roles.code',
                    'r.title',
                    'r.description',
                )->orderBy('roles.created_at', 'desc')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'code' => $role->code,
                        'title' => $role->title,
                        'description' => $role->description
                    ];
                });

            return ApiResponse::success(
                __('messages.permission.success.listRoles'),
                ['roles' => $roles]
            );

        } catch (\Throwable $th) {
            return ApiResponse::error(
                __('messages.permission.error.listRoles'),
                ['exception' => $th->getMessage()],
                500
            );
        }
    }
}
