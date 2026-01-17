<?php

namespace App\Domains\Setting\Setting\Controllers;

use App\Domains\Setting\Setting\Models\Setting;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use DB;
use Illuminate\Http\Request;


class SettingController extends Controller
{

    public function list_setting()
    {
        $data = Setting::all()->groupBy('group')->map(function ($items) {
            return $items->mapWithKeys(function ($item) {
                if ($item->key === 'whatsapp') {
                    return [$item->key => json_decode($item->value)];
                }
                return [$item->key => $item->value];
            });
        });

        return ApiResponse::success(
            message: 'Setting list success',
            data: $data,
            code: 200
        );
    }

    /**
     * Obtener un setting por key
     */
    public function get_setting(string $key, Request $request)
    {
        $default = $request->query('default');

        return ApiResponse::success(
            message: 'Get setting',
            data: [
                'key' => $key,
                'value' => Setting::getValue($key, $default),
            ],
            code: 200
        );
    }

    /**
     * Crear o actualizar un setting
     */
    public function update_settings(Request $request)
    {
        $data = $request->validate([
            'contact' => 'array',
            'social' => 'array',
            'system' => 'array',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data as $group => $settings) {
                foreach ($settings as $key => $value) {
                    Setting::setValue($key, $value, $group);
                }
            }
        });


        return ApiResponse::success(
            message: 'Settings updated successfully'
        );
    }

    /**
     * buscar un grupo de settings
     */
    public function find_group($group)
    {
        $data = Setting::findByGroup($group);

        return ApiResponse::success(
            message: "Group success",
            data: $data
        );
    }
}
