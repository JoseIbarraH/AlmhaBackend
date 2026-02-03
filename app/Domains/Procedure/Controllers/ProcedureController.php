<?php

namespace App\Domains\Procedure\Controllers;

use App\Domains\Procedure\Models\Procedure;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Log;


class ProcedureController extends Controller
{
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
            $procedure = Procedure::with([
                'translation',
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
                'category' => $procedure->category_code,
                'views' => $procedure->views,
                'title' => $procedure->translation->title ?? '',
                'subtitle' => $procedure->translation->subtitle ?? '',
                'section' => $procedure->sections->map(fn($section) => [
                    'id' => $section->id,
                    'type' => $section->type,
                    'image' => $section->image,
                    'title' => $section->translation->title,
                    'contentOne' => $section->translation->content_one,
                    'contentTwo' => $section->translation->content_two
                ])->toArray() ?? [],
                'preStep' => $procedure->preparationSteps->map(fn($pre) => [
                    'id' => $pre->id,
                    'title' => $pre->translation->title,
                    'description' => $pre->translation->description,
                    'order' => $pre->order
                ])->toArray() ?? [],
                'phase' => $procedure->recoveryPhases->map(fn($rec) => [
                    'id' => $rec->id,
                    'period' => $rec->translation->period,
                    'title' => $rec->translation->title,
                    'description' => $rec->translation->description,
                    'order' => $rec->order
                ])->toArray() ?? [],
                'do' => $procedure->postoperativeDos->map(fn($do) => [
                    'id' => $do->id,
                    'type' => $do->type,
                    'order' => $do->order,
                    'content' => $do->translation->content
                ])->toArray() ?? [],
                'dont' => $procedure->postoperativeDonts->map(fn($dont) => [
                    'id' => $dont->id,
                    'type' => $dont->type,
                    'order' => $dont->order,
                    'content' => $dont->translation->content
                ])->toArray() ?? [],
                'faq' => $procedure->faqs->map(fn($faq) => [
                    'id' => $faq->id,
                    'question' => $faq->translation->question,
                    'answer' => $faq->translation->answer,
                    'order' => $faq->order
                ])->toArray() ?? [],
                'gallery' => $procedure->resultGallery->map(fn($gallery) => [
                    'id' => $gallery->id,
                    'path' => $gallery->path,
                    'order' => $gallery->order
                ])->toArray() ?? []
            ];

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

            \Illuminate\Support\Facades\Cache::tags(['procedures', 'navbar'])->flush();

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
