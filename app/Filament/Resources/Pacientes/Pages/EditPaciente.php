<?php

namespace App\Filament\Resources\Pacientes\Pages;

use App\Filament\Resources\Pacientes\PacienteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPaciente extends EditRecord
{
    protected static string $resource = PacienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
