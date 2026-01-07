<?php

namespace App\Domains\TeamMember\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TeamMemberImageTranslation extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'team_member_image_translations';

    protected $fillable = [
        'team_member_image_id',
        'lang',
        'description',
    ];

    public $timestamps = false;

    protected $touches = ['teamMemberImage'];

    public function teamMemberImage(){
        return $this->belongsTo(TeamMemberImage::class, 'team_member_image_id');
    }
}
