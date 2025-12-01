<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\TeamMember;
use App\Models\Service;
use App\Models\Blog;
use App\Models\Role;
use App\Models\User;

class TrashController extends Controller
{
    private const TRASHABLE_MODELS = [
        'Blog' => [
            'class' => Blog::class,
            'name_field' => 'translation.title',
            'translation' => true,
        ],
        'Service' => [
            'class' => Service::class,
            'name_field' => 'serviceTranslation.title',
            'translation' => true,
        ],
        'TeamMember' => [
            'class' => TeamMember::class,
            'name_field' => 'name',
            'translation' => false,
        ],
        'Role' => [
            'class' => Role::class,
            'name_field' => 'name',
            'translation' => false,
        ],
        'User' => [
            'class' => User::class,
            'name_field' => 'name',
            'translation' => false,
        ],
    ];

    /**
     * Obtener todos los elementos eliminados
     */
    public function list_trash(): JsonResponse
    {
        $trashedItems = collect(self::TRASHABLE_MODELS)
            ->flatMap(fn($config, $modelType) => $this->getTrashedItems($modelType, $config))
            ->sortByDesc('deleted_at')
            ->values();

        return ApiResponse::success(
            'Elementos eliminados obtenidos correctamente',
            $trashedItems
        );
    }

    /**
     * Obtener elementos eliminados de un modelo específico
     */
    private function getTrashedItems(string $modelType, array $config): Collection
    {
        $modelClass = $config['class'];

        $query = $modelClass::onlyTrashed();

        // Eager loading para modelos con traducciones
        if ($config['translation']) {
            $relationName = $this->getTranslationRelation($modelType);
            $query->with([$relationName => fn($q) => $q->where('lang', 'es')]);
        }

        return $query->get()->map(function ($item) use ($modelType, $config) {
            return [
                'model_type' => $modelType,
                'model_id' => $item->id,
                'name' => $this->getItemName($item, $config),
                'deleted_at' => $item->deleted_at->format('Y-m-d H:i:s')
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
                'Elemento restaurado correctamente',
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
                'Error al restaurar el elemento',
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
                return ApiResponse::error('Tipo de modelo no válido', null, 400);
            }

            $modelClass = self::TRASHABLE_MODELS[$modelType]['class'];
            $count = $modelClass::onlyTrashed()->count();

            $modelClass::onlyTrashed()->forceDelete();

            return ApiResponse::success(
                "Se eliminaron permanentemente {$count} elementos de {$modelType}",
                ['count' => $count, 'model_type' => $modelType]
            );

        } catch (\Throwable $e) {
            \Log::error('Error al vaciar papelera', [
                'model_type' => $modelType,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Error al vaciar la papelera',
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

        return ApiResponse::success('Estadísticas de papelera', [
            'by_model' => $stats,
            'total' => $stats->sum('count')
        ]);
    }

    /**
     * Eliminar permanentemente un elemento
     */
    public function force_delete(string $modelType, int $modelId): JsonResponse
    {
        try {
            if (!isset(self::TRASHABLE_MODELS[$modelType])) {
                return ApiResponse::error('Tipo de modelo no válido', null, 400);
            }

            $modelClass = self::TRASHABLE_MODELS[$modelType]['class'];
            $item = $modelClass::onlyTrashed()->findOrFail($modelId);

            $name = $this->getItemName($item, self::TRASHABLE_MODELS[$modelType]);

            $item->forceDelete();

            return ApiResponse::success(
                'Elemento eliminado permanentemente',
                [
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'name' => $name
                ]
            );

        } catch (\Throwable $e) {
            \Log::error('Error al eliminar permanentemente', [
                'model_type' => $modelType,
                'model_id' => $modelId,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Error al eliminar el elemento',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
}
