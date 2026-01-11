<?php

namespace App\Domains\Procedure\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcedureCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. BLOG CATEGORIES
        DB::table('procedure_categories')->insert([
            ['id' => 1, 'code' => 'general'],
        ]);

        // 2. BLOG CATEGORY TRANSLATIONS
        DB::table('procedure_category_translations')->insert([
            ['id' => 1, 'category_id' => 1, 'lang' => 'es', 'title' => 'General'],
            ['id' => 2, 'category_id' => 1, 'lang' => 'en', 'title' => 'General'],
        ]);
    }
}
