<?php

namespace Database\Factories;

use App\Models\ServiceTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTranslationFactory extends Factory
{
    protected $model = ServiceTranslation::class;

    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->paragraph(3),
            'lang' => $this->faker->randomElement(['es', 'en']),
        ];
    }
}
