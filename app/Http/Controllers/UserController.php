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

    public function list_user(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 5;

            $query = User::select('id', 'name', 'email');

            // Buscar solo por nombre y email
            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage)->appends($request->only('search'));

            return ApiResponse::success(
                __('messages.user.success.list'),
                $users
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



}
