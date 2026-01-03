<?php

namespace App\Domains\TeamMember\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class TeamMemberImage extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'team_member_images';

    protected $fillable = [
        'team_member_id',
        'url',
        'description',
        'lang'
    ];

    protected $touches = ['teamMember'];

    public $timestamps = false;

    public function teamMember(){
        return $this->belongsTo(TeamMember::class);
    }

    protected function url(): Attribute
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
