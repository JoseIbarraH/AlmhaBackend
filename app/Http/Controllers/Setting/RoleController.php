<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\RoleTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Role;

class RoleController extends Controller
{
    public function create_role(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'code' => 'required|string|unique:roles',
                'title' => 'required|string|unique:roles',
                'description' => 'nullable|string',
                'permits' => 'nullable|array',
                'permits.*' => 'integer|exists:permissions,id',
            ]);

            $code = $request->code ?? Str::slug($request->title, '_');

            $role = Role::create([
                'code' => $code,
                'status' => 'inactive,'
            ]);

            RoleTranslation::create([
                'lang' => 'es',
                'title' => $request->title,
                'description' => $request->description
            ]);

            RoleTranslation::create([
                'lang' => 'en',
                'title' => Helpers::translateBatch([$request->title])[0] ?? '',
                'description' => Helpers::translateBatch([$request->description])[0] ?? ''
            ]);

            if ($request->filled('permits')) {
                $role->permissions()->sync($request->permits);
            }

            DB::commit();
            return ApiResponse::success(
                __('messages.role.success.createRoles'),
                $role,
                201
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.role.error.createRoles'),
                $role,
                500
            );
        }

    }

    public function update_role(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'code' => 'required|string|unique:roles,code,' . $role->id,
                'title' => 'required|string|unique:roles,title,' . $role->id,
                'description' => 'nullable|string',
                'permits' => 'nullable|array',
                'permits.*' => 'integer|exists:permissions,id',
            ]);

            $role->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->filled('permits')) {
                $role->permissions()->sync($request->permits);
            }
            DB::commit();
            return ApiResponse::success(
                __('messages.role.success.updateRoles'),
                $role,
                200
            )
            ;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.role.error.updateRoles'),
                $role,
                500
            );
        }
    }

    public function list_role(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 5;

            $query = Role::join('role_translations as r', function ($join) use ($locale) {
                $join->on('Roles.id', '=', 'r.role_id')->where('r.lang', $locale);
            })->select('roles.id', 'roles.code', 'r.title', 'r.description', 'roles.created_at', 'roles.updated_at')
                ->orderBy('roles.created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('r.title', 'like', "%{$search}%");
            }

            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            $paginate->getCollection()->transform(function ($role) {
                return [
                    'id' => $role->id,
                    'code' => $role->code,
                    'title' => $role->title,
                    'description' => $role->description,
                    'created_at' => $role->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $role->updated_at->format('Y-m-d H:i:s')
                ];
            });

            return ApiResponse::success(
                __('messages.role.success.listRoles'),
                [
                    'pagination' => $paginate,
                    'filters' => $request->only('search')
                ]
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.role.error.listRoles'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $role->update(['status' => $data['status']]);

            DB::commit();
            return ApiResponse::success(
                __('messages.role.success.updateStatus'),
                $role
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.role.error.updateStatus'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function delete_role($id)
    {
        DB::beginTransaction();

        try {
            $role = Role::findOrFail($id);

            if ($role->code === 'super_admin') {
                return ApiResponse::error(
                    __('messages.role.error.deleteAdminRole'),
                    [],
                    403
                );
            }

            $role->permissions()->detach();
            $role->users()->detach();

            $role->delete();

            DB::commit();

            return ApiResponse::success(
                __('messages.role.success.deleteRole')
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.role.error.deleteRole'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function list_permission(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());

            $permissions = Permission::join('permission_translations as p', function ($join) use ($locale) {
                $join->on('permissions.id', '=', 'p.permission_id')
                    ->where('p.lang', $locale);
            })
                ->select(
                    'permissions.id',
                    'permissions.code',
                    'p.title',
                    'p.description',
                    'permissions.created_at',
                    'permissions.updated_at'
                )
                ->orderBy('permissions.created_at', 'desc')
                ->get()
                ->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'code' => $permission->code,
                        'title' => $permission->title,
                        'description' => $permission->description,
                        'created_at' => $permission->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $permission->updated_at->format('Y-m-d H:i:s')
                    ];
                });

            return ApiResponse::success(
                __('messages.permission.success.listRoles'),
                ['permissions' => $permissions]
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.permission.error.listRoles'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }


}
