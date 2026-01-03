<?php

namespace App\Domains\TeamMember\Controllers;

use App\Domains\TeamMember\Models\TeamMemberTranslation;
use App\Domains\TeamMember\Models\TeamMemberImage;
use App\Domains\TeamMember\Requests\UpdateRequest;
use App\Domains\TeamMember\Requests\StoreRequest;
use App\Domains\TeamMember\Models\TeamMember;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
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

    public function create_teamMember(StoreRequest $request, GoogleTranslateService $translator)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $team = TeamMember::create([
                'name' => $data['name'],
                'status' => $data['status'],
                'user_id' => auth()->id(),
                'image' => ''
            ]);

            $team->image = Helpers::saveWebpFile($data['image'], "images/team/{$team->id}/image_main");
            $team->save();

            $this->createTranslations($team, $data, $translator);

            if (!empty($data['results'])) {
                $this->createResults($team, $data['results'], $translator);
            }

            DB::commit();
            return ApiResponse::success(
                __('messages.teamMember.success.create_teamMember'),
                $team->load(['translations', 'images'])
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_teamMember', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.teamMember.error.create_teamMember'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    /**
     * Crear traducciones para todos los idiomas
     */
    private function createTranslations(TeamMember $team, array $data, GoogleTranslateService $translator): void
    {
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                // Crear traducción en español
                TeamMemberTranslation::create([
                    'team_member_id' => $team->id,
                    'specialization' => $data['specialization'],
                    'biography' => $data['biography'],
                    'lang' => $lang
                ]);
                continue;
            }

            try {
                // Traducir ambos campos en UNA sola llamada API
                $translated = $translator->translate([
                    $data['specialization'],
                    $data['biography']
                ], $lang);

                TeamMemberTranslation::create([
                    'team_member_id' => $team->id,
                    'specialization' => $translated[0] ?? $data['specialization'],
                    'biography' => $translated[1] ?? $data['biography'],
                    'lang' => $lang
                ]);

            } catch (\Exception $e) {
                \Log::error("Translation error for team member {$team->id} to {$lang}: " . $e->getMessage());

                // Fallback: crear con texto original
                TeamMemberTranslation::create([
                    'team_member_id' => $team->id,
                    'specialization' => $data['specialization'],
                    'biography' => $data['biography'],
                    'lang' => $lang
                ]);
            }
        }
    }

    /**
     * Crear imágenes de resultados con traducciones
     */
    private function createResults(TeamMember $team, array $results, GoogleTranslateService $translator): void
    {
        // Guardar rutas de imágenes
        $imagePaths = [];
        $descriptions = [];

        foreach ($results as $result) {
            $path = Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results");
            $imagePaths[] = $path;
            $descriptions[] = $result['description'] ?? '';
        }

        // Traducir por idioma
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                // Español: descripciones originales
                foreach ($imagePaths as $index => $path) {
                    TeamMemberImage::create([
                        'team_member_id' => $team->id,
                        'url' => $path,
                        'description' => $descriptions[$index],
                        'lang' => $lang
                    ]);
                }
                continue;
            }

            try {
                // UNA sola llamada con TODAS las descripciones
                $translated = $translator->translate($descriptions, $lang);

                foreach ($imagePaths as $index => $path) {
                    TeamMemberImage::create([
                        'team_member_id' => $team->id,
                        'url' => $path,
                        'description' => $translated[$index] ?? $descriptions[$index],
                        'lang' => $lang
                    ]);
                }

            } catch (\Exception $e) {
                \Log::error("Translation error for images to {$lang}: " . $e->getMessage());

                // Fallback
                foreach ($imagePaths as $index => $path) {
                    TeamMemberImage::create([
                        'team_member_id' => $team->id,
                        'url' => $path,
                        'description' => $descriptions[$index],
                        'lang' => $lang
                    ]);
                }
            }
        }
    }

    public function update_teamMember(UpdateRequest $request, $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $team = TeamMember::findOrFail($id);

            // Actualizar datos básicos del miembro
            if (isset($data['name']) || isset($data['status']) || isset($data['image'])) {
                $this->updateTeamMember($team, $data);
            }

            // Actualizar traducciones
            if (isset($data['specialization']) || isset($data['biography'])) {
                $this->updateTranslations($team, $data, $translator);
            }

            // Actualizar imágenes de resultados
            if (isset($data['results'])) {
                $this->updateResults($team, $data['results'], $translator);
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.teamMember.success.updateTeamMember'),
                $team->fresh(['translations', 'images'])
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_teamMember', [
                'team_member_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.teamMember.error.updateTeamMember'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    /**
     * Actualizar datos básicos del miembro del equipo
     */
    private function updateTeamMember(TeamMember $team, array $data): void
    {
        $updates = [];

        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
        }

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        // Actualizar imagen principal
        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            // Eliminar imagen anterior
            if (!empty($team->image) && Storage::disk('public')->exists($team->image)) {
                Storage::disk('public')->delete($team->image);
            }

            $updates['image'] = Helpers::saveWebpFile($data['image'], "images/team/{$team->id}/image_main");
        }

        if (!empty($updates)) {
            $team->update($updates);
        }
    }

    /**
     * Actualizar traducciones para todos los idiomas
     */
    private function updateTranslations(TeamMember $team, array $data, GoogleTranslateService $translator): void
    {
        // Obtener traducción en español
        $translationEs = TeamMemberTranslation::firstOrCreate([
            'team_member_id' => $team->id,
            'lang' => 'es'
        ]);

        $specializationChanged = isset($data['specialization']) && $data['specialization'] !== $translationEs->specialization;
        $biographyChanged = isset($data['biography']) && $data['biography'] !== $translationEs->biography;

        // Si no hay cambios, salir
        if (!$specializationChanged && !$biographyChanged) {
            return;
        }

        // Actualizar español
        $translationEs->update([
            'specialization' => $data['specialization'] ?? $translationEs->specialization,
            'biography' => $data['biography'] ?? $translationEs->biography,
        ]);

        // Preparar textos para traducir
        $textsToTranslate = [];
        $changedFields = [];

        if ($specializationChanged) {
            $textsToTranslate[] = $data['specialization'];
            $changedFields[] = 'specialization';
        }

        if ($biographyChanged) {
            $textsToTranslate[] = $data['biography'];
            $changedFields[] = 'biography';
        }

        // Traducir a otros idiomas
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                // UNA sola llamada API con todos los textos cambiados
                $translated = $translator->translate($textsToTranslate, $lang);

                $translation = TeamMemberTranslation::firstOrCreate([
                    'team_member_id' => $team->id,
                    'lang' => $lang
                ]);

                // Actualizar solo los campos que cambiaron
                $updatedFields = [];
                foreach ($changedFields as $index => $field) {
                    $updatedFields[$field] = $translated[$index] ?? $translation->$field;
                }

                $translation->update($updatedFields);
                $team->auditEvent('updated')->setOldValues($translation)->setNewValues($updatedFields)->save();

            } catch (\Exception $e) {
                \Log::error("Translation error for team member {$team->id} to {$lang}: " . $e->getMessage());
            }
        }
    }

    /**
     * Actualizar imágenes de resultados con traducciones optimizadas
     */
    private function updateResults(TeamMember $team, array $results, GoogleTranslateService $translator): void
    {
        // IDs recibidos (para identificar qué mantener)
        $receivedIds = collect($results)->pluck('id')->filter()->toArray();

        // Eliminar imágenes que ya no están en la lista
        $imagesToDelete = TeamMemberImage::where('team_member_id', $team->id)
            ->whereNotIn('id', $receivedIds)
            ->get()
            ->groupBy('url');

        foreach ($imagesToDelete as $url => $imagesGroup) {
            // Eliminar archivo físico solo una vez por URL
            $firstImage = $imagesGroup->first();
            if ($firstImage->url && Storage::disk('public')->exists($firstImage->url)) {
                Storage::disk('public')->delete($firstImage->url);
            }

            // Eliminar todos los registros de esta URL (todos los idiomas)
            foreach ($imagesGroup as $image) {
                $image->delete();
            }
        }

        // Obtener imágenes existentes en español
        $existingImagesEs = TeamMemberImage::where('team_member_id', $team->id)
            ->where('lang', 'es')
            ->get()
            ->keyBy('id');

        // Procesar cada resultado
        $processedImages = [];

        foreach ($results as $result) {
            $imageEs = null;
            $isNewImage = empty($result['id']) || !isset($existingImagesEs[$result['id']]);

            if (!$isNewImage) {
                // Actualizar imagen existente
                $imageEs = $existingImagesEs[$result['id']];
                $oldUrl = $imageEs->url;

                // Manejar actualización de URL
                if (!empty($result['url'])) {
                    if ($result['url'] instanceof UploadedFile) {
                        // Nueva imagen subida
                        if ($oldUrl && Storage::disk('public')->exists($oldUrl)) {
                            Storage::disk('public')->delete($oldUrl);
                        }
                        $newUrl = Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results");
                        $imageEs->url = $newUrl;

                        // Actualizar URL en todas las traducciones existentes
                        TeamMemberImage::where('team_member_id', $team->id)
                            ->where('url', $oldUrl)
                            ->update(['url' => $newUrl]);
                    }

                    if (is_string($result['url'])) {
                        // URL como string (sin cambios en la imagen)
                        $urlDecoded = urldecode(Helpers::removeAppUrl($result['url']));
                        if ($urlDecoded !== $oldUrl) {
                            $imageEs->url = $urlDecoded;
                        }
                    }
                }

                $imageEs->description = $result['description'] ?? '';
                $imageEs->save();
            } else {
                // Crear nueva imagen
                $newUrl = $result['url'] instanceof UploadedFile
                    ? Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results")
                    : urldecode(Helpers::removeAppUrl($result['url']));

                $imageEs = TeamMemberImage::create([
                    'team_member_id' => $team->id,
                    'url' => $newUrl,
                    'description' => $result['description'] ?? '',
                    'lang' => 'es',
                ]);
            }

            $processedImages[] = [
                'url' => $imageEs->url,
                'description' => $imageEs->description,
                'is_new' => $isNewImage
            ];
        }

        // Traducir todas las descripciones en batch por idioma
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                // Recolectar todas las descripciones
                $descriptions = array_column($processedImages, 'description');

                // UNA sola llamada API con TODAS las descripciones
                $translated = $translator->translate($descriptions, $lang);

                // Actualizar o crear imágenes traducidas
                foreach ($processedImages as $index => $imageData) {
                    $existingTranslation = TeamMemberImage::where('team_member_id', $team->id)
                        ->where('lang', $lang)
                        ->where('url', $imageData['url'])
                        ->first();

                    $translatedDescription = $translated[$index] ?? $imageData['description'];

                    if ($existingTranslation) {
                        $existingTranslation->update([
                            'description' => $translatedDescription
                        ]);
                    } else {
                        TeamMemberImage::create([
                            'team_member_id' => $team->id,
                            'url' => $imageData['url'],
                            'description' => $translatedDescription,
                            'lang' => $lang,
                        ]);
                    }
                }

            } catch (\Exception $e) {
                \Log::error("Translation error for team member images {$team->id} to {$lang}: " . $e->getMessage());

                // Fallback: crear con descripciones originales
                foreach ($processedImages as $imageData) {
                    TeamMemberImage::firstOrCreate([
                        'team_member_id' => $team->id,
                        'url' => $imageData['url'],
                        'lang' => $lang,
                    ], [
                        'description' => $imageData['description']
                    ]);
                }
            }
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
                __('messages.teamMember.success.delete_teamMember')
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_teamMember: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.teamMember.error.delete_teamMember'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $team = TeamMember::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $team->update(['status' => $data['status']]);

            DB::commit();
            return ApiResponse::success(
                __('messages.teamMember.success.updateStatus'),
                $data
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.teamMember.error.updateStatus'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
