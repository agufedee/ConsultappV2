<?php

namespace App\Filament\Resources\Pacientes\Pages;

use App\Filament\Resources\Pacientes\PacienteResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaciente extends CreateRecord
{
    protected static string $resource = PacienteResource::class;
}
