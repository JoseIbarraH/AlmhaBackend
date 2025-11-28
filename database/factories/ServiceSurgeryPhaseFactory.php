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
            'recovery_time' => $this->faker->randomElements(
                ['1 semana', '2 semanas', '1 mes', '3 días', '4 semanas'],
                rand(1, 3) // cantidad aleatoria
            ),

            'preoperative_recommendations' => $this->faker->randomElements(
                [
                    'No fumar 24h antes',
                    'Suspender anticoagulantes',
                    'Ayuno de 8 horas',
                    'Hidratación adecuada'
                ],
                rand(1, 3)
            ),

            'postoperative_recommendations' => $this->faker->randomElements(
                [
                    'Usar faja por 2 semanas',
                    'Dormir boca arriba',
                    'Evitar esfuerzos físicos',
                    'Tomar medicamentos prescritos'
                ],
                rand(1, 3)
            ),

            'lang' => $this->faker->randomElement(['es', 'en']),
        ];
    }

}
