<?php

namespace App\Domains\Setting\Trash\Controllers;

use App\Domains\Setting\User\Models\Role;
use App\Domains\Setting\User\Models\User;
use App\Domains\TeamMember\Models\TeamMember;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Domains\Procedure\Models\Procedure;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use App\Domains\Blog\Models\Blog;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;


class TrashController extends Controller
{
    private const TRASHABLE_MODELS = [
        'Blog' => [
            'class' => Blog::class,
            'name_field' => 'translation.title',
            'translation' => true,
            'delete_url' => 'images/blog/'
        ],
        'Procedure' => [
            'class' => Procedure::class,
            'name_field' => 'procedureTranslation.title',
            'translation' => true,
            'delete_url' => 'images/procedure/'
        ],
        'TeamMember' => [
            'class' => TeamMember::class,
            'name_field' => 'name',
            'translation' => false,
            'delete_url' => 'images/team/'
        ],
        'Role' => [
            'class' => Role::class,
            'name_field' => 'name',
            'translation' => false,
            'delete_url' => ''
        ],
        'User' => [
            'class' => User::class,
            'name_field' => 'name',
            'translation' => false,
            'delete_url' => ''
        ],
    ];

    /**
     * Obtener todos los elementos eliminados
     */
    public function list_trash(Request $request): JsonResponse
    {
        $perPage = 10;
        // 1. Obtener todos los eliminados como colección
        $trashedItems = collect(self::TRASHABLE_MODELS)
            ->flatMap(fn($config, $modelType) => $this->getTrashedItems($modelType, $config))
            ->sortByDesc('deleted_at')
            ->values();

        // 2. Filtrado manual para colecciones
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $trashedItems = $trashedItems->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['model_type']), $search)
                    || str_contains(strtolower($item['name']), $search);
            })->values();
        }

        // 3. Paginar una colección manualmente
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $trashedItems->slice(($currentPage - 1) * $perPage, $perPage);
        $paginate = new LengthAwarePaginator(
            $currentItems->values(),
            $trashedItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return ApiResponse::success(
            __('messages.trash.success.listTrash'),
            [
                'pagination' => $paginate,
                'filters' => $request->only('search')
            ]
        );
    }

    /**
     * Obtener elementos eliminados de un modelo específico
     */
    private function getTrashedItems(string $modelType, array $config): Collection
    {
        $modelClass = $config['class'];

        $query = $modelClass::onlyTrashed();

        if ($config['translation']) {
            $relationName = $this->getTranslationRelation($modelType);
            $query->with([$relationName => fn($q) => $q->where('lang', 'es')]);
        }

        return $query->get()->map(function ($item) use ($modelType, $config) {
            return [
                'model_type' => $modelType,
                'model_id' => $item->id,
                'name' => $this->getItemName($item, $config),
                'deleted_at' => $item->deleted_at->format('Y-m-d H:i:s'),
                'model' => $item->toArray()
            ];
        });
    }

    /**
     * Obtener el nombre del elemento según su configuración
     */
    private function getItemName($item, array $config): ?string
    {
        if ($config['translation']) {
            $relationName = $this->getTranslationRelation(class_basename($item));
            $translation = $item->$relationName->first();

            // Extraer el campo después del punto (ej: "translation.title" -> "title")
            $field = explode('.', $config['name_field'])[1] ?? 'title';

            return $translation?->$field;
        }

        return $item->{$config['name_field']} ?? null;
    }

    /**
     * Obtener nombre de la relación de traducción según el modelo
     */
    private function getTranslationRelation(string $modelType): string
    {
        return match ($modelType) {
            'Blog' => 'translation',
            'Service' => 'serviceTranslation',
            default => 'translations',
        };
    }

    /**
     * Restaurar un elemento eliminado
     */
    public function restore_trash(string $modelType, int $modelId): JsonResponse
    {
        try {
            if (!isset(self::TRASHABLE_MODELS[$modelType])) {
                return ApiResponse::error('Tipo de modelo no válido', null, 400);
            }

            $modelClass = self::TRASHABLE_MODELS[$modelType]['class'];
            $item = $modelClass::onlyTrashed()->findOrFail($modelId);

            $item->restore();

            return ApiResponse::success(
                __('messages.trash.success.restoreTrash'),
                [
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'name' => $this->getItemName($item, self::TRASHABLE_MODELS[$modelType])
                ]
            );

        } catch (\Throwable $e) {
            \Log::error('Error al restaurar elemento', [
                'model_type' => $modelType,
                'model_id' => $modelId,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                __('messages.trash.error.restoreTrash'),
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Vaciar papelera de un modelo específico
     */
    public function empty_trash(string $modelType): JsonResponse
    {
        try {
            if (!isset(self::TRASHABLE_MODELS[$modelType])) {
                return ApiResponse::error(__('messages.trash.error.empty_trash.invalidModel'), null, 400);
            }

            $modelClass = self::TRASHABLE_MODELS[$modelType]['class'];
            $count = $modelClass::onlyTrashed()->count();

            $modelClass::onlyTrashed()->forceDelete();

            return ApiResponse::success(
                __('messages.trash.error.emptyTrash', ['count' => $count, 'modelType' => $modelType]),
                ['count' => $count, 'model_type' => $modelType]
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.trash.error.emptyTrash.emptyTrash'),
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Obtener estadísticas de la papelera
     */
    public function stats_trash(): JsonResponse
    {
        $stats = collect(self::TRASHABLE_MODELS)->map(function ($config, $modelType) {
            $modelClass = $config['class'];

            return [
                'model_type' => $modelType,
                'count' => $modelClass::onlyTrashed()->count(),
                'oldest' => optional($modelClass::onlyTrashed()->oldest('deleted_at')->first())
                    ->deleted_at?->format('Y-m-d H:i:s'),
                'newest' => optional($modelClass::onlyTrashed()->latest('deleted_at')->first())
                    ->deleted_at?->format('Y-m-d H:i:s'),
            ];
        })->values();

        return ApiResponse::success(
            __('messages.trash.success.statsTrash'),
            [
                'by_model' => $stats,
                'total' => $stats->sum('count')
            ]
        );
    }

    /**
     * Eliminar permanentemente un elemento
     */
    public function force_delete(string $modelType, int $modelId): JsonResponse
    {
        try {
            if (!isset(self::TRASHABLE_MODELS[$modelType])) {
                return ApiResponse::error(__('messages.trash.error.forceDelete.invalidModel'), null, 400);
            }

            $modelClass = self::TRASHABLE_MODELS[$modelType]['class'];

            $item = $modelClass::onlyTrashed()->findOrFail($modelId);

            $name = $this->getItemName($item, self::TRASHABLE_MODELS[$modelType]);

            $urlDelete = self::TRASHABLE_MODELS[$modelType]['delete_url'];

            if (!empty($urlDelete)) {
                $path = rtrim($urlDelete, '/') . '/' . $modelId;
                Storage::disk('public')->deleteDirectory($path);
            }

            $item->forceDelete();

            return ApiResponse::success(
                __('messages.trash.error.forceDelete.forceDelete'),
                [
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'name' => $name
                ]
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.trash.error.forceDelete.forceDelete'),
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
}
