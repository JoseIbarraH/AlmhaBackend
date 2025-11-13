<?php

namespace Database\Factories;

use App\Models\ServiceFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFaqFactory extends Factory
{
    protected $model = ServiceFaq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(8),
            'answer' => $this->faker->paragraph(),
            'lang' => $this->faker->randomElement(['es', 'en']),
        ];
    }
}
