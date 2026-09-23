<?php

namespace App\Filament\Resources\Pacientes\Pages;

use App\Filament\Resources\Pacientes\PacienteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewPaciente extends ViewRecord
{
    protected static string $resource = PacienteResource::class;

    public bool $showWeightChart = false;

    public bool $hasConsultations = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->hasConsultations = $this->getRecord()->consultas()->exists();
    }

    public function toggleWeightChart(): void
    {
        $this->showWeightChart = ! $this->showWeightChart;
    }

    public function getFooter(): ?View
    {
        return view('filament.resources.pacientes.pages.view-paciente-footer', [
            'hasConsultations' => $this->hasConsultations,
            'record' => $this->getRecord(),
            'showWeightChart' => $this->showWeightChart,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
