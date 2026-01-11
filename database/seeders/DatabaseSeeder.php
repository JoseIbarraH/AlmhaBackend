<?php

namespace Database\Seeders;

use App\Domains\Blog\Seeders\BlogCategorySeeder;
use App\Domains\Design\Seeders\DesignSettingSeeder;
use App\Domains\Procedure\Seeders\ProcedureCategorySeeder;
use App\Domains\Setting\Setting\Seeders\SettingSeeder;
use App\Domains\Setting\User\Seeders\RolesAndPermissionsSeeder;
use App\Domains\Setting\User\Seeders\UserSeeder;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(DesignSettingSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(BlogCategorySeeder::class);
        $this->call(ProcedureCategorySeeder::class);
    }
}
