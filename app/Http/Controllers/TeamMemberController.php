<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\TeamMember\StoreRequest;
use App\Http\Requests\Dashboard\TeamMember\UpdateRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\TeamMemberTranslation;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\TeamMemberImage;
use Illuminate\Http\Request;
use App\Models\TeamMember;
use App\Helpers\Helpers;

class TeamMemberController extends Controller
{
    public function list_teamMember(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 8;

            // Query principal con relaciones
            $query = TeamMember::with([
                'teamMemberTranslations' => fn($q) => $q->where('lang', $locale),
                'teamMemberImages' => fn($q) => $q->where('lang', $locale)
            ])->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            // Transformamos la colección para agregar URLs completas y resultados
            $paginate->getCollection()->transform(function ($team) {
                $translation = $team->teamMemberTranslations->first();
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'status' => $team->status,
                    'image' => $team->image ? url("storage/{$team->image}") : null,
                    'biography' => $translation?->biography ?? '',
                    'specialization' => $translation?->specialization ?? '',
                    'results' => $team->teamMemberImages->map(fn($img) => [
                        'url' => url("storage/{$img->url}"),
                        'description' => $img->description
                    ]),
                    'created_at' => $team->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $team->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            // Stats
            $total = TeamMember::count();
            $totalActivated = TeamMember::where('status', 'active')->count();
            $totalDeactivated = TeamMember::where('status', 'inactive')->count();
            $last = TeamMember::where('created_at', '>=', now()->subDays(15))->count();

            return ApiResponse::success(
                __('messages.teamMember.success.list_teamMember'),
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
            Log::error('Error en list_teamMember: ' . $e->getMessage());
            return ApiResponse::error(
                __('messages.teamMember.error.list_teamMember'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function get_teamMember($id, Request $request)
    {
        try {
            $locale = 'es';

            $team = TeamMember::with([
                'teamMemberTranslations' => fn($q) => $q->where('lang', $locale),
                'teamMemberImages' => fn($q) => $q->where('lang', $locale),
            ])->findOrFail($id);

            // Obtener traducción del idioma solicitado
            $translation = $team->teamMemberTranslations?->first();

            $data = [
                'id' => $team->id,
                'status' => $team->status,
                'name' => $team->name ?? '',
                'image' => url('storage', $team->image),
                'biography' => $translation->biography ?? '',
                'specialization' => $translation->specialization ?? '',
                'results' => $team->teamMemberImages->map(fn($img) => [
                    'id' => $img->id,
                    'team_member_id' => $img->team_member_id,
                    'lang' => $img->lang,
                    'url' => url('storage', $img->url), // 👈 agrega el dominio
                    'description' => $img->description,
                    'created_at' => $img->created_at,
                    'updated_at' => $img->updated_at,
                ]),
                'created_at' => $team->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $team->updated_at->format('Y-m-d H:i:s'),
            ];
            return ApiResponse::success(
                __('messages.teamMember.success.getTeamMember'),
                $data
            );

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage());
            return ApiResponse::error(
                __('messages.teamMember.error.getTeamMember'),
                [['exception' => $e->getMessage()]],
                500
            );
        }
    }

    public function get_teamMember_client($id, Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale()); // o 'es' por default

            $team = TeamMember::with([
                'teamMemberTranslations' => fn($q) => $q->where('lang', $locale),
                'teamMemberImages' => fn($q) => $q->where('lang', $locale),
            ])->findOrFail($id);

            // Obtener traducción del idioma solicitado
            $translation = $team->teamMemberTranslations?->first();

            $data = [
                'id' => $team->id,
                'status' => $team->status,
                'name' => $team->name ?? '',
                'image' => url('storage', $team->image),
                'biography' => $translation->biography ?? '',
                'specialization' => $translation->specialization ?? '',
                'results' => $team->teamMemberImages->map(fn($img) => [
                    'id' => $img->id,
                    'team_member_id' => $img->team_member_id,
                    'lang' => $img->lang,
                    'url' => url('storage', $img->url), // 👈 agrega el dominio
                    'description' => $img->description,
                    'created_at' => $img->created_at,
                    'updated_at' => $img->updated_at,
                ]),
                'created_at' => $team->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $team->updated_at->format('Y-m-d H:i:s'),
            ];

            return ApiResponse::success(
                __('messages.teamMember.success.getTeamMember'),
                $data
            );

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage());
            return ApiResponse::error(
                __('messages.teamMember.error.getTeamMember'),
                [['exception' => $e->getMessage()]],
                500
            );
        }
    }

    public function create_teamMember(StoreRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Crear miembro del equipo
            $team = TeamMember::create([
                'name' => $data['name'],
                'status' => $data['status'],
                'user_id' => auth()->id(),
                'image' => ''
            ]);

            // Guardar imagen principal
            $team->image = Helpers::saveWebpFile($data['image'], "images/team/{$team->id}/image_main");
            $team->save();

            // Traducción en español
            TeamMemberTranslation::create([
                'team_member_id' => $team->id,
                'specialization' => $data['specialization'],
                'biography' => $data['biography'],
                'lang' => 'es'
            ]);

            // Traducción en inglés
            TeamMemberTranslation::create([
                'team_member_id' => $team->id,
                'specialization' => Helpers::translateBatch([$data["specialization"] ?? ''], 'es', 'en')[0] ?? null,
                'biography' => Helpers::translateBatch([$data["biography"] ?? ''], 'es', 'en')[0] ?? null,
                'lang' => 'en'
            ]);

            // Procesar resultados si existen
            if (!empty($data['results'])) {
                foreach ($data['results'] as $result) {
                    $path = Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results");

                    // Guardar versión en español
                    TeamMemberImage::create([
                        'team_member_id' => $team->id,
                        'url' => $path,
                        'description' => $result['description'] ?? '',
                        'lang' => 'es'
                    ]);

                    // Guardar versión traducida en inglés
                    TeamMemberImage::create([
                        'team_member_id' => $team->id,
                        'url' => $path,
                        'description' => Helpers::translateBatch([$result['description'] ?? ''], 'es', 'en')[0] ?? '',
                        'lang' => 'en'
                    ]);
                }
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.teamMember.success.create_teamMember'),
                $team
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_teamMember: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.teamMember.error.create_teamMember'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    public function update_teamMember(UpdateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $team = TeamMember::findOrFail($id);

            Log::info("DATA: ", [$data]);

            if (isset($data['name']) || isset($data['status']) || isset($data['image'])) {
                $this->team_member(data: $data, team: $team);
            }

            if (isset($data['specialization']) || isset($data['biography'])) {
                $team->load(['teamMemberTranslations']);
                $this->team_translations(data: $data, team: $team);
            }

            if (isset($data['results'])) {
                $team->load(['teamMemberImages']);
                $this->team_image(data: $data, team: $team);
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.teamMember.success.update_teamMember'),
                $team->fresh(['teamMemberTranslations', 'teamMemberImages'])
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_teamMember: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.teamMember.error.update_teamMember'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    private function team_member($data, $team)
    {
        $updates = [];

        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
        }

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (!empty($data['image'])) {
            if ($data['image'] instanceof UploadedFile) {
                if (!empty($team->image) && Storage::disk('public')->exists($team->image)) {
                    Storage::disk('public')->delete($team->image);
                }

                $path = Helpers::saveWebpFile($data['image'], "images/team/{$team->id}/image_main");
                $updates['image'] = $path;
            }
        }

        if (!empty($updates)) {
            $team->update($updates);
        }

        return $team;
    }

    private function team_translations($data, $team)
    {
        TeamMemberTranslation::updateOrCreate(
            [
                'team_member_id' => $team->id,
                'lang' => 'es'
            ],
            [
                'specialization' => $data['specialization'] ?? '',
                'biography' => $data['biography'] ?? '',
            ]
        );

        $translations = Helpers::translateBatch([$data['specialization'], $data['biography']]) ?? '';

        TeamMemberTranslation::updateOrCreate(
            [
                'team_member_id' => $team->id,
                'lang' => 'en'
            ],
            [
                'specialization' => $translations[0] ?? '',
                'biography' => $translations[1] ?? '',
            ]
        );
    }

    private function team_image($data, $team)
    {
        $receivedIds = collect($data['results'])->pluck('id')->filter()->toArray();

        $imagesToDelete = TeamMemberImage::where('team_member_id', $team->id)
            ->whereNotIn('id', $receivedIds)
            ->get();

        foreach ($imagesToDelete as $image) {
            if ($image->lang === 'es' && $image->url && Storage::disk('public')->exists($image->url)) {
                Storage::disk('public')->delete($image->url);
            }
            $image->delete();
        }

        $existingImages = TeamMemberImage::where('team_member_id', $team->id)
            ->where('lang', 'es')
            ->get()
            ->keyBy('id');

        foreach ($data['results'] as $result) {
            $imageEs = null;

            if (!empty($result['id']) && isset($existingImages[$result['id']])) {
                $imageEs = $existingImages[$result['id']];
                $oldUrl = $imageEs->url;

                if (!empty($result['url']) && $result['url'] instanceof UploadedFile) {
                    if ($oldUrl && Storage::disk('public')->exists($oldUrl)) {
                        Storage::disk('public')->delete($oldUrl);
                    }
                    $imageEs->url = Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results");
                } elseif (!empty($result['url']) && is_string($result['url'])) {
                    $urlDecoded = urldecode(Helpers::removeAppUrl($result['url']));
                    if ($urlDecoded !== $oldUrl) {
                        $imageEs->url = $urlDecoded;
                    }
                }

                $imageEs->description = $result['description'] ?? '';
                $imageEs->save();
            } else {
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

            if ($imageEs) {
                $translatedDesc = Helpers::translateBatch([$imageEs->description], 'es', 'en')[0] ?? '';

                $imageEn = TeamMemberImage::where('team_member_id', $team->id)
                    ->where('lang', 'en')
                    ->where('url', $imageEs->url)
                    ->first();

                if ($imageEn) {
                    $imageEn->description = $translatedDesc;
                    $imageEn->save();
                } else {
                    TeamMemberImage::create([
                        'team_member_id' => $team->id,
                        'url' => $imageEs->url,
                        'description' => $translatedDesc,
                        'lang' => 'en',
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
