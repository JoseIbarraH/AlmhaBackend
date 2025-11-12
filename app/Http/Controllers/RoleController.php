<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|unique:roles',
            'description' => 'nullable|string',
            'permits' => 'nullable|array',
            'permits.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'title' => $request->title,
            'description' => $request->description
        ]);

        if ($request->filled('permits')) {
            $role->permissions()->sync($request->permisos);
        }

        return ApiResponse::success(
            'Rol creado correctamente',
            $role,
            201
        );
    }
}
