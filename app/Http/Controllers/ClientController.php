<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Blog;
use App\Models\DesignSetting;
use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

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
            ];

            // Ejecutar consultas en paralelo usando lazy collections
            [$settingsCollection, $topServices] = [
                DesignSetting::with([
                    'designItems.translations' => fn($q) => $q->where('lang', $lang)
                ])->get(),

                Service::with([
                    'serviceTranslation' => fn($q) => $q->where('lang', $lang),
                ])
                    ->where('status', 'active')
                    ->orderByDesc('view')
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
                        'image' => $item->full_path,
                        'title' => $item->translations->first()?->title ?? '',
                        'subtitle' => $item->translations->first()?->subtitle ?? '',
                    ])
                    ->values()
                    ->toArray();

                return $carry;
            }, []);

            // Agregar top blogs a la respuesta
            $transformedSettings['topServices'] = $topServices->map(function ($service) {
                $translation = $service->serviceTranslation->first();

                return [
                    'title' => $translation?->title ?? $service->title ?? '',
                    'slug' => $service->slug,
                    'image' => $service->image ? asset('storage/' . $service->image) : null,
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


    public function get_service_client($slug, Request $request)
    {
        try {
            $lang = $request->query('locale', app()->getLocale());

            $service = Service::with([
                'serviceTranslation' => fn($q) => $q->where('lang', $lang),
                'frequentlyAskedQuestions' => fn($q) => $q->where('lang', $lang),
                'surgeryPhases' => fn($q) => $q->where('lang', $lang),
                'sampleImages',
                'resultGallery'
            ])
                ->where('slug', $slug)
                ->firstOrFail();

            // Obtener traducción del idioma solicitado
            $translation = $service->serviceTranslation->first();

            $data = [
                'id' => $service->id,
                'slug' => $service->slug,
                'status' => $service->status,
                'image' => $service->image ? asset('storage/' . $service->image) : null,
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),

                // Traducción del servicio
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',
                'lang' => $lang,

                // Preguntas frecuentes
                'frequently_asked_questions' => $service->frequentlyAskedQuestions
                    ? $service->frequentlyAskedQuestions->map(function ($faq) {
                        return [
                            'id' => $faq->id,
                            'question' => $faq->question ?? '',
                            'answer' => $faq->answer ?? '',
                            'order' => $faq->order ?? 0,
                        ];
                    })->toArray()
                    : [],

                // Fases de cirugía
                'surgery_phases' => $service->surgeryPhases
                    ? $service->surgeryPhases->map(function ($phase) {
                        return [
                            'id' => $phase->id,
                            'lang' => $phase->lang,
                            'recovery_time' => is_string($phase->recovery_time)
                                ? json_decode($phase->recovery_time, true)
                                : ($phase->recovery_time ?? []),
                            'preoperative_recommendations' => is_string($phase->preoperative_recommendations)
                                ? json_decode($phase->preoperative_recommendations, true)
                                : ($phase->preoperative_recommendations ?? []),
                            'postoperative_recommendations' => is_string($phase->postoperative_recommendations)
                                ? json_decode($phase->postoperative_recommendations, true)
                                : ($phase->postoperative_recommendations ?? []),
                        ];
                    })->toArray()
                    : [],

                // Imágenes de muestra
                'sample_images' => $service->sampleImages
                    ? [
                        'id' => $service->sampleImages->id,
                        'technique' => $service->sampleImages->technique ? asset('storage/' . $service->sampleImages->technique) : null,
                        'recovery' => $service->sampleImages->recovery ? asset('storage/' . $service->sampleImages->recovery) : null,
                        'postoperative_care' => $service->sampleImages->postoperative_care ? asset('storage/' . $service->sampleImages->postoperative_care) : null,
                    ]
                    : [],

                // Galería de resultados
                'result_gallery' => $service->resultGallery
                    ? $service->resultGallery->map(function ($result) {
                        return [
                            'id' => $result->id,
                            'path' => $result->path ? asset('storage/' . $result->path) : null,
                        ];
                    })->toArray()
                    : [],
            ];

            $service->increment('view');

            return ApiResponse::success(
                __('messages.service.success.getService'),
                $data,
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponse::error(
                __('messages.service.error.notFound'),
                'El servicio no fue encontrado',
                404
            );
        } catch (\Throwable $e) {
            \Log::error('Error en get_service: ' . $e->getMessage(), [
                'service_slug' => $slug,
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.service.error.getService'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }
}
