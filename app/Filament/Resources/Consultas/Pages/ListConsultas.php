<?php

namespace App\Filament\Resources\Consultas\Pages;

use App\Filament\Resources\Consultas\ConsultaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsultas extends ListRecords
{
    protected static string $resource = ConsultaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
