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
                ->select('id', 'slug', 'status', 'created_at', 'updated_at')
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
                    'status' => $procedure->status,
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
            Log::error("Erro al crear" . $th->getMessage());
            return ApiResponse::error(
                __('messages.procedure.error.listProcedures'),
                ['exception' => $th->getMessage()],
                500
            );
        }
    }

    public function get_procedure($id)
    {
        try {
            \DB::enableQueryLog();
            $procedure = Procedure::with([
                'translation', // Esta SÍ la usas para title y subtitle
                'sections.translation',
                'preparationSteps.translation',
                'recoveryPhases.translation',
                'postoperativeDos.translation',
                'postoperativeDonts.translation'
            ])
                ->where('id', $id)
                ->firstOrFail();

            $data = [
                'id' => $procedure->id,
                'slug' => $procedure->slug,
                'image' => $procedure->image,
                'status' => $procedure->status,
                'views' => $procedure->views,
                'title' => $procedure->translation->title ?? null,
                'subtitle' => $procedure->translation->subtitle ?? null,
                'section' => $procedure->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'type' => $section->type,
                        'image' => $section->image,
                        'title' => $section->translation->title,
                        'contentOne' => $section->translation->content_one,
                        'contentTwo' => $section->translation->content_two
                    ];
                })->toArray() ?? [],
                'preStep' => $procedure->preparationSteps->map(function ($pre) {
                    return [
                        'id' => $pre->id,
                        'title' => $pre->translation->title,
                        'description' => $pre->translation->description,
                        'order' => $pre->order
                    ];
                })->toArray() ?? [],
                'phase' => $procedure->recoveryPhases->map(function ($rec) {
                    return [
                        'id' => $rec->id,
                        'period' => $rec->translation->period,
                        'title' => $rec->translation->title,
                        'description' => $rec->translation->description,
                        'order' => $rec->order
                    ];
                })->toArray() ?? [],
                'do' => $procedure->postoperativeDos->map(function ($do) {
                    return [
                        'id' => $do->id,
                        'type' => $do->type,
                        'order' => $do->order,
                        'content' => $do->translation->content
                    ];
                })->toArray() ?? [],
                'dont' => $procedure->postoperativeDonts->map(function ($dont) {
                    return [
                        'id' => $dont->id,
                        'type' => $dont->type,
                        'order' => $dont->order,
                        'content' => $dont->translation->content
                    ];
                })->toArray() ?? []
            ];

            Log::info("Query: ", \DB::getQueryLog());

            return ApiResponse::success(
                __('messages.procedure.success.getProcedure'),
                $data
            );

        } catch (\Throwable $e) {
            Log::error('Error en get_procedure: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.procedure.error.getProcedure'),
                ['exception' => $e->getMessage()],
                500
            );
        }

    }

    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $procedure = Procedure::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $procedure->update(['status' => $data['status']]);

            DB::commit();
            return ApiResponse::success(
                __('messages.procedure.success.updateStatus'),
                $procedure
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.procedure.error.updateStatus'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }


}
