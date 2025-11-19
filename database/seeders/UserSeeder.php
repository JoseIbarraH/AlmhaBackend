<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(
            ['code' => 'super_admin'],
            [
                'title' => 'Super Administrador',
                'description' => 'Acceso completo a todo el sistema'
            ]
        );
        $allPermissions = Permission::pluck('id');
        $superAdminRole->permissions()->sync($allPermissions);

        // 🔹 Crear usuario inicial
        $user = User::firstOrCreate(
            ['email' => 'jcibarrah1423@gmail.com'], // evita duplicados
            [
                'name' => 'Jose Carlos Ibarra Herrera',
                'password' => Hash::make('12345678'), // cambia la contraseña después
                'status' => 'active'
            ]
        );

        if ($superAdminRole) {
            $user->roles()->sync([$superAdminRole->id]);
            echo "Asigno Rol. \n";
        }

        echo "Usuario super_admin creado correctamente.\n";
    }
}
