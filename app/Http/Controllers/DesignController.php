<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackgroundStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DesignController extends Controller
{
    public function index(){

    }

    public function backgroundStore(BackgroundStoreRequest $request){
        Log::info($request->all());
    }
}
