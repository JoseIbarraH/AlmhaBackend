<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureRecoveryPhase extends Model
{
    protected $table = "procedure_recovery_phases";
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
        return $this->hasMany(ProcedureRecoveryPhaseTranslation::class, 'procedure_recovery_phase_id', 'id');
    }

    public function translation()
    {
        return $this->hasOne(ProcedureRecoveryPhaseTranslation::class)
            ->where('lang', app()->getLocale());
    }
}