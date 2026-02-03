<?php

namespace App\Domains\Client\Controllers;

use App\Domains\TeamMember\Models\TeamMember;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;

class TeamMemberClientController extends Controller
{

    public function list_members()
    {
        try {
            // Usamos tags(['members']) para que luego sea fácil invalidar este caché específico
            $members = Cache::tags(['members'])->remember('team_members_active', 86400, function () {
                return QueryBuilder::for(TeamMember::class)
                    ->where('status', 'active')
                    ->with(['translation'])
                    ->select('id', 'slug', 'name', 'status', 'image')
                    ->get()
                    ->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'slug' => $member->slug,
                            'name' => $member->name,
                            'status' => $member->status,
                            'image' => $member->image,
                            'description' => optional($member->translation)->description,
                            'specialization' => optional($member->translation)->specialization,
                        ];
                    });
            });

            return ApiResponse::success("list of members obtained successfully", $members);
        } catch (\Throwable $th) {
            return ApiResponse::error("Error retrieving member list", $th->getMessage(), 500);
        }
    }

    public function get_member($id)
    {
        try {
            $data = Cache::tags(['members'])->remember("member_detail_{$id}", 86400, function () use ($id) {
                $member = TeamMember::with(['translation', 'images'])
                ->orWhere('slug', $id)
                ->firstOrFail();

                return [
                    'id' => $member->id,
                    'slug' => $member->slug,
                    'name' => $member->name,
                    'status' => $member->status,
                    'image' => $member->image,
                    'biography' => $member->translation->biography,
                    'description' => $member->translation->description,
                    'specialization' => $member->translation->specialization,
                    'results' => $member->images->map(function ($img) {
                        return [
                            'id' => $img->id,
                            'path' => $img->path,
                            'order' => $img->order,
                            'description' => $img->translation->description
                        ];
                    })
                ];
            });

            return ApiResponse::success(
                "Member obtained successfully",
                $data
            );

        } catch (\Throwable $th) {
            return ApiResponse::error(
                "Error retrieving member",
                $th,
                500
            );
        }
    }
}
