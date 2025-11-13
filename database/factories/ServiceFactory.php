<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'image' => $this->faker->imageUrl(800, 600, 'surgery', true, 'Service'),
            'slug' => Str::slug($this->faker->unique()->words(3, true)),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
