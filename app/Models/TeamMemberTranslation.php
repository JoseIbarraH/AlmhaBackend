<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMemberTranslation extends Model
{
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
