<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Crear usuario inicial
        $user = User::firstOrCreate(
            ['email' => 'jcibarrah1423@gmail.com'], // evita duplicados
            [
                'name' => 'Jose Carlos Ibarra Herrera',
                'password' => Hash::make('12345678'), // cambia la contraseña después
            ]
        );

        // 🔹 Asignar rol super_admin
        $superAdminRole = Role::where('code', 'super_admin')->first();

        if ($superAdminRole) {
            $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        echo "✅ Usuario super_admin creado correctamente.\n";
    }
}
