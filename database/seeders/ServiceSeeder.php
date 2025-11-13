<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceResultGallery;
use App\Models\ServiceSampleImage;
use App\Models\ServiceSurgeryPhase;
use App\Models\ServiceTranslation;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::factory(30)->create()->each(function ($service) {
            // Relaciones por cada servicio
            ServiceFaq::factory(rand(2, 5))->create(['service_id' => $service->id]);
            ServiceResultGallery::factory(rand(2, 4))->create(['service_id' => $service->id]);
            ServiceSampleImage::factory()->create(['service_id' => $service->id]);
            ServiceSurgeryPhase::factory()->create(['service_id' => $service->id]);
            ServiceTranslation::factory(2)->create(['service_id' => $service->id]); // es/en
        });
    }
}
