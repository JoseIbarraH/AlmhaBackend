<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTranslation extends Model
{
    protected $table = 'role_translations';

    protected $fillable = [
        'role_id',
        'lang',
        'title',
        'description'
    ];

    public $timestamps = false;
}
