<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Setting\Setting\Models\Setting;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function maintenance()
    {
        return ApiResponse::success(
            message: 'Get maintenance state',
            data: [
                'key' => 'is_maintenance_mode',
                'value' => json_decode(Setting::getValue('is_maintenance_mode')),
            ],
            code: 200
        );
    }

    public function list_procedures()
    {

    }
}
