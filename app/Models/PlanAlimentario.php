<?php

namespace App\Models;

use App\Enums\PlanAlimentarioStatus;
use Database\Factories\PlanAlimentarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['consulta_id', 'objetivo_calorico', 'descripcion', 'archivo_adjunto', 'estado', 'fecha_entrega', 'vigente_desde', 'vigente_hasta'])]
class PlanAlimentario extends Model
{
    /** @use HasFactory<PlanAlimentarioFactory> */
    use HasFactory;

    protected $table = 'planes_alimentarios';

    protected static function booted(): void
    {
        static::saving(function (PlanAlimentario $plan) {
            if ($plan->estado === PlanAlimentarioStatus::Delivered && $plan->fecha_entrega === null) {
                throw new LogicException('A plan alimentario request cannot be delivered without a delivery date.');
            }

            if ($plan->estado !== PlanAlimentarioStatus::Delivered) {
                $plan->fecha_entrega = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'objetivo_calorico' => 'integer',
            'estado' => PlanAlimentarioStatus::class,
            'fecha_entrega' => 'date',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }
}
