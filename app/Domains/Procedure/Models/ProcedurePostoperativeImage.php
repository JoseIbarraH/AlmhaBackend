<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedurePostoperativeImage extends Model
{
    protected $table = "procedure_postoperative_images";

    protected $fillable = [
        'procedure_id',
        'image'
    ];

    public $timestamps = false;

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }


}