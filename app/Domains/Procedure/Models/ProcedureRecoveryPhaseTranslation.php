<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureRecoveryPhaseTranslation extends Model
{
    protected $table = "procedure_recovery_phase_translations";
    protected $filename = [
        'procedure_recovery_phase_id',
        'lang',
        'period',
        'title',
        'description'
    ];

    public $timestamps = false;

    public function recoveryPhase()
    {
        return $this->belongsTo(ProcedureRecoveryPhase::class, 'procedure_recovery_phase_id');
    }
}