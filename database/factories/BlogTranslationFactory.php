<?php

namespace Database\Factories;

use App\Domains\Blog\Models\Blog;
use App\Domains\Blog\Models\BlogTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogTranslationFactory extends Factory
{
    protected $model = BlogTranslation::class;

    public function definition(): array
    {
        $lang = $this->faker->randomElement(['es', 'en']);

        return [
            'blog_id' => Blog::factory(),
            'lang' => $lang,
            'title' => $lang === 'es'
                ? $this->faker->sentence(5)
                : $this->faker->sentence(5) . ' (EN)',
            'content' => $lang === 'es'
                ? $this->faker->paragraphs(3, true)
                : $this->faker->paragraphs(3, true) . ' (Translated)',
        ];
    }
}
