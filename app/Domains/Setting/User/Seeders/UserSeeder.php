<?php

namespace App\Domains\Setting\User\Seeders;

use App\Domains\Setting\User\Models\Permission;
use App\Domains\Setting\User\Models\Role;
use App\Domains\Setting\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

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

        $user = User::firstOrCreate(
            ['email' => 'jcibarrah1423@gmail.com'],
            [
                'name' => 'Jose Carlos Ibarra Herrera',
                'password' => Hash::make('12345678'),
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
