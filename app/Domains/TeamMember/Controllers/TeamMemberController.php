<?php

namespace App\Domains\TeamMember\Controllers;

use App\Domains\TeamMember\Models\TeamMember;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class TeamMemberController extends Controller
{

    public function list_teamMember(Request $request)
    {
        try {
            $perPage = 8;

            $query = TeamMember::with([
                'translation',
                'images'
            ])->orderBy('created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            $paginate->getCollection()->transform(fn($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'status' => $team->status,
                'created_at' => $team->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $team->updated_at->format('Y-m-d H:i:s'),
            ]);

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

    public function get_teamMember($id)
    {
        try {
            $team = TeamMember::with([
                'translation',
                'images',
            ])->findOrFail($id);

            $translation = $team->translations->first();

            $data = [
                'id' => $team->id,
                'status' => $team->status,
                'name' => $team->name ?? '',
                'image' => $team->image,
                'biography' => $translation?->biography ?? '',
                'specialization' => $translation?->specialization ?? '',
                'results' => $team->images->map(fn($img) => [
                    'id' => $img->id,
                    'team_member_id' => $img->team_member_id,
                    'lang' => $img->lang,
                    'url' => $img->url,
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
            Log::error('Error en get_teamMember: ' . $e->getMessage());
            return ApiResponse::error(
                __('messages.teamMember.error.getTeamMember'),
                [['exception' => $e->getMessage()]],
                500
            );
        }
    }
}
