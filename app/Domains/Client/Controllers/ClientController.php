<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Setting\Setting\Models\Setting;
use App\Domains\Design\Models\DesignSetting;
use App\Domains\Procedure\Models\Procedure;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;

class ClientController extends Controller
{
    public function maintenance()
    {
        $value = Cache::tags(['maintenance'])->remember('maintenance_mode', 86400, function () {
            return Setting::getValue('is_maintenance_mode');
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
            $data = Cache::tags(['procedures'])->remember('navbar_data_' . app()->getLocale(), 86400, function () {
                $proceduresQuery = QueryBuilder::for(Procedure::class)
                    ->select('id', 'slug', 'image', 'category_code', 'views')
                    ->with(['translation', 'category.translation'])
                    ->where('status', 'active');

                $procedures = (clone $proceduresQuery)
                    ->whereHas('category')
                    ->get()
                    ->map(function (Procedure $procedure) {
                        return [
                            'id' => $procedure->id,
                            'slug' => $procedure->slug,
                            'title' => $procedure->translation?->title ?? '',
                            'category' => $procedure->category?->translation?->title,
                        ];
                    })
                    ->groupBy('category');

                return [
                    'procedures' => $procedures,
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
