<?php

namespace App\Models;

use Database\Factories\PacienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nombre', 'apellido', 'dni', 'fecha_nacimiento', 'sexo', 'telefono', 'email', 'antecedentes', 'fecha_alta'])]
class Paciente extends Model
{
    /** @use HasFactory<PacienteFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_alta' => 'date',
        ];
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function objetivos(): HasMany
    {
        return $this->hasMany(Objetivo::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }
}
