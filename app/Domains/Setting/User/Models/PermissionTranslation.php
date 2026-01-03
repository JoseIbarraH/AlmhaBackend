<?php

namespace App\Domains\Setting\User\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionTranslation extends Model
{
    protected $table = 'permission_translations';

    protected $fillable = [
        'permission_id',
        'lang',
        'title',
        'description'
    ];

    public $timestamps = false;

}
