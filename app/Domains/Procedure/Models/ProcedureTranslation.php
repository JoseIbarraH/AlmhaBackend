<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureTranslation extends Model
{
    protected $table = "procedure_translations";
    protected $fillable = ['procedure_id', 'lang', 'title', 'subtitle'];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}