<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class RoleTranslation extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'role_translations';

    protected $fillable = [
        'role_id',
        'lang',
        'title',
        'description'
    ];

    public $timestamps = false;
}
