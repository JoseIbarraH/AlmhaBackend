<?php

namespace App\Domains\TeamMember\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

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

    public function translation()
    {
        return $this->hasOne(TeamMemberTranslation::class)->where('lang', app()->getLocale());
    }

    public function images()
    {
        return $this->hasMany(TeamMemberImage::class)->where('lang', app()->getLocale());
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => match (true) {
                empty($value) => null,
                str_starts_with($value, 'http') => $value,
                default => asset("storage/{$value}"),
            },
        );
    }

}
