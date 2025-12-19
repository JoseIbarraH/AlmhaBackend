<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedurePostoperativeInstruction extends Model
{
    protected $table = "procedure_postoperative_instructions";
    protected $fillable = [
        'procedure_id',
        'type',
        'order'
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function translations()
    {
        return $this->hasMany(ProcedurePostoperativeInstructionTranslation::class);
    }

    public function translation()
    {
        return $this->hasOne(ProcedurePostoperativeInstructionTranslation::class)
            ->where('lang', app()->getLocale());
    }
}