<?php

namespace App\Filament\Resources\Consultas\Pages;

use App\Filament\Resources\Consultas\ConsultaResource;
use App\Services\PlanAlimentarioRequestService;
use Filament\Resources\Pages\CreateRecord;

class CreateConsulta extends CreateRecord
{
    protected static string $resource = ConsultaResource::class;

    protected function afterCreate(): void
    {
        app(PlanAlimentarioRequestService::class)->sync($this->record, $this->data);
    }
}
