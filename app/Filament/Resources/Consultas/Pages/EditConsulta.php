<?php

namespace App\Filament\Resources\Consultas\Pages;

use App\Filament\Resources\Consultas\ConsultaResource;
use App\Services\PlanAlimentarioRequestService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditConsulta extends EditRecord
{
    protected static string $resource = ConsultaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $plan = $this->record->planAlimentario;

        if ($plan !== null) {
            $data['requiere_plan'] = true;
            $data['estado'] = $plan->estado->value;
            $data['fecha_entrega'] = $plan->fecha_entrega?->format('Y-m-d');
            $data['archivo_adjunto'] = $plan->archivo_adjunto;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(PlanAlimentarioRequestService::class)->sync($this->record, $this->data);
    }
}
