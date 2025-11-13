<?php

namespace Database\Factories;

use App\Models\ServiceSampleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceSampleImageFactory extends Factory
{
    protected $model = ServiceSampleImage::class;

    public function definition(): array
    {
        return [
            'technique' => $this->faker->sentence(6),
            'recovery' => $this->faker->sentence(10),
            'postoperative_care' => $this->faker->paragraph(),
        ];
    }
}
