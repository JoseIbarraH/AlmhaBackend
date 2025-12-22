<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureFaq extends Model
{
    protected $table = "procedures_faqs";
    protected $fillable = [
        'procedure_id',
        'order'
    ];

    public $timestamps = false;

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function translations()
    {
        return $this->hasMany(ProcedureFaqTranslation::class);
    }

    public function translation()
    {
        return $this->hasOne(ProcedureFaqTranslation::class)
            ->where('lang', app()->getLocale());
    }
}