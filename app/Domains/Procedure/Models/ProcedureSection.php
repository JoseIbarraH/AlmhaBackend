<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureSection extends Model
{
    protected $table = "procedure_sections";
    protected $fillable = ['procedure_id', 'type', 'image', 'order'];

    public $timestamps = false;

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function translations()
    {
        return $this->hasMany(ProcedureSectionTranslation::class);
    }

    public function translation()
    {
        return $this->hasOne(ProcedureSectionTranslation::class)
            ->where('lang', app()->getLocale());
    }
}