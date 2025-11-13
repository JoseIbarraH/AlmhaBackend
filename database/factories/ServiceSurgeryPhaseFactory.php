<?php

namespace Database\Factories;

use App\Models\ServiceSurgeryPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceSurgeryPhaseFactory extends Factory
{
    protected $model = ServiceSurgeryPhase::class;

    public function definition(): array
    {
        return [
            'recovery_time' => $this->faker->randomElement(['1 semana', '2 semanas', '1 mes']),
            'preoperative_recommendations' => $this->faker->paragraph(),
            'postoperative_recommendations' => $this->faker->paragraph(),
            'lang' => $this->faker->randomElement(['es', 'en']),
        ];
    }
}
