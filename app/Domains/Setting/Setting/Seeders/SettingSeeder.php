<?php

namespace App\Domains\Setting\Setting\Seeders;

use App\Domains\Setting\Setting\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'contact_phone',
                'value' => '+57 3131231233',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@gmail.com',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_location',
                'value' => 'Cartagena',
                'group' => 'contact',
            ],
            [
                'key' => 'is_maintenance_mode',
                'value' => true,
                'group' => 'system',
            ],
            [
                'key' => 'social_facebook',
                'value' => '',
                'group' => 'social',
            ],
            [
                'key' => 'social_instagram',
                'value' => '',
                'group' => 'social',
            ],
            [
                'key' => 'social_threads',
                'value' => '',
                'group' => 'social',
            ],
            [
                'key' => 'social_twitter',
                'value' => '',
                'group' => 'social',
            ],
            [
                'key' => 'social_linkedin',
                'value' => '',
                'group' => 'social',
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                ]
            );
        }
    }
}
