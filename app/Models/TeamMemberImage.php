<?php

namespace App\Models;

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
}
