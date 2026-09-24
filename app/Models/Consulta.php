<?php

namespace App\Models;

use Database\Factories\ConsultaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['paciente_id', 'fecha', 'motivo', 'peso', 'altura', 'imc', 'circunferencia_cintura', 'circunferencia_cadera', 'porcentaje_grasa', 'pliegues_cutaneos', 'observaciones', 'proximo_control', 'requiere_plan'])]
class Consulta extends Model
{
    /** @use HasFactory<ConsultaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'imc' => 'float',
            'pliegues_cutaneos' => 'array',
            'proximo_control' => 'date',
            'requiere_plan' => 'boolean',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function planAlimentario(): HasOne
    {
        return $this->hasOne(PlanAlimentario::class);
    }
}
