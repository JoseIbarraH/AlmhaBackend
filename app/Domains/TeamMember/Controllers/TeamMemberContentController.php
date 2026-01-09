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
use Illuminate\Http\Request;
use App\Helpers\Helpers;

class TeamMemberContentController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function create_teamMember(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name' => 'required|string'
            ]);

            $team = TeamMember::create([
                'user_id' => auth()->id(),
                'slug' => uniqid('temp-'),
                'name' => $data['name'],
                'image' => '',
                'status' => 'inactive'
            ]);

            $team->slug = null;
            $team->save();

            DB::commit();

            return ApiResponse::success(
                message: 'team member successfully created',
                data: [
                    'id' => $team->id
                ]
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return ApiResponse::error(
                message: 'Error creating team member',
                errors: $th,
                code: 500
            );
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

        if (isset($data['image'])) {
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

        $sourceTranslation = $team->translations()->updateOrCreate([
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

            $translation = $team->translations()->updateOrCreate([
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

    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $blog = TeamMember::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $blog->update(['status' => $data['status']]);

            DB::commit();
            return ApiResponse::success(
                __('messages.blog.success.updateStatus'),
                $blog
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.blog.error.updateStatus'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function delete_teamMember($id)
    {
        DB::beginTransaction();
        try {
            $team = TeamMember::findOrFail($id);
            $team->delete();

            DB::commit();

            return ApiResponse::success(
                "Member successfully removed"
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                "Error deleting member",
                $e,
                500
            );
        }
    }
}
