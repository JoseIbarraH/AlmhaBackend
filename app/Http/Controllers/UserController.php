<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;

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

}
