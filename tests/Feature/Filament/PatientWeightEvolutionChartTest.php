<?php

use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Widgets\PatientWeightEvolutionChart;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function chartDataFor(Paciente $paciente): array
{
    $component = Livewire::test(PatientWeightEvolutionChart::class, [
        'record' => $paciente,
    ])->instance();
    $method = new ReflectionMethod($component, 'getData');

    return $method->invoke($component);
}

it('provides ordered weights for only the current patient', function () {
    $paciente = Paciente::factory()->create();
    $otherPaciente = Paciente::factory()->create();

    Consulta::factory()->create([
        'paciente_id' => $paciente->id,
        'fecha' => '2026-03-10',
        'peso' => 70,
    ]);
    Consulta::factory()->create([
        'paciente_id' => $paciente->id,
        'fecha' => '2026-01-15',
        'peso' => 72,
    ]);
    Consulta::factory()->create([
        'paciente_id' => $otherPaciente->id,
        'fecha' => '2026-02-01',
        'peso' => 99,
    ]);

    expect(chartDataFor($paciente))->toBe([
        'labels' => ['2026-01-15', '2026-03-10'],
        'datasets' => [[
            'label' => 'Weight (kg)',
            'data' => [72.0, 70.0],
        ]],
    ]);
});

it('returns one weight series with native tooltip data', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->create([
        'paciente_id' => $paciente->id,
        'fecha' => '2026-05-20',
        'peso' => 68.5,
    ]);

    $data = chartDataFor($paciente);

    expect($data['datasets'])->toHaveCount(1)
        ->and($data['datasets'][0]['label'])->toBe('Weight (kg)')
        ->and($data['labels'][0])->toBe('2026-05-20')
        ->and($data['datasets'][0]['data'][0])->toBe(68.5);
});

it('hides the chart by default and toggles it locally', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->create(['paciente_id' => $paciente->id]);

    Livewire::test(ViewPaciente::class, ['record' => $paciente->getRouteKey()])
        ->assertSee('Show weight evolution')
        ->assertDontSee('Weight evolution')
        ->call('toggleWeightChart')
        ->assertSee('Hide weight evolution')
        ->assertSee('Weight evolution')
        ->call('toggleWeightChart')
        ->assertSee('Show weight evolution')
        ->assertDontSee('Weight evolution');
});

it('resets visibility on a fresh patient page and keeps the footer after patient content', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->create(['paciente_id' => $paciente->id]);

    Livewire::test(ViewPaciente::class, ['record' => $paciente->getRouteKey()])
        ->call('toggleWeightChart')
        ->assertSeeInOrder(['Consultas', 'Weight evolution']);

    Livewire::test(ViewPaciente::class, ['record' => $paciente->getRouteKey()])
        ->assertSee('Show weight evolution')
        ->assertDontSee('Weight evolution');
});

it('omits the chart control and widget when the patient has no consultations', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(ViewPaciente::class, ['record' => $paciente->getRouteKey()])
        ->assertSee('Consultas')
        ->assertDontSee('Show weight evolution')
        ->assertDontSee('Weight evolution');
});
