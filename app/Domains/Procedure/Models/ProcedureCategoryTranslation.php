<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class ProcedureCategoryTranslation extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'procedure_category_translations';

    protected $fillable = [
        'category_id',
        'lang',
        'title'
    ];

    public $timestamps = false;

    /**
     * Relación: Una traducción pertenece a una categoría
     */
    public function category()
    {
        return $this->belongsTo(ProcedureCategory::class, 'category_id');
    }
}
