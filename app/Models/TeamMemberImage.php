<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMemberImage extends Model
{
    use HasFactory;

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
}
