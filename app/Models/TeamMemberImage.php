<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMemberImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_member_id',
        'url',
        'description',
        'lang'
    ];

    protected $touches = ['teamMember'];

    public function teamMember(){
        return $this->belongsTo(TeamMember::class);
    }
}
