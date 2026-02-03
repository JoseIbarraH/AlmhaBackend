<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Procedure\Models\Procedure;
use App\Domains\Procedure\Models\ProcedureCategory;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class ProcedureClientController extends Controller
{
    public function list_procedure(Request $request)
    {
        try {
            $perPage = 6;

            $procedures = \Illuminate\Support\Facades\Cache::tags(['procedures'])->remember("procedures_list_page_{$request->page}_{$request->search}_{$request->category_code}_" . app()->getLocale(), 86400, function () use ($perPage, $request) {
                $query = QueryBuilder::for(Procedure::class)
                    ->select('id', 'slug', 'image', 'status', 'category_code', 'created_at')
                    ->allowedFilters([
                        AllowedFilter::scope('search', 'RelationTitle'),
                        AllowedFilter::exact('category_code')
                    ])
                    ->with(['translation', 'category.translation'])
                    ->where('status', 'active')
                    ->whereHas('category');

                // Use default sort from builder or manual
                $p = (clone $query)->orderByDesc('views')
                    ->paginate($perPage)
                    ->withQueryString();

                $p->getCollection()->transform(function (Procedure $procedure) {
                    return [
                        'id' => $procedure->id,
                        'status' => $procedure->status,
                        'slug' => $procedure->slug,
                        'image' => $procedure->image,
                        'title' => $procedure->translation->title ?? '',
                        'subtitle' => $procedure->translation->subtitle ?? '',
                        'category' => $procedure->category?->translation?->title,
                        'category_code' => $procedure->category->code,
                        'created_at' => $procedure->created_at?->format('Y-m-d H:i:s')
                    ];
                });

                return $p;
            });

            // Obtener todas las categorías para los filtros
            $categories = \Illuminate\Support\Facades\Cache::tags(['procedures'])->remember("procedure_categories_count_" . app()->getLocale(), 86400, function () {
                return ProcedureCategory::with('translation')
                    ->withCount([
                        'procedures' => function ($query) {
                            $query->where('status', 'active');
                        }
                    ])
                    ->get()
                    ->map(function ($category) {
                        return [
                            'code' => $category->code,
                            'title' => $category->translation->title ?? $category->code,
                            'count' => $category->procedures_count,
                        ];
                    });
            });

            return ApiResponse::success(
                "list of procedures obtained correctly",
                [
                    'pagination' => $procedures,
                    'filters' => $request->only('search'),
                    'categories' => $categories
                ]
            );
        } catch (\Throwable $th) {
            \Log::error("Erro al crear" . $th->getMessage());
            return ApiResponse::error(
                "Error obtaining the list of procedures",
                $th,
                500
            );
        }
    }

    public function get_procedure($slug)
    {
        try {
            $data = \Illuminate\Support\Facades\Cache::tags(['procedures'])->remember("procedure_detail_{$slug}_" . app()->getLocale(), 86400, function () use ($slug) {
                $procedure = Procedure::with([
                    'translation',
                    'sections.translation',
                    'preparationSteps.translation',
                    'recoveryPhases.translation',
                    'postoperativeDos.translation',
                    'postoperativeDonts.translation',
                    'category.translation'
                ])
                    ->where('slug', $slug)
                    ->firstOrFail();

                return [
                    'id' => $procedure->id,
                    'slug' => $procedure->slug,
                    'image' => $procedure->image,
                    'status' => $procedure->status,
                    'category' => $procedure->category->translation->title,
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
            });

            // Increment views regardless of cache
            try {
                Procedure::where('slug', $slug)->increment('views');
            } catch (\Exception $e) {
            }

            return ApiResponse::success(
                __('messages.procedure.success.getProcedure'),
                $data
            );

        } catch (\Throwable $e) {
            \Log::error('Error en get_procedure: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.procedure.error.getProcedure'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
