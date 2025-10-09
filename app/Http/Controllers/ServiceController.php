<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceSurgeryPhase;
use App\Models\ServiceTranslation;
use App\Models\Media;
use Illuminate\Http\Request;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Dashboard\Service\StoreRequest;

class ServiceController extends Controller
{
    // Listar servicios
    public function list_services(Request $request)
    {
        try {
            $query = Service::with(['media', 'serviceTranslation']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('serviceTranslation', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $paginate = $query->paginate(10);

            $paginate->getCollection()->transform(function ($service) {
                $translation = $service->serviceTranslation->first();
                return [
                    'id' => $service->id,
                    'status' => $service->status,
                    'title' => $translation->title ?? '',
                    'description' => $translation->description ?? '',
                    'created_at' => $service->created_at ? $service->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $service->updated_at ? $service->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Lista de servicios obtenida correctamente',
                'data' => $paginate
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en list_services: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Ver un servicio
    public function get_service($id)
    {
        try {
            $service = Service::with([

                'serviceTranslation',
                'frequentlyAskedQuestions',
                'surgeryPhases'
            ])->findOrFail($id);

            // Obtener traducción principal (puedes ajustar el idioma base)
            $translation = $service->serviceTranslation->first();

            $data = [
                'id' => $service->id,
                'status' => $service->status,
                'title' => $translation->title ?? '',
                'description' => $translation->description ?? '',
                'surgery_phases' => $service->surgeryPhases->map(function ($phase) {
                    return [
                        'id' => $phase->id,
                        'recovery_time' => $phase->recovery_time,
                        'preoperative_recommendations' => $phase->preoperative_recommendations,
                        'postoperative_recommendations' => $phase->postoperative_recommendations,
                        'lang' => $phase->lang,
                    ];
                }),
                'frequently_asked_questions' => $service->frequentlyAskedQuestions->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'lang' => $faq->lang,
                    ];
                }),

                'created_at' => $service->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $service->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Servicio obtenido correctamente',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el servicio',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function test_upload(Request $request)
    {
        dd([
            'all_request' => $request->all(),
            'sample_images_files' => $request->file('sample_images'),
            'result_images_files' => $request->file('result_images'),
            'has_sample_images' => $request->hasFile('sample_images'),
            'has_result_images' => $request->hasFile('result_images'),
        ]);
    }

    // Crear un servicio
    public function create_service(StoreRequest $request)
    {

        dd([
            'all_request' => $request->all(),
            'sample_images_files' => $request->file('sample_images'),
            'result_images_files' => $request->file('result_images'),
            'has_sample_images' => $request->hasFile('sample_images'),
            'has_result_images' => $request->hasFile('result_images'),
        ]);
        try {
            $data = $request->validated();

            // 1️⃣ Crear el servicio principal
            $service = Service::create([
                'status' => $data['status'] ?? 'inactive',
            ]);

            // 2️⃣ Crear traducciones del servicio (ES + EN)
            $titleEn = Helpers::translateBatch([$data['title']], 'es', 'en')[0] ?? '';
            $descEn = Helpers::translateBatch([$data['description']], 'es', 'en')[0] ?? '';

            ServiceTranslation::insert([
                [
                    'service_id' => $service->id,
                    'lang' => 'es',
                    'title' => $data['title'],
                    'description' => $data['description'],
                ],
                [
                    'service_id' => $service->id,
                    'lang' => 'en',
                    'title' => $titleEn,
                    'description' => $descEn,
                ],
            ]);

            // 3️⃣ Fases quirúrgicas
            if (!empty($data['surgery_phases'])) {
                foreach ($data['surgery_phases'] as $phase) {
                    $recovery = $phase['recovery_time'] ?? [];
                    $preop = $phase['preoperative_recommendations'] ?? [];
                    $postop = $phase['postoperative_recommendations'] ?? [];

                    $translatedRecovery = Helpers::translateBatch([(is_array($recovery) ? implode(', ', $recovery) : $recovery)], 'es', 'en')[0] ?? '';
                    $translatedPreop = Helpers::translateBatch([(is_array($preop) ? implode(', ', $preop) : $preop)], 'es', 'en')[0] ?? '';
                    $translatedPostop = Helpers::translateBatch([(is_array($postop) ? implode(', ', $postop) : $postop)], 'es', 'en')[0] ?? '';

                    // Español
                    $service->surgeryPhases()->create([
                        'recovery_time' => $recovery,
                        'preoperative_recommendations' => $preop,
                        'postoperative_recommendations' => $postop,
                        'lang' => 'es',
                    ]);

                    // Inglés
                    $service->surgeryPhases()->create([
                        'recovery_time' => explode(', ', $translatedRecovery),
                        'preoperative_recommendations' => explode(', ', $translatedPreop),
                        'postoperative_recommendations' => explode(', ', $translatedPostop),
                        'lang' => 'en',
                    ]);
                }
            }

            // 4️⃣ Preguntas frecuentes
            if (!empty($data['frequently_asked_questions'])) {
                foreach ($data['frequently_asked_questions'] as $faq) {
                    $question = $faq['question'] ?? '';
                    $answer = $faq['answer'] ?? '';

                    $questionEn = Helpers::translateBatch([$question], 'es', 'en')[0] ?? '';
                    $answerEn = Helpers::translateBatch([$answer], 'es', 'en')[0] ?? '';

                    $service->frequentlyAskedQuestions()->create([
                        'question' => $question,
                        'answer' => $answer,
                        'lang' => 'es',
                    ]);

                    $service->frequentlyAskedQuestions()->create([
                        'question' => $questionEn,
                        'answer' => $answerEn,
                        'lang' => 'en',
                    ]);
                }
            }

            // 5️⃣ Guardar imágenes de muestra
            if ($request->hasFile('sample_images')) {
                foreach ($request->file('sample_images') as $file) {
                    $path = $file->store('sample_images', 'public');
                    $service->sampleImages()->create([
                        'technique' => $data['technique'] ?? null,
                        'recovery' => $data['recovery'] ?? null,
                        'postoperative_care' => $data['postoperative_care'] ?? null,
                        'path' => $path,
                    ]);
                }
            }


            // 6️⃣ Guardar imágenes de resultados
            if ($request->hasFile('result_images')) {
                foreach ($request->file('result_images') as $file) {
                    $path = $file->store('result_gallery', 'public');

                    $service->resultGallery()->create([
                        'path' => $path,
                    ]);
                }
            }


            // 7️⃣ Respuesta final
            return response()->json([
                'success' => true,
                'message' => 'Servicio creado correctamente',
                'data' => $service->load([
                    'serviceTranslation',
                    'surgeryPhases',
                    'frequentlyAskedQuestions',
                    'sampleImages',
                    'resultGallery',
                ]),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Error en create_service: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el servicio',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    // Actualizar un servicio
    public function update_service(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);

            $data = $request->validate([
                'title' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'nullable|in:active,inactive',
                'images.*' => 'nullable|image|max:5120',
            ]);

            $titleEn = Helpers::translateBatch([$data['title']], 'es', 'en')[0] ?? '';
            $descEn = Helpers::translateBatch([$data['description'] ?? ''], 'es', 'en')[0] ?? '';

            $service->update(['status' => $data['status'] ?? $service->status]);

            // Traducciones
            ServiceTranslation::updateOrCreate(
                ['service_id' => $service->id, 'lang' => 'es'],
                ['title' => $data['title'], 'description' => $data['description'] ?? '']
            );

            ServiceTranslation::updateOrCreate(
                ['service_id' => $service->id, 'lang' => 'en'],
                ['title' => $titleEn, 'description' => $descEn]
            );

            // Guardar nuevas imágenes si las hay
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store("services/{$service->id}", 'public');
                    $service->media()->create([
                        'type' => 'gallery',
                        'media_type' => 'image',
                        'path' => $path,
                        'title' => $file->getClientOriginalName()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente',
                'data' => $service
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en update_service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar un servicio
    public function delete_service($id)
    {
        try {
            $service = Service::findOrFail($id);

            // Borrar relaciones dependientes para evitar error de FK
            $service->serviceTranslation()->delete();
            $service->frequentlyAskedQuestions()->delete();
            $service->surgeryPhases()->delete();
            $service->media()->delete();

            // Borrar carpeta de imágenes
            Storage::disk('public')->deleteDirectory("services/{$service->id}");

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Servicio eliminado correctamente'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en delete_service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el servicio',
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
                'message' => 'Estado actualizado correctamente',
                'data' => $service
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}