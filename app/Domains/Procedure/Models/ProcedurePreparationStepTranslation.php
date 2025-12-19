<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedurePreparationStepTranslation extends Model
{
    protected $table = "procedure_preparation_step_translations";
    protected $fillable = [
        'procedure_preparation_step_id',
        'lang',
        'title',
        'description'
    ];

    public function preparationStep()
    {
        return $this->belongsTo(ProcedurePreparationStep::class, 'procedure_preparation_step_id');
    }
}