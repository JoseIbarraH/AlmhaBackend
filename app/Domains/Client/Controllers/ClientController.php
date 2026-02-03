<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Design\Models\DesignSetting;
use App\Domains\Procedure\Models\Procedure;
use App\Domains\Setting\Setting\Models\Setting;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientController extends Controller
{
    public function maintenance()
    {
        $value = Cache::tags(['maintenance'])->remember('maintenance_mode', 86400, function () {
            return json_decode(Setting::getValue('is_maintenance_mode'));
        });

        return ApiResponse::success(
            message: 'Get maintenance state',
            data: [
                'key' => 'is_maintenance_mode',
                'value' => $value,
            ],
            code: 200
        );
    }

    public function navbarData()
    {
        try {
            $data = Cache::tags(['navbar', 'procedures', 'settings'])->remember('navbar_data_' . app()->getLocale(), 86400, function () {
                $carouselNavbar = DesignSetting::getData('carouselNavbar');

                $carousel = $carouselNavbar->designItems->map(function ($item) {
                    return [
                        'image' => $item->path,
                        'title' => $item->translation->title,
                        'subtitle' => $item->translation->subtitle,
                    ];
                })->values()->toArray();

                $proceduresQuery = QueryBuilder::for(Procedure::class)
                    ->select('id', 'slug', 'image', 'category_code', 'views')
                    ->with(['translation', 'category.translation'])
                    ->where('status', 'active');

                $topProcedure = (clone $proceduresQuery)->whereHas('category')->orderBy('views', 'desc')->limit(3)->get()->map(function (Procedure $procedure) {
                    return [
                        'id' => $procedure->id,
                        'slug' => $procedure->slug,
                        'image' => $procedure->image,
                        'title' => $procedure->translation?->title ?? '',
                        'category' => $procedure->category?->translation?->title ?? '',
                    ];
                });

                $procedures = (clone $proceduresQuery)
                    ->whereHas('category')
                    ->get()
                    ->map(function (Procedure $procedure) {
                        return [
                            'id' => $procedure->id,
                            'slug' => $procedure->slug,
                            'image' => $procedure->image,
                            'title' => $procedure->translation?->title ?? '',
                            'category' => $procedure->category?->translation?->title,
                        ];
                    })
                    ->groupBy('category');

                $communication = [];
                $communication['social'] = Setting::findByGroup("social");
                $communication['contact'] = Setting::findByGroup("contact");

                return [
                    'carousel' => $carousel,
                    'procedures' => $procedures,
                    'topProcedure' => $topProcedure,
                    'settings' => $communication
                ];
            });

            return ApiResponse::success(
                "Navbar data obtained correctly",
                $data
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                "Error listing the list of navbar data",
                $th,
                500
            );
        }
    }


}
