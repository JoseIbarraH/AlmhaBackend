<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Setting\Setting\Models\Setting;
use App\Domains\Procedure\Models\Procedure;
use App\Helpers\ApiResponse;

use Illuminate\Http\Request;

class ContactPageController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->has('lang')) {
                app()->setLocale($request->lang);
            }

            // Obtener settings relevantes para contacto
            $settingKeys = [
                'contact_phone',
                'contact_email',
                'contact_location',
                'whatsapp'
            ];

            $dbSettings = Setting::whereIn('key', $settingKeys)
                ->pluck('value', 'key');

            // Parsear whatsapp settings
            $whatsappSettings = json_decode($dbSettings['whatsapp'] ?? '{}', true);

            $settings = [
                'phone' => $dbSettings['contact_phone'] ?? null,
                'email' => $dbSettings['contact_email'] ?? null,
                'location' => $dbSettings['contact_location'] ?? null,
                'whatsapp_number' => $whatsappSettings['phone'] ?? null,
                'whatsapp_message' => $whatsappSettings['default_message'] ?? null,
                'whatsapp_active' => $whatsappSettings['is_active'] ?? false,
                'whatsapp_open_new_tab' => $whatsappSettings['open_in_new_tab'] ?? false,
            ];

            // Obtener procedimientos para el select
            $procedures = Procedure::where('status', 'active')
                ->with('translation')
                ->get()
                ->map(function ($procedure) {
                    return [
                        'id' => $procedure->id,
                        'title' => $procedure->translation?->title ?? 'Sin título',
                    ];
                });

            return ApiResponse::success(
                "Contact data retrieved successfully",
                [
                    'settings' => $settings,
                    'procedures' => $procedures
                ]
            );

        } catch (\Throwable $e) {
            \Log::error('Error fetching contact data: ' . $e->getMessage());
            return ApiResponse::error("Error fetching contact data", $e->getMessage());
        }
    }
}
