<?php

namespace Database\Factories;

use App\Models\ServiceResultGallery;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceResultGalleryFactory extends Factory
{
    protected $model = ServiceResultGallery::class;

    public function definition(): array
    {
        return [
            'path' => $this->faker->imageUrl(1200, 800, 'clinic', true),
        ];
    }
}
