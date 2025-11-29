<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'team_members';

    protected $fillable = [
        'user_id',
        'name',
        'status',
        'image'
    ];

    public function translations()
    {
        return $this->hasMany(TeamMemberTranslation::class);
    }

    public function images()
    {
        return $this->hasMany(TeamMemberImage::class);
    }

    protected static function boot()
    {
        parent::boot();

        // SOLO SE MANTIENE EL BORRADO DE CARPETA (esto sí es propio del modelo)
        static::deleting(function ($teamMember) {
            $folderPath = "images/team/{$teamMember->id}";
            if (Storage::disk('public')->exists($folderPath)) {
                Storage::disk('public')->deleteDirectory($folderPath);
            }
        });
    }
}
