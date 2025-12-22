<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureSectionTranslation extends Model
{
    protected $table = "procedure_section_translations";
    protected $fillable = ['procedure_section_id', 'lang', 'title', 'content_one', 'content_two'];

    public $timestamps = false;

    public function section()
    {
        return $this->belongsTo(ProcedureSection::class, 'procedure_section_id');
    }
}