<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = TeamMember::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'image' => 'images/default.png',
        ];
    }

    public function withTranslations(): static
    {
        return $this->afterCreating(function (TeamMember $member) {
            $member->teamMemberTranslations()->createMany([
                [
                    'specialization' => $this->faker->jobTitle(),
                    'biography' => $this->faker->paragraph(3),
                    'lang' => 'es',
                ],
                [
                    'specialization' => $this->faker->jobTitle(),
                    'biography' => $this->faker->paragraph(3),
                    'lang' => 'en',
                ],
            ]);
        });
    }

    public function withImages(): static
    {
        return $this->afterCreating(function (TeamMember $member) {
            $member->teamMemberImages()->createMany([
                [
                    'url' => 'images/default.png',
                    'description' => $this->faker->sentence(),
                    'lang' => 'es',
                ],
                [
                    'url' => 'images/default.png',
                    'description' => $this->faker->sentence(),
                    'lang' => 'en',
                ],
            ]);
        });
    }

}
