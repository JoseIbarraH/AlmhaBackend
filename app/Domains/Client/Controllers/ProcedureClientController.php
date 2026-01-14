<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Procedure\Models\Procedure;
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
            $perPage = 9;

            $procedures = QueryBuilder::for(Procedure::class)
                ->select('id', 'slug', 'image', 'status', 'category_code', 'created_at')
                ->allowedFilters([
                    AllowedFilter::scope('title', 'RelationTitle')
                ])
                ->defaultSort('-created_at')
                ->with(['translation', 'category.translation'])
                ->where('status', 'active')
                ->whereHas('category')
                ->paginate($perPage)
                ->withQueryString();

            $procedures->getCollection()->transform(function (Procedure $procedure) {
                return [
                    'id' => $procedure->id,
                    'status' => $procedure->status,
                    'slug' => $procedure->slug,
                    'image' => $procedure->image,
                    'title' => $procedure->translation->title ?? 'Default title',
                    'subtitle' => $procedure->translation->subtitle ?? 'Default subtitle',
                    'category' => $procedure->category?->translation?->title,
                    'category_code' => $procedure->category->code,
                    'created_at' => $procedure->created_at?->format('Y-m-d H:i:s')
                ];
            });

            return ApiResponse::success(
                "list of procedures obtained correctly",
                [
                    'pagination' => $procedures,
                    'filters' => $request->only('search'),
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
}
