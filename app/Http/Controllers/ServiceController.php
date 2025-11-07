<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Service\UpdateRequest;
use App\Http\Requests\Dashboard\Service\StoreRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ServiceFaq;
use App\Helpers\Helpers;
use App\Models\Service;

class ServiceController extends Controller
{
    // Listar servicios
    public function list_services(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 9;

            $query = Service::with([
                'serviceTranslation' => fn($q) => $q->where('lang', $locale),
                'surgeryPhases' => fn($q) => $q->where('lang', $locale),
                'frequentlyAskedQuestions' => fn($q) => $q->where('lang', $locale),
                'sampleImages',
                'resultGallery',
            ]);

            // Filtro por búsqueda en traducción
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('serviceTranslation', function ($q) use ($search, $locale) {
                    $q->where('lang', $locale)
                        ->where(function ($q2) use ($search) {
                            $q2->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                });
            }

            // Paginación
            /* $services = $query->paginate($perPage); */
            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            // Transformar la colección paginada
            $paginate->getCollection()->transform(function ($service) {
                $translation = $service->serviceTranslation->first();

                return [
                    'id' => $service->id,
                    'status' => $service->status,
                    'slug' => $service->slug,
                    'image' => $service->image ? url("storage/{$service->image}") : null,
                    'title' => $translation->title ?? '',
                    'description' => $translation->description ?? '',
                    'surgery_phases' => $service->surgeryPhases->map(fn($phase) => [
                        'id' => $phase->id,
                        'recovery_time' => $phase->recovery_time,
                        'preoperative_recommendations' => $phase->preoperative_recommendations,
                        'postoperative_recommendations' => $phase->postoperative_recommendations,
                        'lang' => $phase->lang,
                    ]),
                    'frequently_asked_questions' => $service->frequentlyAskedQuestions->map(fn($faq) => [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'lang' => $faq->lang,
                    ]),
                    'sample_images' => $service->sampleImages ? [
                        'technique' => $service->sampleImages->technique ? url("storage/{$service->sampleImages->technique}") : null,
                        'recovery' => $service->sampleImages->recovery ? url("storage/{$service->sampleImages->recovery}") : null,
                        'postoperative_care' => $service->sampleImages->postoperative_care ? url("storage/{$service->sampleImages->postoperative_care}") : null,
                    ] : null,
                    'results_gallery' => $service->resultGallery->map(fn($img) => url("storage/{$img->path}")),
                    'created_at' => $service->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $service->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

            $total = Service::count();
            $totalActivated = Service::where('status', 'active')->count();
            $totalDeactivated = Service::where('status', 'inactive')->count();
            $last = Service::where('created_at', '>=', now()->subDays(15))->count();

            return ApiResponse::success(
                __('messages.service.success.listServices'),
                [
                    'pagination' => $paginate,
                    'filters' => $request->only('search'),
                    'stats' => [
                        'total' => $total,
                        'totalActivated' => $totalActivated,
                        'totalDeactivated' => $totalDeactivated,
                        'lastCreated' => $last,
                    ],
                ]
            );

        } catch (\Throwable $e) {
            Log::error('Error en list_services: ' . $e->getMessage());
            return ApiResponse::error(
                __('messages.service.error.listServices'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    // Ver un servicio
    public function get_service($id, Request $request)
    {
        try {
            $locale = 'es';

            $service = Service::with([
                'serviceTranslation' => fn($q) => $q->where('lang', $locale),
                'frequentlyAskedQuestions' => fn($q) => $q->where('lang', $locale),
                'surgeryPhases' => fn($q) => $q->where('lang', $locale),
                'sampleImages',
                'resultGallery'
            ])->findOrFail($id);

            Log::info($service);

            // Obtener traducción del idioma solicitado
            $translation = $service->serviceTranslation->first();

            $data = [
                'id' => $service->id,
                'status' => $service->status,
                'image' => $service->image ? asset('storage/' . $service->image) : null,
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),

                // Traducción del servicio
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',
                'lang' => $locale,

                // Preguntas frecuentes (verificar si existe)
                'frequently_asked_questions' => $service->frequentlyAskedQuestions
                    ? $service->frequentlyAskedQuestions->map(function ($faq) {
                        return [
                            'id' => $faq->id,
                            'question' => $faq->question ?? '',
                            'answer' => $faq->answer ?? ''
                        ];
                    })->toArray()
                    : [],

                // Fases de cirugía (verificar si existe)
                'surgery_phases' => $service->surgeryPhases && $service->surgeryPhases->isNotEmpty()
                    ? (function ($phase) {
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
                    })($service->surgeryPhases->first())
                    : [
                        'id' => 0,
                        'lang' => '',
                        'recovery_time' => [],
                        'preoperative_recommendations' => [],
                        'postoperative_recommendations' => [],
                    ],

                // Imágenes de muestra (verificar si existe)
                'sample_images' => $service->sampleImages
                    ? [
                        'id' => $service->sampleImages->id,
                        'technique' => $service->sampleImages->technique ? asset('storage/' . $service->sampleImages->technique) : null,
                        'recovery' => $service->sampleImages->recovery ? asset('storage/' . $service->sampleImages->recovery) : null,
                        'postoperative_care' => $service->sampleImages->postoperative_care ? asset('storage/' . $service->sampleImages->postoperative_care) : null,
                    ]
                    : [],

                // Galería de resultados (verificar si existe)
                'result_gallery' => $service->resultGallery
                    ? $service->resultGallery->map(function ($result) {
                        return [
                            'id' => $result->id,
                            'path' => $result->path ? asset('storage/' . $result->path) : null,
                        ];
                    })->toArray()
                    : [],
            ];

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.getService'),
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage(), [
                'service_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.getService'),
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    public function get_service_client($id, Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());

            $service = Service::with([
                'serviceTranslation' => fn($q) => $q->where('lang', $locale),
                'frequentlyAskedQuestions' => fn($q) => $q->where('lang', $locale),
                'surgeryPhases' => fn($q) => $q->where('lang', $locale),
                'sampleImages',
                'resultGallery'
            ])->findOrFail($id);

            // Obtener traducción del idioma solicitado
            $translation = $service->serviceTranslation->first();

            $data = [
                'id' => $service->id,
                'status' => $service->status,
                'image' => $service->image ? asset('storage/' . $service->image) : null,
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),

                // Traducción del servicio
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',
                'lang' => $locale,

                // Preguntas frecuentes (verificar si existe)
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

                // Fases de cirugía (verificar si existe)
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


                // Imágenes de muestra (verificar si existe)
                'sample_images' => $service->sampleImages
                    ? [
                        'id' => $service->sampleImages->id,
                        'technique' => $service->sampleImages->technique ? asset('storage/' . $service->sampleImages->technique) : null,
                        'recovery' => $service->sampleImages->recovery ? asset('storage/' . $service->sampleImages->recovery) : null,
                        'postoperative_care' => $service->sampleImages->postoperative_care ? asset('storage/' . $service->sampleImages->postoperative_care) : null,
                    ]
                    : [],

                // Galería de resultados (verificar si existe)
                'result_gallery' => $service->resultGallery
                    ? $service->resultGallery->map(function ($result) {
                        return [
                            'id' => $result->id,
                            'path' => $result->path ? asset('storage', $result->path) : null,
                        ];
                    })->toArray()
                    : [],
            ];

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.getService'),
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage(), [
                'service_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.getService'),
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    // Crear un servicio
    public function create_service(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Crear servicio
            $service = Service::create([
                'status' => $data['status'] ?? 'inactive',
                'image' => '',
                'slug' => ''
            ]);

            $service->image = Helpers::saveWebpFile($data['image'], "images/service/{$service->id}/service_image");
            // Mover imagen a carpeta definitiva
            $service->update([
                'image' => $service->image
            ]);

            // Crear traducciones (ES y EN)
            $this->createTranslations($service, $data);

            // Generar y guardar slug
            $service->update([
                'slug' => $this->generateUniqueSlug($service->serviceTranslation->where('lang', 'es')->first()->title)
            ]);

            // Crear fases de cirugía (si existen)
            if (!empty($data['surgery_phases'])) {
                $this->createSurgeryPhases($service, $data['surgery_phases']);
            }

            // Crear FAQs (si existen)
            if (!empty($data['frequently_asked_questions'])) {
                $this->createFAQs($service, $data['frequently_asked_questions']);
            }

            // Guardar imágenes de muestra
            if ($request->hasFile('sample_images')) {
                $this->saveSampleImages($service, $data['sample_images']);
            }

            // Guardar galería de resultados
            if ($request->hasFile('results_gallery')) {
                $this->saveResultsGallery($service, $request->file('results_gallery'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.createService'),
                'data' => $service->load([
                    'serviceTranslation',
                    'surgeryPhases',
                    'frequentlyAskedQuestions',
                    'sampleImages',
                    'resultGallery',
                ]),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_service', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.createService'),
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    private function createTranslations(Service $service, array $data): void
    {
        $translations = [
            'es' => [
                'title' => $data['title'],
                'description' => $data['description'],
            ],
            'en' => [
                'title' => Helpers::translateBatch([$data['title']], 'es', 'en')[0] ?? $data['title'],
                'description' => Helpers::translateBatch([$data['description']], 'es', 'en')[0] ?? $data['description'],
            ]
        ];

        foreach ($translations as $lang => $trans) {
            $service->serviceTranslation()->create([
                'lang' => $lang,
                'title' => $trans['title'],
                'description' => $trans['description'],
            ]);
        }
    }

    private function createSurgeryPhases(Service $service, array $phases): void
    {
        foreach ($phases as $phase) {
            // Crear versión ES
            $service->surgeryPhases()->create($phase + ['lang' => 'es']);

            // Traducir y crear versión EN
            $fieldsToTranslate = ['title', 'description', 'recovery_time', 'preoperative_recommendations', 'postoperative_recommendations'];

            $translated = collect($fieldsToTranslate)
                ->filter(fn($field) => !empty($phase[$field]))
                ->mapWithKeys(function ($field) use ($phase) {
                    $value = is_array($phase[$field]) ? implode(', ', $phase[$field]) : $phase[$field];
                    $translatedValue = Helpers::translateBatch([$value], 'es', 'en')[0] ?? $value;

                    return [$field => is_array($phase[$field]) ? explode(', ', $translatedValue) : $translatedValue];
                })
                ->toArray();

            $service->surgeryPhases()->create($translated + ['lang' => 'en']);
        }
    }

    private function createFAQs(Service $service, array $faqs): void
    {
        foreach ($faqs as $faq) {
            $question = $faq['question'] ?? '';
            $answer = $faq['answer'] ?? '';
            $order = $faq['order'] ?? 0;

            // Versión ES
            $service->frequentlyAskedQuestions()->create([
                'question' => $question,
                'answer' => $answer,
                'order' => $order,
                'lang' => 'es',
            ]);

            // Versión EN (traducida)
            $service->frequentlyAskedQuestions()->create([
                'question' => Helpers::translateBatch([$question], 'es', 'en')[0] ?? $question,
                'answer' => Helpers::translateBatch([$answer], 'es', 'en')[0] ?? $answer,
                'order' => $order,
                'lang' => 'en',
            ]);
        }
    }

    private function saveSampleImages(Service $service, array $images): void
    {
        $paths = [];
        $fields = ['technique', 'recovery', 'postoperative_care'];

        foreach ($fields as $field) {
            if (!empty($images[$field])) {
                $paths[$field] = Helpers::saveWebpFile(
                    $images[$field],
                    "images/service/{$service->id}/sample_images"
                );
            }
        }

        if (!empty($paths)) {
            $service->sampleImages()->create($paths);
        }
    }

    private function saveResultsGallery(Service $service, array $files): void
    {
        foreach ($files as $file) {
            $path = Helpers::saveWebpFile($file, "images/service/{$service->id}/results_gallery");

            if ($path) {
                $service->resultGallery()->create(['path' => $path]);
            }
        }
    }

    // Actualizar un servicio
    public function update_service(UpdateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $service = Service::findOrFail($id);
            $data = $request->validated();
            /* dd($data); */

            if (($data['status'] ?? null) !== $service->status) {
                $service->update(['status' => $data['status']]);
            }

            if (isset($data['image']) && !is_string($data['service_image'])) {
                if (!empty($service->service_image) && Storage::disk('public')->exists($service->service_image)) {
                    Storage::disk('public')->delete($service->service_image);
                }
                $path = Helpers::saveWebpFile($data['service_image'], "images/service/{$service->id}/service_image");
                $service->update(['service_image' => $path]);
            }

            $translations = [
                'es' => ServiceTranslation::firstOrCreate(['service_id' => $service->id, 'lang' => 'es']),
                'en' => ServiceTranslation::firstOrCreate(['service_id' => $service->id, 'lang' => 'en']),
            ];

            $fields = ['title', 'description'];
            $changed = collect($fields)->filter(fn($f) => ($data[$f] ?? '') !== $translations['es']->$f);

            if ($changed->isNotEmpty()) {
                $translations['es']->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                ]);

                $translations['en']->update([
                    'title' => $changed->contains('title')
                        ? Helpers::translateBatch([$data['title']], 'es', 'en')[0] ?? ''
                        : $translations['en']->title,
                    'description' => $changed->contains('description')
                        ? Helpers::translateBatch([$data['description'] ?? ''], 'es', 'en')[0] ?? ''
                        : $translations['en']->description,
                ]);
            }

            if (!empty($data['frequently_asked_questions'])) {
                // Traer los IDs existentes de la base de datos
                $existingFaqs = $service->frequentlyAskedQuestions()->pluck('id')->toArray();
                $incomingIds = [];

                foreach ($data['frequently_asked_questions'] as $faqData) {
                    $faqId = $faqData['id'] ?? null;

                    // Español
                    $faqEs = $faqId
                        ? ServiceFaq::find($faqId)
                        : new ServiceFaq(['service_id' => $service->id, 'lang' => 'es']);

                    $faqEs->fill([
                        'question' => $faqData['question'] ?? '',
                        'answer' => $faqData['answer'] ?? '',
                    ])->save();

                    $incomingIds[] = $faqEs->id;

                    // Inglés
                    $faqEn = ServiceFaq::firstOrNew([
                        'service_id' => $service->id,
                        'lang' => 'en',
                        'id' => $faqId, // asegura que se actualice el mismo registro
                    ]);

                    $faqEn->fill([
                        'question' => Helpers::translateBatch([$faqEs->question], 'es', 'en')[0] ?? '',
                        'answer' => Helpers::translateBatch([$faqEs->answer], 'es', 'en')[0] ?? '',
                    ])->save();
                }

                // Opcional: eliminar FAQs que ya no estén en el request
                $toDelete = array_diff($existingFaqs, $incomingIds);
                if (!empty($toDelete)) {
                    ServiceFaq::whereIn('id', $toDelete)->delete();
                }
            }


            if ($request->hasFile('sample_images')) {
                $sample = $service->sampleImages;

                // Cargamos los paths existentes (si hay)
                $technique = $sample->technique ?? null;
                $recovery = $sample->recovery ?? null;
                $postoperative_care = $sample->postoperative_care ?? null;

                // Procesamos cada campo nuevo solo si se envió un archivo
                foreach (['technique', 'recovery', 'postoperative_care'] as $field) {
                    if (isset($data['sample_images'][$field]) && !is_string($data['sample_images'][$field])) {
                        // Borrar anterior si existía
                        if ($sample && $sample->$field && Storage::disk('public')->exists($sample->$field)) {
                            Storage::disk('public')->delete($sample->$field);
                        }

                        // Guardar nuevo archivo
                        ${$field} = Helpers::saveWebpFile(
                            $data['sample_images'][$field],
                            "images/service/{$service->id}/sample_images"
                        );
                    }
                }

                // Solo un update/create con los resultados finales
                $service->sampleImages()->updateOrCreate(
                    ['service_id' => $service->id],
                    compact('technique', 'recovery', 'postoperative_care')
                );
            }

            if (isset($data['results_gallery'])) {
                $existingImages = $service->resultGallery()->pluck('path')->toArray();
                $newImages = [];

                foreach ($data['results_gallery'] as $item) {
                    // Si es string => imagen ya existente, se conserva
                    if (is_string($item)) {
                        $newImages[] = $item;
                    }
                    // Si es un archivo nuevo => se guarda y se agrega
                    elseif ($item instanceof \Illuminate\Http\UploadedFile) {
                        $path = Helpers::saveWebpFile($item, "images/service/{$service->id}/results_gallery");
                        $newImages[] = $path;
                        $service->resultGallery()->create(['path' => $path]);
                    }
                }

                // 🧹 Eliminar las imágenes que estaban antes pero ya no llegan
                $toDelete = array_diff($existingImages, $newImages);

                foreach ($toDelete as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    $service->resultGallery()->where('path', $path)->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.updateService'),
                'data' => $service->load([
                    'serviceTranslation',
                    'surgeryPhases',
                    'frequentlyAskedQuestions',
                    'sampleImages',
                    'resultGallery',
                ]),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_service: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.updateService'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Eliminar un servicio
    public function delete_service($id)
    {
        DB::beginTransaction();
        try {
            $service = Service::findOrFail($id);
            $service->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.deleteService')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en delete_service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.error.success.deleteService'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Actualizar estado
    public function update_status(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $service->update(['status' => $data['status']]);

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.updateStatus'),
                'data' => $service
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.updateStatus'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (Service::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
