<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedurePreparationStep extends Model
{
    protected $table = "procedure_preparation_steps";
    protected $fillable = ['procedure_id', 'order'];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function translations()
    {
        return $this->hasMany(ProcedurePreparationStepTranslation::class);
    }

    public function translation()
    {
        return $this->hasOne(ProcedurePreparationStepTranslation::class)
            ->where('lang', app()->getLocale());
    }
}