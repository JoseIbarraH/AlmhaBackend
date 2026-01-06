<?php

namespace App\Domains\Setting\Setting\Controllers;

use App\Domains\Setting\Setting\Models\Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class SettingController extends Controller
{
    /**
     * Obtener un setting por key
     */
    public function get_setting(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:150',
            'default' => 'nullable',
        ]);

        return response()->json([
            'key'   => $data['key'],
            'value' => Setting::getValue(
                $data['key'],
                $data['default'] ?? null
            ),
        ]);
    }

    /**
     * Crear o actualizar un setting
     */
    public function update_setting(Request $request)
    {
        $data = $request->validate([
            'key'   => 'required|string|max:150',
            'value' => 'required',
            'group' => 'nullable|string|max:100',
        ]);

        Setting::setValue(
            $data['key'],
            $data['value'],
            $data['group'] ?? null
        );

        return response()->json([
            'message' => 'Updated setting successfully',
        ]);
    }
}
