<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RoleTranslation;

class Role extends Model
{
    protected $fillable = ['code'];

    public function translations()
    {
        return $this->hasMany(RoleTranslation::class);
    }

    public function translate($lang = null)
    {
        $lang = $lang ?? app()->getLocale();
        return $this->translations()->where('lang', $lang)->first();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}

