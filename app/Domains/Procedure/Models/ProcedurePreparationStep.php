<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedurePreparationStep extends Model
{
    protected $table = "procedure_preparation_steps";
    protected $fillable = ['procedure_id', 'order'];

    public $timestamps = false;

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function translations()
    {
        return $this->hasMany(ProcedurePreparationStepTranslation::class, 'procedure_preparation_id', 'id');
    }

    public function translation()
    {
        return $this->hasOne(ProcedurePreparationStepTranslation::class, 'procedure_preparation_id', 'id')
            ->where('lang', app()->getLocale());
    }
}
