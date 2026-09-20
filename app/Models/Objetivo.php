<?php

namespace App\Models;

use Database\Factories\ObjetivoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['paciente_id', 'tipo', 'peso_objetivo', 'fecha_objetivo', 'estado'])]
class Objetivo extends Model
{
    /** @use HasFactory<ObjetivoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'peso_objetivo' => 'float',
            'fecha_objetivo' => 'date',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
