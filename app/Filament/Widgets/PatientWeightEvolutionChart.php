<?php

namespace App\Filament\Widgets;

use App\Models\Consulta;
use App\Models\Paciente;
use Filament\Widgets\ChartWidget;

class PatientWeightEvolutionChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    public Paciente $record;

    protected ?string $heading = 'Weight evolution';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $consultas = $this->record
            ->consultas()
            ->select(['fecha', 'peso'])
            ->orderBy('fecha')
            ->get();

        return [
            'labels' => $consultas->map(fn (Consulta $consulta): string => $consulta->fecha->format('Y-m-d'))->all(),
            'datasets' => [[
                'label' => 'Weight (kg)',
                'data' => $consultas->map(fn (Consulta $consulta): float => (float) $consulta->peso)->all(),
            ]],
        ];
    }
}
