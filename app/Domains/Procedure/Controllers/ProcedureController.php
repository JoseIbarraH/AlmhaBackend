<?php

namespace App\Domains\Procedure\Controllers;

use App\Domains\Procedure\Models\Procedure;
use Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class ProcedureController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function list_procedure(Request $request)
    {
        try {
            $perPage = 9;

            $procedures = QueryBuilder::for(Procedure::class)
                ->select('id','slug','status','created_at','updated_at')
                ->allowedFilters([
                    AllowedFilter::scope('title', 'RelationTitle'),
                    'status'
                ])
                ->defaultSort('-created_at')
                ->with('translation')
                ->paginate($perPage)
                ->withQueryString();
            
            $procedures->getCollection()->transform(function (Procedure $procedure) {
                return [
                    'id' => $procedure->id,
                    'status'=> $procedure->status,
                    'slug' => $procedure->slug,
                    'title' => $procedure->translation->title ?? 'Default title',
                    'created_at' => $procedure->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $procedure->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

            $total = Procedure::count();
            $totalActivated = Procedure::where('status', 'active')->count();
            $totalDeactivated = Procedure::where('status', 'inactive')->count();
            $last = Procedure::where('created_at', '>=', now()->subDays(15))->count();

            return ApiResponse::success(
                __('messages.procedure.success.listProcedures'),
                [
                    'pagination' => $procedures,
                    'filters' => $request->only('search'),
                    'stats' => [
                        'total' => $total,
                        'totalActivated' => $totalActivated,
                        'totalDeactivated' => $totalDeactivated,
                        'lastCreated' => $last,
                    ],
                ]
            );
        } catch (\Throwable $th) {
            Log::error("Erro al crear". $th->getMessage());
            return ApiResponse::error(
                __('messages.procedure.error.listProcedures'),
                ['exception' => $th->getMessage()],
                500
            );
        }
    }

    public function show($id)
    {

    }

    public function create_procedure(Request $request)
    { 
        DB::beginTransaction();
        try {
            $procedure = Procedure::create([
                'user_id' => auth()->id(),
                'slug' => uniqid('temp-'),
                'views' => 0,
                'image' => '',
                'status' => 'draft'
            ]);

            $procedure->slug = null;
            $procedure->save();

            DB::commit();

            return ApiResponse::success(
                message: ('messages.procedure.success.createProcedure'),
                code: 201
            );

        } catch (\Throwable $th) {
            Log::error('Error en crear el procedimiento' . $th->getMessage());

            return ApiResponse::error(
                message: ('messages.procedure.error.createProcedure'),
                code: 500
            );
        }
    }
}
