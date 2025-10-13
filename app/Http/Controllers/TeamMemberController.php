<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\TeamMember\StoreRequest;
use App\Http\Requests\Dashboard\TeamMember\UpdateRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\TeamMemberTranslation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            $perPage = 12;

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
            $lastBlogs = TeamMember::where('created_at', '>=', now()->subDays(15))->count();

            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.list_teamMember'),
                'data' => [
                    'pagination' => $paginate,
                    'filters' => $request->only('search'),
                    'stats' => [
                        'total' => $total,
                        'totalActivated' => $totalActivated,
                        'totalDeactivated' => $totalDeactivated,
                        'lastBlogs' => $lastBlogs,
                    ],
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en list_teamMember: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.list_teamMember'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function get_teamMember($id, Request $request)
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

            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.getTeamMember'),
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en get_service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.getTeamMember'),
                'error' => $e->getMessage(),
            ], 500);
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

            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.create_teamMember'),
                'data' => $team
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_teamMember: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.create_teamMember'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update_teamMember(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $team = TeamMember::findOrFail($id);
            $data = $request->validated();

            $updates = [];

            if (isset($data['name']) && $data['name'] !== $team->name) {
                $updates['name'] = $data['name'];
            }

            if (isset($data['status']) && $data['status'] !== $team->status) {
                $updates['status'] = $data['status'];
            }

            // 📸 Si se sube una nueva imagen principal
            if (!empty($data['image'])) {
                if (!empty($team->image) && Storage::disk('public')->exists($team->image)) {
                    Storage::disk('public')->delete($team->image);
                }

                $path = Helpers::saveWebpFile($data['image'], "images/team/{$team->id}/image_main");
                if ($path !== $team->image) {
                    $updates['image'] = $path;
                }
            }

            // 🔁 Aplicar los cambios si hay algo que actualizar
            if (!empty($updates)) {
                $team->update($updates);
            }

            // 🗣️ Actualizar o crear traducción en español si cambió algo
            $translationEs = TeamMemberTranslation::where('team_member_id', $team->id)
                ->where('lang', 'es')
                ->first();

            if (
                !$translationEs ||
                $translationEs->specialization !== ($data['specialization'] ?? '') ||
                $translationEs->biography !== ($data['biography'] ?? '')
            ) {
                TeamMemberTranslation::updateOrCreate(
                    ['team_member_id' => $team->id, 'lang' => 'es'],
                    [
                        'specialization' => $data['specialization'] ?? '',
                        'biography' => $data['biography'] ?? '',
                    ]
                );

                // Actualizar versión en inglés solo si hay cambios
                TeamMemberTranslation::updateOrCreate(
                    ['team_member_id' => $team->id, 'lang' => 'en'],
                    [
                        'specialization' => Helpers::translateBatch([$data["specialization"] ?? ''], 'es', 'en')[0] ?? null,
                        'biography' => Helpers::translateBatch([$data["biography"] ?? ''], 'es', 'en')[0] ?? null,
                    ]
                );
            }

            if (!empty($data['results'])) {
                // IDs recibidos desde el frontend
                $receivedIds = collect($data['results'])->pluck('id')->filter()->toArray();

                // Eliminar imágenes no presentes en el payload
                $imagesToDelete = TeamMemberImage::where('team_member_id', $team->id)
                    ->whereNotIn('id', $receivedIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    if ($image->url && Storage::disk('public')->exists($image->url)) {
                        Storage::disk('public')->delete($image->url);
                    }
                    $image->delete();
                }

                // Obtener imágenes existentes en español
                $existingImages = TeamMemberImage::where('team_member_id', $team->id)
                    ->where('lang', 'es')
                    ->get()
                    ->keyBy('id');

                foreach ($data['results'] as $result) {
                    // Actualizar o crear imagen en español
                    if (!empty($result['id']) && isset($existingImages[$result['id']])) {
                        $imageEs = $existingImages[$result['id']];

                        // Si cambió la URL, eliminar imagen anterior y guardar nueva
                        if (!empty($result['url']) && $result['url'] !== $imageEs->url) {
                            if ($imageEs->url && Storage::disk('public')->exists($imageEs->url)) {
                                Storage::disk('public')->delete($imageEs->url);
                            }
                            $imageEs->url = Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results");
                        }

                        $imageEs->description = $result['description'] ?? '';
                        $imageEs->save();
                    } else {
                        // Crear nueva imagen
                        $imageEs = TeamMemberImage::create([
                            'team_member_id' => $team->id,
                            'url' => Helpers::saveWebpFile($result['url'], "images/team/{$team->id}/results"),
                            'description' => $result['description'] ?? '',
                            'lang' => 'es',
                        ]);
                    }

                    // Sincronizar versión en inglés
                    $translatedDesc = Helpers::translateBatch([$result['description'] ?? ''], 'es', 'en')[0] ?? '';

                    TeamMemberImage::updateOrCreate(
                        [
                            'team_member_id' => $team->id,
                            'lang' => 'en',
                            'url' => $imageEs->url,
                        ],
                        ['description' => $translatedDesc]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.update_teamMember'),
                'data' => $team->fresh(['teamMemberTranslations', 'teamMemberImages'])
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_teamMember: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.update_teamMember'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_teamMember($id)
    {
        DB::beginTransaction();
        try {
            $team = TeamMember::findOrFail($id);
            $team->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.delete_teamMember')
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_teamMember: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.delete_teamMember'),
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.updateStatus'),
                'data' => $team
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.updateStatus'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
