<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureFaqTranslation extends Model
{
    protected $table = "procedure_faq_translations";
    protected $fillable = [
        'procedure_faq_id',
        'lang',
        'question',
        'answer'
    ];

}