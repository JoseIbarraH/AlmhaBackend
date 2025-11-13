<?php

namespace Database\Factories;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'user_id' => 1, // puedes ajustar si tienes usuarios
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'image' => $this->faker->imageUrl(800, 600, 'blog', true),
            'category' => $this->faker->randomElement(['general', 'facial', 'bodily', 'non-surgical']),
            'writer' => $this->faker->name(),
            'view' => $this->faker->numberBetween(0, 5000),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
