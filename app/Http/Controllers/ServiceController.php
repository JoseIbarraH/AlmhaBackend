<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Service\UpdateRequest;
use App\Http\Requests\Dashboard\Service\StoreRequest;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceTranslation;
use Illuminate\Http\Request;
use App\Models\ServiceFaq;
use App\Helpers\Helpers;
use App\Models\Service;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    // Listar servicios
    public function list_service(Request $request)
    {
        try {

            $perPage = 9;

            $services = QueryBuilder::for(Service::class)
                ->allowedIncludes(['translation', 'surgeryPhases', 'frequentlyAskedQuestions', 'sampleImages', 'resultGallery'])
                ->allowedFilters([
                    AllowedFilter::scope('title', 'RelationTitle'),
                ])
                ->allowedSorts(['created_at', 'updated_at'])
                ->defaultSort('-created_at')
                ->whereHas('translation')
                ->with(['translation'])
                ->paginate($perPage)
                ->withQueryString();

            $services->getCollection()->transform(function ($service) {
                return [
                    'id' => $service->id,
                    'status' => $service->status,
                    'slug' => $service->slug,
                    'title' => $service->translation->title ?? '',
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
                    'pagination' => $services,
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
            $service = Service::with([
                'translation',
                'frequentlyAskedQuestions',
                'surgeryPhases',
                'sampleImages',
                'resultGallery'
            ])->findOrFail($id);

            Log::info($service);

            // Obtener traducción del idioma solicitado
            $translation = $service->translation;

            $data = [
                'id' => $service->id,
                'status' => $service->status,
                'image' => $service->image,
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),

                // Traducción del servicio
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',

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
                'results_gallery' => $service->resultGallery
                    ? $service->resultGallery->map(function ($result) {
                        return [
                            'id' => $result->id,
                            'path' => $result->path ? asset('storage/' . $result->path) : null,
                        ];
                    })->toArray()
                    : [],
            ];

            return ApiResponse::success(
                __('messages.service.success.getService'),
                $data
            );

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage(), [
                'service_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.service.error.getService'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    /**
     * Crear un servicio StoreRequest
     */
    public function create_service(StoreRequest $request, GoogleTranslateService $translator)
    {
        /* Log::info("Datos: ", [$request->all()]); */
        DB::beginTransaction();
        try {
            $data = $request->all();
            $service = Service::create([
                'status' => $data['status'] ?? 'inactive',
                'image' => '',
                'slug' => ''
            ]);

            $service->image = Helpers::saveWebpFile($data['image'], "images/service/{$service->id}/service_image");
            $service->update(['image' => $service->image]);

            $this->createTranslations($service, $data, $translator);

            $service->update([
                'slug' => Helpers::generateUniqueSlug(
                    modelClass: Service::class,
                    title: $service->serviceTranslation()->where('lang', 'es')->first()->title,
                    slugColumn: 'slug'
                )
            ]);

            if (!empty($data['surgery_phases'])) {
                $this->createSurgeryPhases($service, $data['surgery_phases'], $translator);
            }

            if (!empty($data['frequently_asked_questions'])) {
                $this->createFAQs($service, $data['frequently_asked_questions'], $translator);
            }

            if ($request->hasFile('sample_images')) {
                $this->saveSampleImages($service, $data['sample_images']);
            }

            if ($request->hasFile('results_gallery')) {
                $this->saveResultsGallery($service, $request->file('results_gallery'));
            }

            DB::commit();
            return ApiResponse::success(
                __('messages.service.success.createService'),
                $service->load([
                    'serviceTranslation',
                    'surgeryPhases',
                    'frequentlyAskedQuestions',
                    'sampleImages',
                    'resultGallery',
                ]),
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_service', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.service.error.createService'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    private function createTranslations(Service $service, array $data, GoogleTranslateService $translator): void
    {
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                $service->serviceTranslation()->create([
                    'lang' => $lang,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                ]);
                continue;
            }

            try {
                // Traducir title y description en UNA sola llamada
                $translated = $translator->translate([
                    $data['title'],
                    $data['description'] ?? ''
                ], $lang);

                $service->serviceTranslation()->create([
                    'lang' => $lang,
                    'title' => $translated[0] ?? $data['title'],
                    'description' => $translated[1] ?? ($data['description'] ?? ''),
                ]);

            } catch (\Exception $e) {
                \Log::error("Translation error for service {$service->id} to {$lang}: " . $e->getMessage());

                // Fallback: usar texto original
                $service->serviceTranslation()->create([
                    'lang' => $lang,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                ]);
            }
        }
    }

    private function createSurgeryPhases(Service $service, array $phases, GoogleTranslateService $translator): void
    {
        $service->surgeryPhases()->create([
            'lang' => 'es',
            'recovery_time' => $phases['recovery_time'] ?? [],
            'preoperative_recommendations' => $phases['preoperative_recommendations'] ?? [],
            'postoperative_recommendations' => $phases['postoperative_recommendations'] ?? [],
        ]);

        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                $translatedData = ['lang' => $lang];

                $textsToTranslate = [];
                $fieldMap = [];

                foreach ($phases as $key => $values) {
                    if (empty($values) || !is_array($values)) {
                        $translatedData[$key] = [];
                        continue;
                    }

                    $fieldMap[$key] = [
                        'start' => count($textsToTranslate),
                        'count' => count($values)
                    ];
                    $textsToTranslate = array_merge($textsToTranslate, $values);
                }

                if (!empty($textsToTranslate)) {
                    $translated = $translator->translate($textsToTranslate, $lang);

                    foreach ($fieldMap as $key => $map) {
                        $translatedData[$key] = array_slice(
                            $translated,
                            $map['start'],
                            $map['count']
                        );
                    }
                }

                $service->surgeryPhases()->create($translatedData);

            } catch (\Exception $e) {
                \Log::error("Translation error for surgery phases service {$service->id} to {$lang}: " . $e->getMessage());

                $service->surgeryPhases()->create([
                    'lang' => $lang,
                    'recovery_time' => $phases['recovery_time'] ?? [],
                    'preoperative_recommendations' => $phases['preoperative_recommendations'] ?? [],
                    'postoperative_recommendations' => $phases['postoperative_recommendations'] ?? [],
                ]);
            }
        }
    }

    private function createFAQs(Service $service, array $faqs, GoogleTranslateService $translator): void
    {
        if (empty($faqs)) {
            return;
        }

        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                foreach ($faqs as $faq) {
                    $service->frequentlyAskedQuestions()->create([
                        'question' => $faq['question'] ?? '',
                        'answer' => $faq['answer'] ?? '',
                        'lang' => $lang,
                    ]);
                }
                continue;
            }

            try {
                $textsToTranslate = [];
                foreach ($faqs as $faq) {
                    $textsToTranslate[] = $faq['question'] ?? '';
                    $textsToTranslate[] = $faq['answer'] ?? '';
                }

                $translated = $translator->translate($textsToTranslate, $lang);

                $index = 0;
                foreach ($faqs as $faq) {
                    $service->frequentlyAskedQuestions()->create([
                        'question' => $translated[$index] ?? ($faq['question'] ?? ''),
                        'answer' => $translated[$index + 1] ?? ($faq['answer'] ?? ''),
                        'lang' => $lang,
                    ]);
                    $index += 2;
                }

            } catch (\Exception $e) {
                \Log::error("Translation error for FAQs service {$service->id} to {$lang}: " . $e->getMessage());

                foreach ($faqs as $faq) {
                    $service->frequentlyAskedQuestions()->create([
                        'question' => $faq['question'] ?? '',
                        'answer' => $faq['answer'] ?? '',
                        'lang' => $lang,
                    ]);
                }
            }
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
    public function update_service(UpdateRequest $request, $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $service = Service::findOrFail($id);
            $data = $request->validated();

            $this->updateServiceStatus($service, $data);
            $this->updateServiceImage($service, $data);
            $this->updateTranslations($service->id, $data, $translator);
            $this->updateSurgeryPhases($service, $data, $translator);
            $this->updateFaqs($service, $data, $translator);
            $this->updateSampleImages($service, $data);
            $this->updateResultsGallery($service, $data);

            DB::commit();

            return ApiResponse::success(
                __('messages.service.success.updateService'),
                $service->load([
                    'serviceTranslation',
                    'surgeryPhases',
                    'frequentlyAskedQuestions',
                    'sampleImages',
                    'resultGallery',
                ]),
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_service', [
                'service_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.service.error.updateService'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    private function updateServiceStatus(Service $service, array $data): void
    {
        $updates = [];

        if (isset($data['status']) && $data['status'] !== $service->status) {
            $updates['status'] = $data['status'];
        }

        if (isset($data['title']) && $data['title'] !== $service->serviceTranslation()->where('lang', 'es')->first()?->title) {
            $updates['slug'] = Helpers::generateUniqueSlug(
                modelClass: Service::class,
                title: $data['title'],
                slugColumn: 'slug'
            );
        }

        if (!empty($updates)) {
            $service->update($updates);
        }
    }

    private function updateServiceImage(Service $service, array $data): void
    {
        if (empty($data['image']) || is_string($data['image'])) {
            return;
        }

        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }

        $path = Helpers::saveWebpFile($data['image'], "images/service/{$service->id}/service_image");
        $service->update(['image' => $path]);
    }

    private function updateTranslations(int $id, array $data, GoogleTranslateService $translator): void
    {
        $translationEs = ServiceTranslation::firstOrCreate(['service_id' => $id, 'lang' => 'es']);

        $titleChanged = isset($data['title']) && $data['title'] !== $translationEs->title;
        $descChanged = isset($data['description']) && $data['description'] !== $translationEs->description;

        if (!$titleChanged && !$descChanged) {
            return;
        }

        // Actualizar español
        $translationEs->update([
            'title' => $data['title'] ?? $translationEs->title,
            'description' => $data['description'] ?? $translationEs->description,
        ]);

        // Preparar textos para traducir
        $textsToTranslate = [];
        $changedFields = [];

        if ($titleChanged) {
            $textsToTranslate[] = $data['title'];
            $changedFields[] = 'title';
        }

        if ($descChanged) {
            $textsToTranslate[] = $data['description'];
            $changedFields[] = 'description';
        }

        // Traducir a otros idiomas
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                // UNA sola llamada API con todos los textos
                $translated = $translator->translate($textsToTranslate, $lang);

                $translation = ServiceTranslation::firstOrCreate([
                    'service_id' => $id,
                    'lang' => $lang
                ]);

                $updatedFields = [];
                foreach ($changedFields as $index => $field) {
                    $updatedFields[$field] = $translated[$index] ?? $translation->$field;
                }

                $translation->update($updatedFields);

            } catch (\Exception $e) {
                \Log::error("Translation error for service {$id} to {$lang}: " . $e->getMessage());
            }
        }
    }

    private function updateSurgeryPhases(Service $service, array $data, GoogleTranslateService $translator): void
    {
        if (empty($data['surgery_phases'])) {
            return;
        }

        // Eliminar fases existentes
        $service->surgeryPhases()->delete();

        $phases = $data['surgery_phases'];

        // Crear español
        $service->surgeryPhases()->create([
            'lang' => 'es',
            'recovery_time' => $phases['recovery_time'] ?? [],
            'preoperative_recommendations' => $phases['preoperative_recommendations'] ?? [],
            'postoperative_recommendations' => $phases['postoperative_recommendations'] ?? [],
        ]);

        // Traducir a otros idiomas
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                $textsToTranslate = [];
                $fieldMap = [];

                foreach ($phases as $key => $values) {
                    if (empty($values) || !is_array($values)) {
                        $fieldMap[$key] = [];
                        continue;
                    }

                    $fieldMap[$key] = [
                        'start' => count($textsToTranslate),
                        'count' => count($values)
                    ];

                    $textsToTranslate = array_merge($textsToTranslate, $values);
                }

                if (!empty($textsToTranslate)) {
                    $translated = $translator->translate($textsToTranslate, $lang);

                    $translatedData = ['lang' => $lang];
                    foreach ($fieldMap as $key => $map) {
                        if (empty($map)) {
                            $translatedData[$key] = [];
                        } else {
                            $translatedData[$key] = array_slice($translated, $map['start'], $map['count']);
                        }
                    }

                    $service->surgeryPhases()->create($translatedData);
                }

            } catch (\Exception $e) {
                \Log::error("Translation error for surgery phases service {$service->id} to {$lang}: " . $e->getMessage());

                // Fallback: usar valores originales
                $service->surgeryPhases()->create([
                    'lang' => $lang,
                    'recovery_time' => $phases['recovery_time'] ?? [],
                    'preoperative_recommendations' => $phases['preoperative_recommendations'] ?? [],
                    'postoperative_recommendations' => $phases['postoperative_recommendations'] ?? [],
                ]);
            }
        }
    }

    private function updateFaqs(Service $service, array $data, GoogleTranslateService $translator): void
    {
        if (empty($data['frequently_asked_questions'])) {
            return;
        }

        $processedIds = [];
        $faqsToTranslate = [];

        // === PASO 1: Procesar FAQs en español ===
        foreach ($data['frequently_asked_questions'] as $index => $faqData) {
            $faqId = $faqData['id'] ?? null;

            // Buscar FAQ existente en español por ID
            $faqEs = null;
            if ($faqId) {
                $faqEs = ServiceFaq::where('id', $faqId)
                    ->where('service_id', $service->id)
                    ->where('lang', 'es')
                    ->first();
            }

            // Si no existe, crear nueva
            if (!$faqEs) {
                $faqEs = new ServiceFaq([
                    'service_id' => $service->id,
                    'lang' => 'es'
                ]);
            }

            // Actualizar datos
            $faqEs->fill([
                'question' => $faqData['question'] ?? '',
                'answer' => $faqData['answer'] ?? '',
            ])->save();

            $processedIds[] = $faqEs->id;

            // Guardar para traducir después
            $faqsToTranslate[$index] = [
                'spanish_id' => $faqEs->id,
                'question' => $faqEs->question,
                'answer' => $faqEs->answer,
            ];
        }

        // === PASO 2: Traducir a todos los demás idiomas ===
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                // Preparar todos los textos para traducir en batch
                $textsToTranslate = [];
                foreach ($faqsToTranslate as $faq) {
                    $textsToTranslate[] = $faq['question'];
                    $textsToTranslate[] = $faq['answer'];
                }

                // UNA sola llamada API con todas las FAQs
                $translated = $translator->translate($textsToTranslate, $lang);

                // Obtener FAQs existentes en este idioma (por orden de creación)
                $existingFaqsInLang = ServiceFaq::where('service_id', $service->id)
                    ->where('lang', $lang)
                    ->orderBy('id')
                    ->get();

                // Procesar cada FAQ traducida
                $translatedIndex = 0;
                foreach ($faqsToTranslate as $index => $faq) {
                    // Intentar obtener la FAQ en esta posición
                    $existingFaq = $existingFaqsInLang->get($index);

                    if ($existingFaq) {
                        // Actualizar FAQ existente
                        $existingFaq->update([
                            'question' => $translated[$translatedIndex] ?? $faq['question'],
                            'answer' => $translated[$translatedIndex + 1] ?? $faq['answer'],
                        ]);
                        $processedIds[] = $existingFaq->id;
                    } else {
                        // Crear nueva FAQ
                        $newFaq = ServiceFaq::create([
                            'service_id' => $service->id,
                            'lang' => $lang,
                            'question' => $translated[$translatedIndex] ?? $faq['question'],
                            'answer' => $translated[$translatedIndex + 1] ?? $faq['answer'],
                        ]);
                        $processedIds[] = $newFaq->id;
                    }

                    $translatedIndex += 2;
                }

            } catch (\Exception $e) {
                \Log::error("Translation error for FAQs service {$service->id} to {$lang}: " . $e->getMessage());

                // Fallback: crear con texto original si falla traducción
                foreach ($faqsToTranslate as $faq) {
                    $fallbackFaq = ServiceFaq::create([
                        'service_id' => $service->id,
                        'lang' => $lang,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                    $processedIds[] = $fallbackFaq->id;
                }
            }
        }

        // === PASO 3: Eliminar FAQs obsoletas ===
        $service->frequentlyAskedQuestions()
            ->whereNotIn('id', $processedIds)
            ->delete();
    }

    private function updateSampleImages(Service $service, array $data): void
    {
        if (!isset($data['sample_images'])) {
            return;
        }

        $sample = $service->sampleImages;
        $fields = ['technique', 'recovery', 'postoperative_care'];
        $updates = [];

        foreach ($fields as $field) {
            if (isset($data['sample_images'][$field]) && !is_string($data['sample_images'][$field])) {
                // Eliminar imagen anterior si existe
                if ($sample?->$field && Storage::disk('public')->exists($sample->$field)) {
                    Storage::disk('public')->delete($sample->$field);
                }

                $updates[$field] = Helpers::saveWebpFile(
                    $data['sample_images'][$field],
                    "images/service/{$service->id}/sample_images"
                );
            } else {
                $updates[$field] = $sample?->$field;
            }
        }

        if (!empty($updates)) {
            $service->sampleImages()->updateOrCreate(
                ['service_id' => $service->id],
                $updates
            );
        }
    }

    private function updateResultsGallery(Service $service, array $data): void
    {
        if (!isset($data['results_gallery'])) {
            return;
        }

        $existingImages = $service->resultGallery()->pluck('path', 'id')->toArray();
        $newImages = [];

        foreach ($data['results_gallery'] as $item) {
            if (is_string($item)) {
                // Es una imagen existente (URL)
                $path = Helpers::removeAppUrl($item);
                $newImages[] = $path;
            } elseif ($item instanceof \Illuminate\Http\UploadedFile) {
                // Es una nueva imagen
                $path = Helpers::saveWebpFile($item, "images/service/{$service->id}/results_gallery");

                if ($path) {
                    $service->resultGallery()->create(['path' => $path]);
                    $newImages[] = $path;
                }
            }
        }

        // Eliminar imágenes que ya no están
        foreach ($existingImages as $id => $path) {
            if (!in_array($path, $newImages)) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $service->resultGallery()->where('id', $id)->delete();
            }
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

            return ApiResponse::success(
                __('messages.service.success.deleteService')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en delete_service: ' . $e->getMessage());
            return ApiResponse::error(
                __('messages.error.success.deleteService'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
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

            return ApiResponse::success(
                __('messages.service.success.updateStatus'),
                $service
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.service.error.updateStatus'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }
}
