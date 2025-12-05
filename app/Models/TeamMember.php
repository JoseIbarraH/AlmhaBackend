<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMember extends Model implements Auditable
{
    use SoftDeletes;
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
}
