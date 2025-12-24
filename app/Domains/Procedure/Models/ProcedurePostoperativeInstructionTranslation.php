<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedurePostoperativeInstructionTranslation extends Model
{
    protected $table = "procedure_postoperative_instruction_translations";
    protected $fillable = [
        'procedure_postoperative_instruction_id',
        'lang',
        'content'
    ];

    public $timestamps = false;

    public function postoperativeInstruction()
    {
        return $this->belongsTo(ProcedurePostoperativeInstruction::class, 'procedure_postoperative_instruction_id');
    }
}