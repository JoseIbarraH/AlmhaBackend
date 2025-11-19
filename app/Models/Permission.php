<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'code'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function translations()
    {
        return $this->hasMany(PermissionTranslation::class);
    }

    public function translate($lang = null)
    {
        $lang = $lang ?? app()->getLocale();
        return $this->translations()->where('lang', $lang)->first();
    }

}
