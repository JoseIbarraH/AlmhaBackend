<?php

namespace App\Domains\Procedure\Controllers;

use App\Domains\Procedure\Models\ProcedureSection;
use App\Domains\Procedure\Models\ProcedureSectionTranslation;
use App\Domains\Procedure\Requests\UpdateProcedureRequest;
use App\Domains\Procedure\Models\Procedure;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Helpers\Helpers;
use Log;

class ProcedureContentController extends Controller
{

    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function update_procedure(UpdateProcedureRequest $request, $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $procedure = Procedure::findOrFail($id);

            // Llamamos a la lógica interna
            /* $this->updateProcedureData($procedure, $data);
            $this->updateTranslations($procedure, $data, $translator);

            if (isset($data['section'])) {
                $this->updateSection($procedure, $data['section'], $translator);
            } */

            if (isset($data['preStep'])) {
                $this->updatePreparationStep($procedure, $data['preStep'], $translator);
            }

            $procedure->touch();

            DB::commit();

            return ApiResponse::success("Correcto", $procedure, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error("Procedimiento no encontrado", 404);

        } catch (\Throwable $th) {
            DB::rollBack();
            // Error genérico para cualquier otra cosa (BD, código, etc.)
            Log::error("Error en update_procedure: " . $th->getMessage());
            return ApiResponse::error("Error interno del servidor", 500);
        }
    }

    private function updateProcedureData(Procedure $procedure, array $data)
    {
        $updates = [];

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if (!empty($procedure->image) && Storage::disk('public')->exists($procedure->image)) {
                Storage::disk('public')->delete($procedure->image);
            }

            $updates['image'] = Helpers::saveWebpFile($data['image'], "images/procedure/{$procedure->id}/procedure_image");
        }

        if (!empty($updates)) {
            $procedure->update($updates);
        }
    }

    private function updateTranslations(Procedure $procedure, array $data, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();
        $fields = ['title', 'subtitle'];

        $sourceTranslation = $procedure->translations()->firstOrNew([
            'procedure_id' => $procedure->id,
            'lang' => $sourceLang,
        ]);

        $changedFields = [];
        $textsToTranslate = [];

        foreach ($fields as $field) {
            if (
                array_key_exists($field, $data) &&
                $data[$field] !== $sourceTranslation->{$field}
            ) {
                $changedFields[] = $field;
                $textsToTranslate[] = $data[$field];
                $sourceTranslation->{$field} = $data[$field];
            }
        }

        if (empty($changedFields)) {
            return;
        }

        $sourceTranslation->save();

        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang) {
                continue;
            }

            $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

            $translation = $procedure->translations()->firstOrNew([
                'procedure_id' => $procedure->id,
                'lang' => $targetLang,
            ]);

            foreach ($changedFields as $index => $field) {
                $translation->{$field} = $translatedTexts[$index] ?? $translation->{$field};
            }

            $translation->save();
        }
    }

    private function updateSection(Procedure $procedure, array $sections, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();
        $procedureSections = [];
        $toTranslation = [];

        // 1. Procesar y guardar todas las secciones primero
        foreach ($sections as $index => $section) {
            $procedureSection = ProcedureSection::firstOrCreate([
                'procedure_id' => $procedure->id,
                'type' => $section['type']
            ]);

            // Actualizar imagen si existe
            if (isset($section['image']) && $section['image'] instanceof UploadedFile) {
                if (!empty($procedureSection->image) && Storage::disk('public')->exists($procedureSection->image)) {
                    Storage::disk('public')->delete($procedureSection->image);
                }

                $procedureSection->image = Helpers::saveWebpFile(
                    $section['image'],
                    "images/procedure/{$procedure->id}/procedure_section/{$section['type']}"
                );
                $procedureSection->save();
            }

            // Almacenar la sección para usar después
            $procedureSections[$index] = [
                'model' => $procedureSection,
                'data' => $section
            ];

            // Preparar textos para traducir (array simple, mantiene el orden)
            $toTranslation[] = $section['title'];
            $toTranslation[] = $section['contentOne'];
            $toTranslation[] = $section['contentTwo'];
        }

        // 2. Traducir por idioma (1 llamada por idioma)
        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang) {
                // Guardar en idioma original sin traducir
                foreach ($procedureSections as $index => $sectionData) {
                    $procedureSection = $sectionData['model'];
                    $section = $sectionData['data'];

                    $procedureSection->translations()->updateOrCreate(
                        [
                            'procedure_section_id' => $procedureSection->id,
                            'lang' => $targetLang
                        ],
                        [
                            'title' => $section['title'],
                            'content_one' => $section['contentOne'],
                            'content_two' => $section['contentTwo']
                        ]
                    );
                }
                continue;
            }

            // UNA SOLA llamada a la API por idioma con TODAS las secciones
            $translatedTexts = $translator->translate(
                $toTranslation,
                $targetLang,
                $sourceLang
            );

            Log::info("Traducciones a {$targetLang}:", [$translatedTexts]);

            // 3. Guardar traducciones para cada sección
            // Como el array viene ordenado, extraemos de 3 en 3
            $translationIndex = 0;
            foreach ($procedureSections as $index => $sectionData) {
                $procedureSection = $sectionData['model'];

                $procedureSection->translations()->updateOrCreate(
                    [
                        'procedure_section_id' => $procedureSection->id,
                        'lang' => $targetLang
                    ],
                    [
                        'title' => $translatedTexts[$translationIndex] ?? '',
                        'content_one' => $translatedTexts[$translationIndex + 1] ?? '',
                        'content_two' => $translatedTexts[$translationIndex + 2] ?? ''
                    ]
                );

                // Avanzar 3 posiciones para la siguiente sección
                $translationIndex += 3;
            }
        }
    }

    private function updatePreparationStep(Procedure $procedure, array $preStep, GoogleTranslateService $translator) {
        Log::info('', [$preStep]);
    }
}
