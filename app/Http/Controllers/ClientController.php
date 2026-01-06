<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Helpers\ApiResponse;
use App\Domains\Blog\Models\Blog;
use App\Domains\Design\Models\DesignSetting;
use App\Domains\Design\Models\DesignItem;
use Illuminate\Http\Request;
use App\Domains\Procedure\Models\Procedure;

class ClientController extends Controller
{
    public function get_design_client(Request $request)
    {
        try {
            $lang = $request->query('lang', app()->getLocale());

            // Definir mapeo de grupos (constante para reutilización)
            $groupMapping = [
                'background1' => 'backgrounds',
                'background2' => 'backgrounds',
                'background3' => 'backgrounds',
                'carousel' => 'carousel',
                'carouselNavbar' => 'carouselNavbar',
                'carouselTool' => 'carouselTool',
                'imageVideo' => 'imageVideo',
                'maintenance' => 'maintenance'
            ];

            // Ejecutar consultas en paralelo usando lazy collections
            [$settingsCollection, $topServices] = [
                DesignSetting::with([
                    'designItems.translations' => function ($query) use ($lang) {
                        $query->where('lang', $lang);
                    }
                ])->get(),

                Procedure::with([
                    'translation',
                ])
                    ->where('status', 'active')
                    ->orderByDesc('views')
                    ->limit(3)
                    ->get()
            ];

            // Transformar settings de manera optimizada
            $transformedSettings = $settingsCollection->reduce(function ($carry, $design) use ($groupMapping) {
                $groupName = $groupMapping[$design->key] ?? $design->key;

                // Inicializar grupo si no existe
                $carry[$groupName] ??= [];

                // Agregar configuración del setting
                $carry[$groupName][$design->key . 'Setting'] = [
                    'id' => $design->id,
                    'enabled' => (bool) $design->value,
                ];

                // Transformar items del diseño
                $carry[$groupName][$design->key] = $design->designItems
                    ->map(fn($item) => [
                        'type' => $item->type,
                        'image' => $item->path,
                        'title' => $item->translations->first()->title ?? '',
                        'subtitle' => $item->translations->first()->subtitle ?? '',
                    ])
                    ->values()
                    ->toArray();

                return $carry;
            }, []);

            // Agregar top blogs a la respuesta
            $transformedSettings['topServices'] = $topServices->map(function ($service) {
                return [
                    'title' => $service->translation->title ?? '',
                    'slug' => $service->slug,
                    'image' => $service->image,
                    'created_at' => $service->created_at?->toISOString(),
                    'updated_at' => $service->updated_at?->toISOString(),
                ];
            })->toArray();

            return ApiResponse::success(data: $transformedSettings);

        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.design.error.getDesign') ?? 'Error fetching design',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function list_blog_client(Request $request)
    {
        try {
            $perPage = 9;

            $blogs = Blog::with(['translation', 'category.translation'])
                ->select('id', 'category_id', 'slug', 'status', 'image', 'created_at', 'updated_at')
                ->orderByDesc('created_at');

            if ($request->filled('search')) {
                $search = $request->search;
                $blogs->whereHas('translation', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            }

            $paginate = $blogs->paginate($perPage)->appends($request->only('search'));

            $paginate->getCollection()->transform(function ($blog) {
                return [
                    'title' => $blog->translation->title,
                    'slug' => $blog->slug,
                    'image' => $blog->image,
                    'category' => $blog->category->translation->title,
                    'created_at' => $blog->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $blog->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return ApiResponse::success(
                __('messages.blog.success.listBlogs'),
                [
                    'pagination' => $paginate,
                    'filters' => $request->only('search')
                ]
            );

        } catch (\Throwable $e) {
            Log::error('Error en list_blogs: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.blog.error.listBlogs'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
