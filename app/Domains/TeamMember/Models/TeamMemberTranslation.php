<?php

namespace App\Domains\TeamMember\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TeamMemberTranslation extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'team_member_translations';

    protected $fillable = [
        'team_member_id',
        'lang',
        'specialization',
        'biography',
        'description',
    ];

    public $timestamps = false;

    protected $touches = ['teamMember'];

    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
