<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTranslation extends Model
{
    protected $fillable = [
        'role_id',
        'lang',
        'title',
        'description'
    ];
}
