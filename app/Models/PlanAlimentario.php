<?php

namespace App\Models;

use Database\Factories\PlanAlimentarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['consulta_id', 'objetivo_calorico', 'descripcion', 'archivo_adjunto', 'vigente_desde', 'vigente_hasta'])]
class PlanAlimentario extends Model
{
    /** @use HasFactory<PlanAlimentarioFactory> */
    use HasFactory;

    protected $table = 'planes_alimentarios';

    protected function casts(): array
    {
        return [
            'objetivo_calorico' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }
}
