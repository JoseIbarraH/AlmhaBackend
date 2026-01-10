<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. BLOG CATEGORIES
        DB::table('blog_categories')->insert([
            ['id' => 1, 'code' => 'general'],
        ]);

        // 2. BLOG CATEGORY TRANSLATIONS
        DB::table('blog_category_translations')->insert([
            ['id' => 1, 'category_id' => 1, 'lang' => 'es', 'title' => 'General'],
            ['id' => 2, 'category_id' => 1, 'lang' => 'en', 'title' => 'General'],
        ]);
    }
}
