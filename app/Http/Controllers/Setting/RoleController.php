<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
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
                'Rol creado correctamente',
                $role,
                201
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return ApiResponse::success(
                'Rol creado correctamente',
                $role,
                201
            );
        }

    }

    public function update_role(Request $request, $id)
    {
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

        return ApiResponse::success('Rol actualizado correctamente', $role, 200);
    }

}
