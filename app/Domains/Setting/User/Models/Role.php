<?php

namespace App\Domains\Setting\User\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Role extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    protected $table = 'roles';

    protected $fillable = ['code', 'status'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

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

