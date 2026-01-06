<?php

namespace Database\Seeders;

use App\Domains\Design\Models\DesignSetting;
use Illuminate\Database\Seeder;


class DesignSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'imageVideo', 'value' => false, 'folder' => 'images/design/imageVideo'],
            ['key' => 'carousel', 'value' => true, 'folder' => 'images/design/carousel'],
            ['key' => 'carouselNavbar', 'value' => true, 'folder' => 'images/design/carouselNavbar'],
            ['key' => 'carouselTool', 'value' => true, 'folder' => 'images/design/carouselTool'],
            ['key' => 'background1', 'value' => true, 'folder' => 'images/design/background/background1'],
            ['key' => 'background2', 'value' => true, 'folder' => 'images/design/background/background2'],
            ['key' => 'background3', 'value' => true, 'folder' => 'images/design/background/background3'],
        ];

        foreach ($settings as $setting) {
            DesignSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'folder' => $setting['folder'],
                ]
            );
        }
    }
}
