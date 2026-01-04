<?php

namespace Database\Seeders;

use App\Domains\TeamMember\Models\TeamMember;
use Illuminate\Database\Seeder;


class TeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TeamMember::factory()
        ->count(20)
        ->withTranslations()
        ->withImages()
        ->create();
    }
}
