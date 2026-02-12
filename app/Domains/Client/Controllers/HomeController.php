<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Design\Models\DesignSetting;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getHomeData(Request $request)
    {
        try {
            $keys = [
                'background1',
                'background2',
                'background3',
                'carousel',
                'carouselTool',
                'imageVideo'
            ];

            $locale = app()->getLocale();
            $cacheKey = "home_data_{$locale}";

            $data = Cache::rememberForever($cacheKey, function () use ($keys) {
                $settingsCollection = DesignSetting::whereIn('key', $keys)
                    ->with(['designItems.translation'])
                    ->get();

                $groupMapping = [
                    'background1' => 'backgrounds',
                    'background2' => 'backgrounds',
                    'background3' => 'backgrounds',
                    'carousel' => 'carousel',
                    'carouselTool' => 'carouselTool',
                    'imageVideo' => 'imageVideo',
                ];

                return $settingsCollection->reduce(function ($carry, $design) use ($groupMapping) {
                    $key = $design->key;
                    $groupName = $groupMapping[$key] ?? 'others';

                    // Procesar ítems con seguridad
                    $itemsArray = $design->designItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'type' => $item->type,
                            'image' => $item->path,
                            'title' => $item->translation->title ?? '',
                            'subtitle' => $item->translation->subtitle ?? '',
                        ];
                    })->values()->toArray();

                    // Inicializar grupo si no existe
                    if (!isset($carry[$groupName])) {
                        $carry[$groupName] = [];
                    }

                    // Estructura de configuración
                    $carry[$groupName][$key . 'Setting'] = [
                        'id' => $design->id,
                        'enabled' => filter_var($design->value, FILTER_VALIDATE_BOOLEAN),
                    ];

                    // Inyectar los ítems
                    $carry[$groupName][$key] = $itemsArray;

                    return $carry;
                }, []);
            });

            \Log::info("Data (Cached/Fresh): ", [$data]);

            return ApiResponse::success(data: $data);
        } catch (\Throwable $th) {
            return ApiResponse::error(
                'Error fetching home data',
                ['exception' => $th->getMessage()]
            );
        }
    }
}
