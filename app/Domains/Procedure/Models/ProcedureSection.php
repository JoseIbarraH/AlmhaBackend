<?php

namespace App\Domains\Procedure\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ProcedureSection extends Model
{
    protected $table = "procedure_sections";
    protected $fillable = ['procedure_id', 'type', 'image'];

    public $timestamps = false;

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function translations()
    {
        return $this->hasMany(ProcedureSectionTranslation::class);
    }

    public function translation()
    {
        return $this->hasOne(ProcedureSectionTranslation::class)
            ->where('lang', app()->getLocale());
    }

    protected function image(): Attribute
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
