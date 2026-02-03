<?php

namespace App\Domains\Procedure\Controllers;

use App\Domains\Procedure\Models\ProcedureFaq;
use App\Domains\Procedure\Models\ProcedurePostoperativeInstruction;
use App\Domains\Procedure\Models\ProcedurePreparationStep;
use App\Domains\Procedure\Models\ProcedureRecoveryPhase;
use App\Domains\Procedure\Models\ProcedureResultGallery;
use App\Domains\Procedure\Models\ProcedureTranslation;
use App\Domains\Procedure\Requests\UpdateProcedureRequest;
use App\Domains\Procedure\Models\ProcedureSection;
use App\Domains\Procedure\Models\Procedure;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Helpers\Helpers;
use Log;

class ProcedureContentController extends Controller
{

    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function create_procedure(Request $request, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $procedure = Procedure::create([
                'user_id' => auth()->id(),
                'slug' => uniqid('temp-'),
                'views' => 0,
                'image' => '',
                'status' => 'inactive'
            ]);

            $this->createTranslations($procedure, $data, $translator);

            $procedure->slug = null;
            $procedure->save();

            \Illuminate\Support\Facades\Cache::tags(['procedures', 'navbar'])->flush();

            DB::commit();

            return ApiResponse::success(
                message: ('messages.procedure.success.createProcedure'),
                code: 201
            );

        } catch (\Throwable $th) {
            Log::error('Error en crear el procedimiento' . $th->getMessage());

            return ApiResponse::error(
                message: ('messages.procedure.error.createProcedure'),
                code: 500
            );
        }
    }

    private function createTranslations(Procedure $procedure, array $data, GoogleTranslateService $translator)
    {
        $currentLocale = app()->getLocale();

        // Determinar texto base y su idioma original
        // Si no viene título, usamos 'New Procedure' y asumimos inglés como origen
        $baseTitle = $data['title'] ?? 'New Procedure';
        $baseLang = isset($data['title']) ? $currentLocale : 'en';

        foreach ($this->languages as $targetLang) {
            $titleToSave = '';

            // 1. Si es el idioma base, usar texto original
            if ($targetLang === $baseLang) {
                $titleToSave = $baseTitle;
            }
            // 2. Si no, traducir desde el idioma base al idioma destino
            else {
                try {
                    $translated = $translator->translate($baseTitle, $targetLang, $baseLang);
                    $titleToSave = is_array($translated) ? $translated[0] : $translated;
                } catch (\Exception $e) {
                    Log::warning("Translation failed for procedure {$procedure->id} to {$targetLang}", [
                        'error' => $e->getMessage()
                    ]);
                    $titleToSave = $baseTitle; // Fallback
                }
            }

            ProcedureTranslation::create([
                'procedure_id' => $procedure->id,
                'lang' => $targetLang,
                'title' => $titleToSave,
                'subtitle' => null
            ]);
        }
    }

    public function update_procedure(UpdateProcedureRequest $request, $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $procedure = Procedure::findOrFail($id);
            $data = $request->validated();

            $this->updateProcedureData($procedure, $data);

            $this->updateTranslations($procedure, $data, $translator);

            if (isset($data['section'])) {
                $this->updateSection($procedure, $data['section'], $translator);
            }

            if (isset($data['preStep'])) {
                $this->updatePreparationStep($procedure, $data['preStep'], $translator);
            }

            if (isset($data['phase'])) {
                $this->updateRecoveryPhase($procedure, $data['phase'], $translator);
            }

            if (isset($data['faq'])) {
                $this->updateFaq($procedure, $data['faq'], $translator);
            }

            if (isset($data['do'])) {
                $this->updateDo($procedure, $data['do'], $translator);
            }

            if (isset($data['dont'])) {
                $this->updateDont($procedure, $data['dont'], $translator);
            }

            if (isset($data['gallery'])) {
                $this->updateGallery($procedure, $data['gallery']);
            }

            $procedure->touch();

            \Illuminate\Support\Facades\Cache::tags(['procedures', 'navbar'])->flush();

            DB::commit();

            return ApiResponse::success("Correcto", $procedure, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error("Procedimiento no encontrado", 404);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error en update_procedure: " . $th);
            return ApiResponse::error("Error interno del servidor", 500);
        }
    }

    private function updateProcedureData(Procedure $procedure, array $data)
    {
        $updates = [];

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (array_key_exists('category', $data)) {
            $updates['category_code'] = $data['category'];
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($procedure->image !== null) {
                $path = Helpers::removeAppUrl($procedure->image);
                if (!empty($path) && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
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
            // Solo procesar si el campo está presente en $data
            if (array_key_exists($field, $data)) {
                // Si es un campo nuevo o cambió su valor
                if (!$sourceTranslation->exists || $data[$field] !== $sourceTranslation->{$field}) {
                    $changedFields[] = $field;
                    $textsToTranslate[] = $data[$field];
                }
                // Actualizar el valor en el idioma fuente
                $sourceTranslation->{$field} = $data[$field];
            }
        }

        // Guardar la traducción fuente solo si hay cambios o es nueva
        if (!empty($changedFields) || !$sourceTranslation->exists) {
            $sourceTranslation->save();
        }

        // Si no hay campos que traducir, salir
        if (empty($changedFields)) {
            return;
        }

        // Traducir a otros idiomas
        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang) {
                continue;
            }

            $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

            $translation = $procedure->translations()->firstOrNew([
                'procedure_id' => $procedure->id,
                'lang' => $targetLang,
            ]);

            // Solo actualizar los campos que cambiaron
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

        foreach ($sections as $index => $section) {
            $procedureSection = ProcedureSection::firstOrCreate([
                'procedure_id' => $procedure->id,
                'type' => $section['type'],
            ]);

            if (isset($section['image']) && $section['image'] instanceof UploadedFile) {
                if ($procedureSection->image) {
                    $urlClean = Helpers::removeAppUrl($procedureSection->image);
                    if (Storage::disk('public')->exists($urlClean)) {
                        Storage::disk('public')->delete($urlClean);
                    }
                }

                $procedureSection->image = Helpers::saveWebpFile(
                    $section['image'],
                    "images/procedure/{$procedure->id}/procedure_section/{$section['type']}"
                );
                $procedureSection->save();
            }

            // Detectar campos enviados
            $fields = [];

            foreach (['title', 'contentOne', 'contentTwo'] as $field) {
                if (array_key_exists($field, $section)) {
                    $fields[] = $field;
                    $toTranslation[] = $section[$field];
                }
            }

            $procedureSections[] = [
                'model' => $procedureSection,
                'data' => $section,
                'fields' => $fields,
            ];
        }

        // 2️⃣ Guardar idioma original (SIN limpiar campos)
        foreach ($procedureSections as $sectionData) {
            if (empty($sectionData['fields'])) {
                continue;
            }

            $dataToUpdate = [];

            foreach ($sectionData['fields'] as $field) {
                if ($field === 'title') {
                    $dataToUpdate['title'] = $sectionData['data']['title'];
                }
                if ($field === 'contentOne') {
                    $dataToUpdate['content_one'] = $sectionData['data']['contentOne'];
                }
                if ($field === 'contentTwo') {
                    $dataToUpdate['content_two'] = $sectionData['data']['contentTwo'];
                }
            }

            if (!empty($dataToUpdate)) {
                $sectionData['model']->translations()->updateOrCreate(
                    [
                        'procedure_section_id' => $sectionData['model']->id,
                        'lang' => $sourceLang,
                    ],
                    $dataToUpdate
                );
            }
        }

        // 3️⃣ Traducciones (1 llamada por idioma)
        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang) {
                continue;
            }

            if (empty($toTranslation)) {
                continue;
            }

            $translatedTexts = $translator->translate(
                $toTranslation,
                $targetLang,
                $sourceLang
            );

            Log::info("Traducciones a {$targetLang}", $translatedTexts);

            $translationIndex = 0;

            foreach ($procedureSections as $sectionData) {
                if (empty($sectionData['fields'])) {
                    continue;
                }

                $dataToUpdate = [];

                foreach ($sectionData['fields'] as $field) {
                    $value = $translatedTexts[$translationIndex] ?? '';
                    $translationIndex++;

                    if ($field === 'title') {
                        $dataToUpdate['title'] = $value;
                    }
                    if ($field === 'contentOne') {
                        $dataToUpdate['content_one'] = $value;
                    }
                    if ($field === 'contentTwo') {
                        $dataToUpdate['content_two'] = $value;
                    }
                }

                if (!empty($dataToUpdate)) {
                    $sectionData['model']->translations()->updateOrCreate(
                        [
                            'procedure_section_id' => $sectionData['model']->id,
                            'lang' => $targetLang,
                        ],
                        $dataToUpdate
                    );
                }
            }
        }
    }

    private function updatePreparationStep(Procedure $procedure, array $preStep, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();

        if (isset($preStep['deleted'])) {
            ProcedurePreparationStep::whereIn('id', $preStep['deleted'])->delete();
        }

        if (isset($preStep['new'])) {
            $toTranslation = [];
            $procedurePreSteps = [];

            foreach ($preStep['new'] as $index => $step) {
                if (!is_array($step)) {
                    continue;
                }

                $procedurePreStep = ProcedurePreparationStep::firstOrCreate([
                    'procedure_id' => $procedure->id,
                    'order' => $step['order'] ?? 0,
                ]);

                $procedurePreSteps[$index] = [
                    'model' => $procedurePreStep,
                    'data' => $step,
                ];

                $toTranslation[] = $step['title'] ?? '';
                $toTranslation[] = $step['description'] ?? '';
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedurePreSteps as $preStepData) {
                        $preStepData['model']->translations()->updateOrCreate(
                            [
                                'procedure_preparation_id' => $preStepData['model']->id,
                                'lang' => $targetLang
                            ],
                            [
                                'title' => $preStepData['data']['title'] ?? '',
                                'description' => $preStepData['data']['description'] ?? '',
                            ]
                        );
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($toTranslation, $targetLang, $sourceLang);

                $translationIndex = 0;
                foreach ($procedurePreSteps as $preStepData) {
                    $preStepData['model']->translations()->updateOrCreate(
                        [
                            'procedure_preparation_id' => $preStepData['model']->id,
                            'lang' => $targetLang
                        ],
                        [
                            'title' => $translatedTexts[$translationIndex] ?? '',
                            'description' => $translatedTexts[$translationIndex + 1] ?? '',
                        ]
                    );

                    $translationIndex += 2;
                }
            }
        }

        if (isset($preStep['updated'])) {
            $toTranslation = [];
            $procedurePreSteps = [];

            foreach ($preStep['updated'] as $index => $step) {
                if (!is_array($step) || !isset($step['id'])) {
                    continue; // Debe tener ID para actualizar
                }

                // Buscar el registro existente por ID
                $procedurePreStep = ProcedurePreparationStep::findOrFail($step['id']);

                // Actualizar campos si vienen en $step
                if (isset($step['order'])) {
                    $procedurePreStep->order = $step['order'];
                }

                $procedurePreStep->save();

                $procedurePreSteps[$index] = [
                    'model' => $procedurePreStep,
                    'data' => $step,
                ];

                // Solo agregar a traducción si los campos existen y son diferentes
                $translation = $procedurePreStep->translations()
                    ->where('lang', $sourceLang)
                    ->first();

                if (isset($step['title']) && (!$translation || $step['title'] !== $translation->title)) {
                    $toTranslation[] = $step['title'];
                } else {
                    $toTranslation[] = null; // Marcador para no traducir
                }

                if (isset($step['description']) && (!$translation || $step['description'] !== $translation->description)) {
                    $toTranslation[] = $step['description'];
                } else {
                    $toTranslation[] = null; // Marcador para no traducir
                }
            }

            // Filtrar valores null antes de traducir
            $textsToTranslate = array_filter($toTranslation, fn($text) => $text !== null);

            if (empty($textsToTranslate)) {
                return;
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedurePreSteps as $preStepData) {
                        $updateData = [];

                        if (isset($preStepData['data']['title'])) {
                            $updateData['title'] = $preStepData['data']['title'];
                        }

                        if (isset($preStepData['data']['description'])) {
                            $updateData['description'] = $preStepData['data']['description'];
                        }

                        if (!empty($updateData)) {
                            $preStepData['model']->translations()->updateOrCreate(
                                [
                                    'procedure_preparation_id' => $preStepData['model']->id,
                                    'lang' => $targetLang
                                ],
                                $updateData
                            );
                        }
                    }
                    continue;
                }

                // Traducir solo los textos que cambiaron
                $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                // Reconstruir array con nulls en las posiciones originales
                $translationIndex = 0;
                $fullTranslations = [];

                foreach ($toTranslation as $originalText) {
                    if ($originalText === null) {
                        $fullTranslations[] = null;
                    } else {
                        $fullTranslations[] = $translatedTexts[$translationIndex] ?? null;
                        $translationIndex++;
                    }
                }

                // Aplicar traducciones
                $arrayIndex = 0;
                foreach ($procedurePreSteps as $preStepData) {
                    $updateData = [];

                    // Title
                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['title'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    // Description
                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['description'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if (!empty($updateData)) {
                        $preStepData['model']->translations()->updateOrCreate(
                            [
                                'procedure_preparation_id' => $preStepData['model']->id,
                                'lang' => $targetLang
                            ],
                            $updateData
                        );
                    }
                }
            }
        }
    }

    private function updateRecoveryPhase(Procedure $procedure, array $phases, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();

        if (isset($phases['deleted'])) {
            ProcedureRecoveryPhase::whereIn('id', $phases['deleted'])->delete();
        }

        if (isset($phases['new'])) {
            $toTranslation = [];
            $procedurePhases = [];

            foreach ($phases['new'] as $index => $phase) {
                if (!is_array($phase)) {
                    continue;
                }

                $procedurePhase = ProcedureRecoveryPhase::create([
                    'procedure_id' => $procedure->id,
                    'order' => $phase['order'] ?? 0,
                ]);

                $procedurePhases[$index] = [
                    'model' => $procedurePhase,
                    'data' => $phase,
                ];

                $toTranslation[] = $phase['period'] ?? '';
                $toTranslation[] = $phase['title'] ?? '';
                $toTranslation[] = $phase['description'] ?? '';
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedurePhases as $phaseData) {
                        $phaseData['model']->translations()->updateOrCreate(
                            [
                                'procedure_recovery_phase_id' => $phaseData['model']->id,
                                'lang' => $targetLang
                            ],
                            [
                                'period' => $phaseData['data']['period'] ?? '',
                                'title' => $phaseData['data']['title'] ?? '',
                                'description' => $phaseData['data']['description'] ?? '',
                            ]
                        );
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($toTranslation, $targetLang, $sourceLang);

                $translationIndex = 0;
                foreach ($procedurePhases as $phaseData) {
                    $phaseData['model']->translations()->updateOrCreate(
                        [
                            'procedure_recovery_phase_id' => $phaseData['model']->id,
                            'lang' => $targetLang
                        ],
                        [
                            'period' => $translatedTexts[$translationIndex] ?? '',
                            'title' => $translatedTexts[$translationIndex + 1] ?? '',
                            'description' => $translatedTexts[$translationIndex + 2] ?? '',
                        ]
                    );

                    $translationIndex += 3;
                }
            }
        }

        if (isset($phases['updated'])) {
            $toTranslation = [];
            $procedurePhases = [];

            Log::info('Updated Entro');

            foreach ($phases['updated'] as $index => $phase) {
                if (!is_array($phase) || !isset($phase['id'])) {
                    Log::info('Updated Entro validacion');
                    continue; // Debe tener ID para actualizar
                }

                Log::info('Updated Entro 2');

                $procedurePhase = ProcedureRecoveryPhase::findOrFail($phase['id']);

                // Actualizar campos si vienen en $step
                if (isset($phase['order'])) {
                    $procedurePhase->order = $phase['order'];
                }

                $procedurePhase->save();

                $procedurePhases[$index] = [
                    'model' => $procedurePhase,
                    'data' => $phase,
                ];

                // Solo agregar a traducción si los campos existen y son diferentes
                $translation = $procedurePhase->translations()
                    ->where('lang', $sourceLang)
                    ->first();

                if (isset($phase['period']) && (!$translation || $phase['period'] !== $translation->period)) {
                    $toTranslation[] = $phase['period'];
                } else {
                    $toTranslation[] = null;
                }

                if (isset($phase['title']) && (!$translation || $phase['title'] !== $translation->title)) {
                    $toTranslation[] = $phase['title'];
                } else {
                    $toTranslation[] = null;
                }

                if (isset($phase['description']) && (!$translation || $phase['description'] !== $translation->description)) {
                    $toTranslation[] = $phase['description'];
                } else {
                    $toTranslation[] = null;
                }
            }

            // Filtrar valores null antes de traducir
            $textsToTranslate = array_filter($toTranslation, fn($text) => $text !== null);

            if (empty($textsToTranslate)) {
                return;
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedurePhases as $phaseData) {
                        $updateData = [];

                        if (isset($phaseData['data']['period'])) {
                            $updateData['period'] = $phaseData['data']['period'];
                        }

                        if (isset($phaseData['data']['title'])) {
                            $updateData['title'] = $phaseData['data']['title'];
                        }

                        if (isset($phaseData['data']['description'])) {
                            $updateData['description'] = $phaseData['data']['description'];
                        }

                        if (!empty($updateData)) {
                            $phaseData['model']->translations()->updateOrCreate(
                                [
                                    'procedure_recovery_phase_id' => $phaseData['model']->id,
                                    'lang' => $targetLang
                                ],
                                $updateData
                            );
                        }
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                $translationIndex = 0;
                $fullTranslations = [];

                foreach ($toTranslation as $originalText) {
                    if ($originalText === null) {
                        $fullTranslations[] = null;
                    } else {
                        $fullTranslations[] = $translatedTexts[$translationIndex] ?? null;
                        $translationIndex++;
                    }
                }

                $arrayIndex = 0;
                foreach ($procedurePhases as $phaseData) {
                    $updateData = [];

                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['period'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['title'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['description'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if (!empty($updateData)) {
                        $phaseData['model']->translations()->updateOrCreate(
                            [
                                'procedure_recovery_phase_id' => $phaseData['model']->id,
                                'lang' => $targetLang
                            ],
                            $updateData
                        );
                    }
                }
            }
        }
    }

    private function updateFaq(Procedure $procedure, array $faqs, GoogleTranslateService $translator)
    {

        $sourceLang = app()->getLocale();

        if (isset($faqs['deleted'])) {
            ProcedurePreparationStep::whereIn('id', $faqs['deleted'])->delete();
        }

        if (isset($faqs['new'])) {
            $toTranslation = [];
            $procedureFaqs = [];

            foreach ($faqs['new'] as $index => $faq) {
                if (!is_array($faq)) {
                    continue;
                }

                $procedureFaq = ProcedureFaq::firstOrCreate([
                    'procedure_id' => $procedure->id,
                    'order' => $faq['order'] ?? 0,
                ]);

                $procedureFaqs[$index] = [
                    'model' => $procedureFaq,
                    'data' => $faq,
                ];

                $toTranslation[] = $faq['question'] ?? '';
                $toTranslation[] = $faq['answer'] ?? '';
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedureFaqs as $faqData) {
                        $faqData['model']->translations()->updateOrCreate(
                            [
                                'procedure_faq_id' => $faqData['model']->id,
                                'lang' => $targetLang
                            ],
                            [
                                'question' => $faqData['data']['question'] ?? '',
                                'answer' => $faqData['data']['answer'] ?? '',
                            ]
                        );
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($toTranslation, $targetLang, $sourceLang);

                $translationIndex = 0;
                foreach ($procedureFaqs as $faqData) {
                    $faqData['model']->translations()->updateOrCreate(
                        [
                            'procedure_faq_id' => $faqData['model']->id,
                            'lang' => $targetLang
                        ],
                        [
                            'question' => $translatedTexts[$translationIndex] ?? '',
                            'answer' => $translatedTexts[$translationIndex + 1] ?? '',
                        ]
                    );

                    $translationIndex += 2;
                }
            }
        }

        if (isset($faqs['updated'])) {
            $toTranslation = [];
            $procedureFaqs = [];

            foreach ($faqs['updated'] as $index => $faq) {
                if (!is_array($faq) || !isset($faq['id'])) {
                    continue; // Debe tener ID para actualizar
                }

                // Buscar el registro existente por ID
                $procedureFaq = ProcedureFaq::findOrFail($faq['id']);

                // Actualizar campos si vienen en $step
                if (isset($step['order'])) {
                    $procedureFaq->order = $faq['order'];
                }

                $procedureFaq->save();

                $procedureFaqs[$index] = [
                    'model' => $procedureFaq,
                    'data' => $faq,
                ];

                // Solo agregar a traducción si los campos existen y son diferentes
                $translation = $procedureFaq->translations()
                    ->where('lang', $sourceLang)
                    ->first();

                if (isset($faq['question']) && (!$translation || $faq['question'] !== $translation->question)) {
                    $toTranslation[] = $faq['question'];
                } else {
                    $toTranslation[] = null; // Marcador para no traducir
                }

                if (isset($faq['answer']) && (!$translation || $faq['answer'] !== $translation->answer)) {
                    $toTranslation[] = $faq['answer'];
                } else {
                    $toTranslation[] = null; // Marcador para no traducir
                }
            }

            // Filtrar valores null antes de traducir
            $textsToTranslate = array_filter($toTranslation, fn($text) => $text !== null);

            if (empty($textsToTranslate)) {
                return;
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedureFaqs as $faqData) {
                        $updateData = [];

                        if (isset($faqData['data']['question'])) {
                            $updateData['question'] = $faqData['data']['question'];
                        }

                        if (isset($faqData['data']['answer'])) {
                            $updateData['answer'] = $faqData['data']['answer'];
                        }

                        if (!empty($updateData)) {
                            $faqData['model']->translations()->updateOrCreate(
                                [
                                    'procedure_faq_id' => $faqData['model']->id,
                                    'lang' => $targetLang
                                ],
                                $updateData
                            );
                        }
                    }
                    continue;
                }

                // Traducir solo los textos que cambiaron
                $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                // Reconstruir array con nulls en las posiciones originales
                $translationIndex = 0;
                $fullTranslations = [];

                foreach ($toTranslation as $originalText) {
                    if ($originalText === null) {
                        $fullTranslations[] = null;
                    } else {
                        $fullTranslations[] = $translatedTexts[$translationIndex] ?? null;
                        $translationIndex++;
                    }
                }

                // Aplicar traducciones
                $arrayIndex = 0;
                foreach ($procedureFaqs as $faqData) {
                    $updateData = [];

                    // Title
                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['question'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    // Description
                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['answer'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if (!empty($updateData)) {
                        $faqData['model']->translations()->updateOrCreate(
                            [
                                'procedure_faq_id' => $faqData['model']->id,
                                'lang' => $targetLang
                            ],
                            $updateData
                        );
                    }
                }
            }
        }
    }

    private function updateDo(Procedure $procedure, array $dos, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();

        if (isset($dos['deleted'])) {
            ProcedurePostoperativeInstruction::whereIn('id', $dos['deleted'])->delete();
        }

        if (isset($dos['new'])) {
            $toTranslation = [];
            $procedureDos = [];

            foreach ($dos['new'] as $index => $do) {
                if (!is_array($do)) {
                    continue;
                }

                $procedureDo = ProcedurePostoperativeInstruction::create([
                    'procedure_id' => $procedure->id,
                    'type' => 'do',
                    'order' => $do['order'],
                ]);

                $procedureDos[$index] = [
                    'model' => $procedureDo,
                    'data' => $do,
                ];

                $toTranslation[] = $do['content'] ?? '';
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedureDos as $doData) {
                        $doData['model']->translations()->updateOrCreate(
                            [
                                'procedure_postoperative_instruction_id' => $doData['model']->id,
                                'lang' => $targetLang
                            ],
                            [
                                'content' => $doData['data']['content'] ?? '',
                            ]
                        );
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($toTranslation, $targetLang, $sourceLang);

                $translationIndex = 0;
                foreach ($procedureDos as $doData) {
                    $doData['model']->translations()->updateOrCreate(
                        [
                            'procedure_postoperative_instruction_id' => $doData['model']->id,
                            'lang' => $targetLang
                        ],
                        [
                            'content' => $translatedTexts[$translationIndex] ?? '',
                        ]
                    );

                    $translationIndex++;
                }
            }
        }

        if (isset($dos['updated'])) {
            $toTranslation = [];
            $procedureDos = [];

            foreach ($dos['updated'] as $index => $do) {
                if (!is_array($do) || !isset($do['id'])) {
                    continue; // Debe tener ID para actualizar
                }

                $procedureDo = ProcedurePostoperativeInstruction::findOrFail($do['id']);

                // Actualizar campos si vienen en $step
                if (isset($do['order'])) {
                    $procedureDo->order = $do['order'];
                }

                $procedureDo->save();

                $procedureDos[$index] = [
                    'model' => $procedureDo,
                    'data' => $do,
                ];

                // Solo agregar a traducción si los campos existen y son diferentes
                $translation = $procedureDo->translations()
                    ->where('lang', $sourceLang)
                    ->first();

                if (isset($do['content']) && (!$translation || $do['content'] !== $translation->content)) {
                    $toTranslation[] = $do['content'];
                } else {
                    $toTranslation[] = null;
                }
            }

            // Filtrar valores null antes de traducir
            $textsToTranslate = array_filter($toTranslation, fn($text) => $text !== null);

            if (empty($textsToTranslate)) {
                return;
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedureDos as $doData) {
                        $updateData = [];

                        if (isset($doData['data']['content'])) {
                            $updateData['content'] = $doData['data']['content'];
                        }

                        if (!empty($updateData)) {
                            $doData['model']->translations()->updateOrCreate(
                                [
                                    'procedure_postoperative_instruction_id' => $doData['model']->id,
                                    'lang' => $targetLang
                                ],
                                $updateData
                            );
                        }
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                $translationIndex = 0;
                $fullTranslations = [];

                foreach ($toTranslation as $originalText) {
                    if ($originalText === null) {
                        $fullTranslations[] = null;
                    } else {
                        $fullTranslations[] = $translatedTexts[$translationIndex] ?? null;
                        $translationIndex++;
                    }
                }

                $arrayIndex = 0;
                foreach ($procedureDos as $doData) {
                    $updateData = [];

                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['content'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if (!empty($updateData)) {
                        $doData['model']->translations()->updateOrCreate(
                            [
                                'procedure_postoperative_instruction_id' => $doData['model']->id,
                                'lang' => $targetLang
                            ],
                            $updateData
                        );
                    }
                }
            }
        }
    }

    private function updateDont(Procedure $procedure, array $donts, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();

        if (isset($donts['deleted'])) {
            ProcedurePostoperativeInstruction::whereIn('id', $donts['deleted'])->delete();
        }

        if (isset($donts['new'])) {
            $toTranslation = [];
            $procedureDonts = [];

            foreach ($donts['new'] as $index => $dont) {
                if (!is_array($dont)) {
                    continue;
                }

                $procedureDont = ProcedurePostoperativeInstruction::create([
                    'procedure_id' => $procedure->id,
                    'type' => 'dont',
                    'order' => $dont['order'],
                ]);

                $procedureDonts[$index] = [
                    'model' => $procedureDont,
                    'data' => $dont,
                ];

                $toTranslation[] = $dont['content'] ?? '';
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedureDonts as $dontData) {
                        $dontData['model']->translations()->updateOrCreate(
                            [
                                'procedure_postoperative_instruction_id' => $dontData['model']->id,
                                'lang' => $targetLang
                            ],
                            [
                                'content' => $dontData['data']['content'] ?? '',
                            ]
                        );
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($toTranslation, $targetLang, $sourceLang);

                $translationIndex = 0;
                foreach ($procedureDonts as $dontData) {
                    $dontData['model']->translations()->updateOrCreate(
                        [
                            'procedure_postoperative_instruction_id' => $dontData['model']->id,
                            'lang' => $targetLang
                        ],
                        [
                            'content' => $translatedTexts[$translationIndex] ?? '',
                        ]
                    );

                    $translationIndex++;
                }
            }
        }

        if (isset($donts['updated'])) {
            $toTranslation = [];
            $procedureDonts = [];

            foreach ($donts['updated'] as $index => $dont) {
                if (!is_array($dont) || !isset($dont['id'])) {
                    continue;
                }

                $procedureDont = ProcedurePostoperativeInstruction::findOrFail($dont['id']);

                // Actualizar campos si vienen en $step
                if (isset($dont['order'])) {
                    $procedureDont->order = $dont['order'];
                }

                $procedureDont->save();

                $procedureDonts[$index] = [
                    'model' => $procedureDont,
                    'data' => $dont,
                ];

                // Solo agregar a traducción si los campos existen y son diferentes
                $translation = $procedureDont->translations()
                    ->where('lang', $sourceLang)
                    ->first();

                if (isset($dont['content']) && (!$translation || $dont['content'] !== $translation->content)) {
                    $toTranslation[] = $dont['content'];
                } else {
                    $toTranslation[] = null;
                }
            }

            // Filtrar valores null antes de traducir
            $textsToTranslate = array_filter($toTranslation, fn($text) => $text !== null);

            if (empty($textsToTranslate)) {
                return;
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($procedureDonts as $dontData) {
                        $updateData = [];

                        if (isset($dontData['data']['content'])) {
                            $updateData['content'] = $dontData['data']['content'];
                        }

                        if (!empty($updateData)) {
                            $dontData['model']->translations()->updateOrCreate(
                                [
                                    'procedure_postoperative_instruction_id' => $dontData['model']->id,
                                    'lang' => $targetLang
                                ],
                                $updateData
                            );
                        }
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                $translationIndex = 0;
                $fullTranslations = [];

                foreach ($toTranslation as $originalText) {
                    if ($originalText === null) {
                        $fullTranslations[] = null;
                    } else {
                        $fullTranslations[] = $translatedTexts[$translationIndex] ?? null;
                        $translationIndex++;
                    }
                }

                $arrayIndex = 0;
                foreach ($procedureDonts as $dontData) {
                    $updateData = [];

                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['content'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if (!empty($updateData)) {
                        $dontData['model']->translations()->updateOrCreate(
                            [
                                'procedure_postoperative_instruction_id' => $dontData['model']->id,
                                'lang' => $targetLang
                            ],
                            $updateData
                        );
                    }
                }
            }
        }
    }

    private function updateGallery(Procedure $procedure, array $gallery)
    {
        // Eliminar imágenes
        if (isset($gallery['deleted'])) {
            $galleriesToDelete = ProcedureResultGallery::whereIn('id', $gallery['deleted'])->get();
            Log::info('asd', [$galleriesToDelete]);
            foreach ($galleriesToDelete as $galleryItem) {
                if (!empty($galleryItem->path) && Storage::disk('public')->exists($galleryItem->path)) {
                    Storage::disk('public')->delete($galleryItem->path);
                }
            }

            // Eliminar registros de la BD
            ProcedureResultGallery::whereIn('id', $gallery['deleted'])->delete();
        }

        // Crear nuevas imágenes
        if (isset($gallery['new'])) {
            foreach ($gallery['new'] as $index => $newImage) {
                if (!is_array($newImage) || !isset($newImage['path'])) {
                    continue;
                }

                // Validar que sea un archivo subido
                if (!($newImage['path'] instanceof UploadedFile)) {
                    continue;
                }

                // Guardar imagen
                $imagePath = Helpers::saveWebpFile(
                    $newImage['path'],
                    "images/procedure/{$procedure->id}/gallery"
                );

                // Crear registro en BD
                ProcedureResultGallery::create([
                    'procedure_id' => $procedure->id,
                    'path' => $imagePath,
                ]);
            }
        }

        // Actualizar imágenes existentes
        if (isset($gallery['updated'])) {
            foreach ($gallery['updated'] as $index => $updatedImage) {
                if (!is_array($updatedImage) || !isset($updatedImage['id'])) {
                    continue;
                }

                $galleryItem = ProcedureResultGallery::findOrFail($updatedImage['id']);

                // Si viene una nueva imagen, reemplazar
                if (isset($updatedImage['path']) && $updatedImage['path'] instanceof UploadedFile) {
                    // Eliminar imagen anterior
                    if (!empty($galleryItem->path) && Storage::disk('public')->exists($galleryItem->path)) {
                        Storage::disk('public')->delete($galleryItem->path);
                    }

                    // Guardar nueva imagen
                    $imagePath = Helpers::saveWebpFile(
                        $updatedImage['path'],
                        "images/procedure/{$procedure->id}/gallery"
                    );

                    $galleryItem->path = $imagePath;
                    $galleryItem->save();
                }
            }
        }
    }


    public function delete_procedure($id)
    {
        DB::beginTransaction();
        try {
            $procedure = Procedure::findOrFail($id);
            $procedure->delete();

            DB::commit();

            return ApiResponse::success(
                __('messages.procedure.success.deleteProcedure')
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.procedure.error.deleteProcedure'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
