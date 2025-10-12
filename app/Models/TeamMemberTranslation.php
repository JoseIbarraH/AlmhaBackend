<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMemberTranslation extends Model
{
    protected $fillable = [
        'team_member_id',
        'lang',
        'specialization',
        'biography',
    ];

    protected $touches = ['teamMember'];

    public function teamMember(){
        return $this->belongsTo(TeamMember::class);
    }
}
