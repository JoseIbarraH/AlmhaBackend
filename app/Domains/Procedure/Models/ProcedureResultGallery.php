<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureResultGallery extends Model
{

    protected $table = 'procedure_result_galleries';

    protected $fillable = [
        'procedure_id',
        'path'
    ];

    public $timestamps = false;

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}