<?php

namespace App\Domains\TeamMember\Controllers;

use App\Domains\TeamMember\Models\TeamMemberImage;
use App\Domains\TeamMember\Models\TeamMemberTranslation;
use App\Domains\TeamMember\Requests\UpdateRequest;
use App\Domains\TeamMember\Requests\StoreRequest;
use App\Domains\TeamMember\Models\TeamMember;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use App\Helpers\Helpers;

class TeamMemberContentController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function create_teamMember(StoreRequest $request, GoogleTranslateService $translator)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $team = TeamMember::create([
                'user_id' => auth()->id(),
                'slug' => uniqid('temp-'),
                'name' => $data['name'] ?? '',
                'image' => '',
                'status' => $data['status'] ?? 'inactive'
            ]);

            if ($request->hasFile('image')) {
                $path = Helpers::saveWebpFile($request->file('image'), "images/team/{$team->id}/main_image");
                $team->update(['image' => $path]);
            }

            $this->createTranslations($team, $data, $translator);

            $this->createImages($team, $data['result'], $translator);

            $team->slug = null;
            $team->save();

            DB::commit();
            return ApiResponse::success(
                "Member of the successfully created team"
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                message: "Error creating successful team member",
                errors: $e,
                code: 500
            );
        }
    }

    private function createTranslations(TeamMember $team, array $data, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();

        $fieldsToTranslate = array_filter([
            'specialization' => $data['specialization'] ?? null,
            'biography' => $data['biography'] ?? null
        ]);

        if (empty($fieldsToTranslate)) {
            return;
        }

        $translationsData = [];

        foreach ($this->languages as $targetLang) {
            $translationRecord = [
                'team_member_id' => $team->id,
                'lang' => $targetLang
            ];

            if ($targetLang === $sourceLang) {
                $translationRecord = array_merge($translationRecord, $fieldsToTranslate);
            } else {
                $textsToTranslate = array_values($fieldsToTranslate);
                $translatedTexts = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                $fieldKeys = array_keys($fieldsToTranslate);
                foreach ($fieldKeys as $index => $field) {
                    $translationRecord[$field] = $translatedTexts[$index] ?? $fieldsToTranslate[$field];
                }
            }

            $translationsData[] = $translationRecord;
        }

        TeamMemberTranslation::insert($translationsData);
    }

    private function createImages(TeamMember $team, array $results, GoogleTranslateService $translator): void
    {
        if (empty($results)) {
            return;
        }

        $sourceLang = app()->getLocale();

        // 1. Guardar imágenes y preparar descripciones
        $savedImages = [];
        foreach ($results as $result) {
            if (!isset($result['path']) || !$result['path'] instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $savedImages[] = [
                'path' => Helpers::saveWebpFile($result['path'], "images/team/{$team->id}/results"),
                'description' => $result['description'] ?? ''
            ];
        }

        if (empty($savedImages)) {
            return;
        }

        // 2. Preparar todas las traducciones por idioma
        $descriptions = array_column($savedImages, 'description');
        $nonEmptyDescriptions = array_filter($descriptions);

        $translationsByLang = [];

        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang || empty($nonEmptyDescriptions)) {
                $translationsByLang[$targetLang] = $descriptions;
            } else {
                $translated = $translator->translate(
                    array_values($nonEmptyDescriptions),
                    $targetLang,
                    $sourceLang
                );

                $translatedDescriptions = [];
                $translatedIndex = 0;
                foreach ($descriptions as $desc) {
                    $translatedDescriptions[] = !empty($desc)
                        ? ($translated[$translatedIndex++] ?? $desc)
                        : '';
                }
                $translationsByLang[$targetLang] = $translatedDescriptions;
            }
        }

        // 3. Crear imágenes con sus traducciones
        foreach ($savedImages as $index => $image) {
            $teamImage = $team->images()->create([
                'path' => $image['path']
            ]);

            $translationsData = [];
            foreach ($this->languages as $targetLang) {
                $translationsData[] = [
                    'team_member_image_id' => $teamImage->id,
                    'lang' => $targetLang,
                    'description' => $translationsByLang[$targetLang][$index] ?? ''
                ];
            }

            // Insertar todas las traducciones de esta imagen de una vez
            DB::table('team_member_image_translations')->insert($translationsData);
        }
    }

    public function update_teamMember(UpdateRequest $request, $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $team = TeamMember::findOrFail($id);

            $this->updateData($team, $data);
            $this->updateTranslations($team, $data, $translator);

            if (isset($data['result'])) {
                $this->updateResults($team, $data['result'], $translator);
            }

            DB::commit();
            return ApiResponse::success(
                __('messages.teamMember.success.updateTeamMember')
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.teamMember.error.updateTeamMember'),
                $e,
                500
            );
        }
    }

    private function updateData(TeamMember $team, array $data)
    {
        $updates = [];
        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
        }

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $path = Helpers::removeAppUrl($team->image);
            if (!empty($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $updates['image'] = Helpers::saveWebpFile($data['image'], "images/team/{$team->id}/main_image");
        }

        if (!empty($updates)) {
            $team->update($updates);
        }
    }

    private function updateTranslations(TeamMember $team, array $data, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();
        $fields = ['specialization', 'biography'];

        $sourceTranslation = $team->translations()->firstOrNew([
            'team_member_id' => $team->id,
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

            $translation = $team->translations()->firstOrNew([
                'team_member_id' => $team->id,
                'lang' => $targetLang,
            ]);

            // Solo actualizar los campos que cambiaron
            foreach ($changedFields as $index => $field) {
                $translation->{$field} = $translatedTexts[$index] ?? $translation->{$field};
            }

            $translation->save();
        }
    }

    private function updateResults(TeamMember $team, array $results, GoogleTranslateService $translator)
    {
        $sourceLang = app()->getLocale();

        if (isset($results['deleted'])) {
            $resultsToDelete = TeamMemberImage::whereIn('id', $results['deleted'])->get();

            foreach ($resultsToDelete as $resultItem) {
                $path = Helpers::removeAppUrl($resultItem->path);
                if (!empty($path) && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            TeamMemberImage::whereIn('id', $results['deleted'])->delete();
        }

        if (isset($results['new'])) {
            $toTranslation = [];
            $teamMemberResults = [];

            foreach ($results['new'] as $index => $result) {
                if (!is_array($result)) {
                    continue;
                }

                $path = Helpers::saveWebpFile($result['path'], "images/team/{$team->id}/results");

                $teamMemberResult = $team->images()->create([
                    'path' => $path,
                    'order' => $result['order']
                ]);

                $teamMemberResults[$index] = [
                    'model' => $teamMemberResult,
                    'data' => $result,
                ];

                $toTranslation[] = $result['description'] ?? '';
            }

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    foreach ($teamMemberResults as $resultData) {
                        $resultData['model']->translations()->updateOrCreate(
                            [
                                'team_member_image_id' => $resultData['model']->id,
                                'lang' => $targetLang
                            ],
                            [
                                'description' => $resultData['data']['description'] ?? '',
                            ]
                        );
                    }
                    continue;
                }

                $translatedTexts = $translator->translate($toTranslation, $targetLang, $sourceLang);

                $translationIndex = 0;
                foreach ($teamMemberResults as $resultData) {
                    $resultData['model']->translations()->updateOrCreate(
                        [
                            'team_member_image_id' => $resultData['model']->id,
                            'lang' => $targetLang
                        ],
                        [
                            'description' => $translatedTexts[$translationIndex] ?? '',
                        ]
                    );

                    $translationIndex++;
                }
            }
        }

        if (isset($results['updated'])) {
            $toTranslation = [];
            $teamMemberResults = [];

            foreach ($results['updated'] as $index => $result) {
                if (!is_array($result) || !isset($result['id'])) {
                    continue;
                }

                $teamMemberResult = TeamMemberImage::findOrFail($result['id']);

                if (isset($result['order'])) {
                    $teamMemberResult->order = $result['order'];
                }

                $teamMemberResult->save();

                $teamMemberResults[$index] = [
                    'model' => $teamMemberResult,
                    'data' => $result,
                ];

                // Solo agregar a traducción si los campos existen y son diferentes
                $translation = $teamMemberResult->translations()
                    ->where('lang', $sourceLang)
                    ->first();

                if (isset($result['description']) && (!$translation || $result['description'] !== $translation->description)) {
                    $toTranslation[] = $result['description'];
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
                    foreach ($teamMemberResults as $resultData) {
                        $updateData = [];

                        if (isset($resultData['data']['description'])) {
                            $updateData['description'] = $resultData['data']['description'];
                        }

                        if (!empty($updateData)) {
                            $resultData['model']->translations()->updateOrCreate(
                                [
                                    'team_member_image_id' => $resultData['model']->id,
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
                foreach ($teamMemberResults as $resultData) {
                    $updateData = [];

                    if ($fullTranslations[$arrayIndex] !== null) {
                        $updateData['description'] = $fullTranslations[$arrayIndex];
                    }
                    $arrayIndex++;

                    if (!empty($updateData)) {
                        $resultData['model']->translations()->updateOrCreate(
                            [
                                'team_member_image_id' => $resultData['model']->id,
                                'lang' => $targetLang
                            ],
                            $updateData
                        );
                    }
                }
            }
        }
    }
}
