<?php

namespace Database\Seeders;

use App\Domains\Blog\Models\BlogTranslation;
use App\Domains\Blog\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        Blog::factory()
            ->count(20)
            ->create()
            ->each(function ($blog) {
                // Crear traducciones en español e inglés
                BlogTranslation::factory()->create([
                    'blog_id' => $blog->id,
                    'lang' => 'es',
                ]);

                BlogTranslation::factory()->create([
                    'blog_id' => $blog->id,
                    'lang' => 'en',
                ]);
            });
    }
}
