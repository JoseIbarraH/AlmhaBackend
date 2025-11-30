<?php

namespace App\Models;

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
    ];

    public $timestamps = false;

    protected $touches = ['teamMember'];

    public function teamMember(){
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
