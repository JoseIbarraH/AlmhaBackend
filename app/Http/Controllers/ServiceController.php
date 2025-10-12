<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Service\UpdateRequest;
use App\Http\Requests\Dashboard\Service\StoreRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceTranslation;
use Illuminate\Http\Request;
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
            $perPage = 12;

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
            $services = $query->paginate($perPage);

            // Transformar la colección paginada
            $services->getCollection()->transform(function ($service) {
                $translation = $service->serviceTranslation->first();

                return [
                    'id' => $service->id,
                    'status' => $service->status,
                    'service_image' => $service->service_image ? url("storage/{$service->service_image}") : null,
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

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.listServices'),
                'data' => $services
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en list_services: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.listServices'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Ver un servicio
    public function get_service($id, Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale()); // o 'es' por default

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
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',
                'service_image' => url('storage', $service->service_image),
                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.getService'),
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.getService'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Crear un servicio
    public function create_service(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $service = Service::create([
                'status' => $data['status'] ?? 'inactive',
                'service_image' => ''
            ]);

            $service->service_image = Helpers::saveWebpFile($data['service_image'], "images/service/{$service->id}/service_image");
            $service->save();

            ServiceTranslation::create([
                'service_id' => $service->id,
                'lang' => 'es',
                'title' => $data['title'],
                'description' => $data['description'],
            ]);

            ServiceTranslation::create([
                'service_id' => $service->id,
                'lang' => 'en',
                'title' => Helpers::translateBatch([$data['title']], 'es', 'en')[0] ?? '',
                'description' => Helpers::translateBatch([$data['description']], 'es', 'en')[0] ?? '',
            ]);

            if (!empty($data['surgery_phases'])) {
                foreach ($data['surgery_phases'] as $phase) {
                    $fields = [
                        'recovery_time',
                        'preoperative_recommendations',
                        'postoperative_recommendations',
                    ];

                    $translated = collect($fields)->mapWithKeys(fn($f) => [
                        $f => explode(', ', Helpers::translateBatch(
                            [(array) $phase[$f] ? implode(', ', (array) $phase[$f]) : ''],
                            'es',
                            'en'
                        )[0] ?? '')
                    ])->toArray();

                    $service->surgeryPhases()->create($phase + ['lang' => 'es']);
                    $service->surgeryPhases()->create($translated + ['lang' => 'en']);
                }
            }

            if (!empty($data['frequently_asked_questions'])) {
                foreach ($data['frequently_asked_questions'] as $faq) {

                    $service->frequentlyAskedQuestions()->create([
                        'question' => $faq['question'] ?? '',
                        'answer' => $faq['answer'] ?? '',
                        'lang' => 'es',
                    ]);

                    $service->frequentlyAskedQuestions()->create([
                        'question' => Helpers::translateBatch([$faq['question'] ?? ''], 'es', 'en')[0] ?? '',
                        'answer' => Helpers::translateBatch([$faq['answer'] ?? ''], 'es', 'en')[0] ?? '',
                        'lang' => 'en',
                    ]);
                }
            }

            if ($request->hasFile('sample_images')) {
                $service->sampleImages()->create([
                    'technique' => Helpers::saveWebpFile($data['sample_images']['technique'], "images/service/{$service->id}/sample_images") ?? null,
                    'recovery' => Helpers::saveWebpFile($data['sample_images']['recovery'], "images/service/{$service->id}/sample_images") ?? null,
                    'postoperative_care' => Helpers::saveWebpFile($data['sample_images']['postoperative_care'], "images/service/{$service->id}/sample_images") ?? null
                ]);
            }

            if ($request->hasFile('results_gallery')) {
                foreach ($request->file('results_gallery') as $file) {
                    $service->resultGallery()->create([
                        'path' => Helpers::saveWebpFile($file, "images/service/{$service->id}/results_gallery") ?? null
                    ]);
                }
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
            Log::error('Error en create_service: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.service.error.createService'),
                'error' => $e->getMessage(),
            ], 500);
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

            // 1️⃣ Actualizar status solo si cambió
            if (($data['status'] ?? null) !== $service->status) {
                $service->update(['status' => $data['status']]);
            }

            if (isset($data['service_image']) && !is_string($data['service_image'])) {
                if (!empty($service->service_image) && Storage::disk('public')->exists($service->service_image)) {
                    Storage::disk('public')->delete($service->service_image);
                }
                $path = Helpers::saveWebpFile($data['service_image'], "images/service/{$service->id}/service_image");
                $service->update(['service_image' => $path]);
            }

            // 2️⃣ Obtener traducciones actuales
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
        try {
            $service = Service::findOrFail($id);
            $service->delete();

            return response()->json([
                'success' => true,
                'message' => __('messages.service.success.deleteService')
            ]);
        } catch (\Throwable $e) {
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
}
