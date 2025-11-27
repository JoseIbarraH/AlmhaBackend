<?php

namespace App\Http\Controllers\Setting;

use App\Services\GoogleTranslateService;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\RoleTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Helpers\Helpers;
use App\Models\Role;

class RoleController extends Controller
{

    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function create_role(Request $request, GoogleTranslateService $translator)
    {
        \Log::info("llego: ", [$request->all()]);

        try {
            $request->validate([
                'code' => 'nullable|string|unique:roles,code',
                'title' => 'nullable|string|unique:role_translations,title',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive',
                'permits' => 'nullable|array',
                'permits.*' => 'string|exists:permissions,code',
            ]);

            DB::beginTransaction();

            $code = $request->code ?? Str::slug($request->title, '_');

            $role = Role::create([
                'code' => $code,
                'status' => $request->status,
            ]);

            foreach ($this->languages as $lang) {
                RoleTranslation::create([
                    'role_id' => $role->id,
                    'lang' => $lang,
                    'title' => $lang === 'es' ? $request->title : $translator->translate($request->title, $lang),
                    'description' => $lang === 'es' ? $request->description : $translator->translate($request->description, $lang),
                ]);
            }

            if ($request->filled('permits')) {
                $permissionIds = Permission::whereIn('code', $request->permits)->pluck('id')->toArray();
                $role->permissions()->sync($permissionIds);
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.role.success.createRoles'),
                $role,
                201
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::info("Error? ", [$th]);

            return ApiResponse::error(
                __('messages.role.error.createRoles'),
                ['error' => $th->getMessage()],
                500
            );
        }
    }

    public function update_role(Request $request, $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();

        try {
            $role = Role::findOrFail($id);

            // 1. Bloquear edición de super_admin
            if ($role->code === 'super_admin') {
                return ApiResponse::error(
                    __('messages.role.error.updateAdminRole'),
                    [],
                    403
                );
            }

            // Usamos firstOrNew para mayor seguridad en el acceso
            $translationEs = $role->translations()->firstOrNew(['lang' => 'es']);
            $oldTitle = $translationEs->title;
            $oldDescription = $translationEs->description;

            // 2. VALIDACIÓN CORREGIDA
            $request->validate([
                'title' => [
                    'required',
                    'string',

                    Rule::unique('role_translations', 'title')
                        ->ignore($translationEs->id ?? 0)
                        ->where(fn($query) => $query->where('lang', 'es')),
                ],
                'description' => 'nullable|string',
                'permits' => 'nullable|array',
                'permits.*' => 'string|exists:permissions,code',
            ]);

            $role->update([
                'status' => $request->status ?? $role->status,
            ]);

            $titleChanged = isset($request->title) && $request->title !== $oldTitle;
            $descriptionChanged = isset($request->description) && $request->description !== $oldDescription;

            if ($titleChanged || $descriptionChanged) {

                foreach ($this->languages as $lang) {
                    $translation = $role->translations()->firstOrNew(['lang' => $lang]);

                    if ($titleChanged) {
                        $translation->title = $lang === 'es'
                            ? $request->title
                            : $translator->translate($request->title, $lang);
                    }

                    if ($descriptionChanged && $request->filled('description')) {
                        $translation->description = $lang === 'es'
                            ? $request->description
                            : $translator->translate($request->description, $lang);
                    }

                    // Guardar los cambios
                    $translation->save();
                }
            }

            // 5. Permisos
            if ($request->filled('permits')) {
                $permissionIds = Permission::whereIn('code', $request->permits)
                    ->pluck('id')
                    ->toArray();

                $role->permissions()->sync($permissionIds);
            }

            DB::commit();

            // Cargar las traducciones para la respuesta si es necesario
            $role->load('translations');

            return ApiResponse::success(
                __('messages.role.success.updateRoles'),
                $role,
                200
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error($th);
            return ApiResponse::error(
                __('messages.role.error.updateRoles'),
                ['error' => $th->getMessage()],
                500
            );
        }
    }

    public function list_role(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 6;

            $query = Role::join('role_translations as r', function ($join) use ($locale) {
                $join->on('Roles.id', '=', 'r.role_id')->where('r.lang', $locale);
            })->select('roles.id', 'roles.code', 'roles.status', 'r.title', 'r.description', 'roles.created_at', 'roles.updated_at')
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
                    'status' => $role->status,
                    'title' => $role->title,
                    'description' => $role->description,
                    'permits' => \DB::table('permission_role')
                        ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                        ->where('permission_role.role_id', $role->id)
                        ->select('permissions.id', 'permissions.code')
                        ->get(),
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

            if ($role->code === 'super_admin') {
                return ApiResponse::error(
                    __('messages.role.error.updateStatusAdminRole'),
                    [],
                    403
                );
            }

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
            if ($role->code === 'user') {
                return ApiResponse::error(
                    __('messages.role.error.deleteUserRole'),
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

                    // SACAR CATEGORÍA DEL CODE
                    // Ejemplo: view_dashboard_limited → dashboard
                    $parts = explode('_', $permission->code);
                    $category = $parts[1] ?? 'general';

                    return [
                        'category' => $category,
                        'id' => $permission->id,
                        'code' => $permission->code,
                        'title' => $permission->title,
                        'description' => $permission->description,
                        'created_at' => $permission->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $permission->updated_at->format('Y-m-d H:i:s')
                    ];
                })
                ->groupBy('category'); // AGRUPAR POR CATEGORÍA

            return ApiResponse::success(
                __('messages.permission.success.listPermissions'),
                ['permissions' => $permissions]
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.permission.error.listPermissions'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }



}
