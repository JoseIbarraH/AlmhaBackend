<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => match (true) {
                empty($value) => null,
                str_starts_with($value, 'http') => $value,
                default => asset("storage/{$value}"),
            },
        );
    }
}
