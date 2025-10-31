<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;


class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'image'
    ];

    public function teamMemberTranslations()
    {
        return $this->hasMany(teamMemberTranslation::class);
    }

    public function teamMemberImages()
    {
        return $this->hasMany(TeamMemberImage::class);
    }

    public static function getImagesById($id)
    {
        return self::findOrFail($id)->teamMemberImages;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($teamMember) {
            $folderPath = "images/team_members/{$teamMember->id}";
            if (Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->deleteDirectory($folderPath);
            }
        });
    }
}
