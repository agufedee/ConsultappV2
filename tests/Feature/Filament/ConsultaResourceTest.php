<?php

use App\Filament\Resources\Consultas\ConsultaResource;
use App\Filament\Resources\Consultas\Pages\CreateConsulta;
use App\Filament\Resources\Consultas\Pages\EditConsulta;
use App\Filament\Resources\Consultas\Pages\ListConsultas;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the consultas list page with records', function () {
    $consultas = Consulta::factory()->count(2)->create();

    Livewire::test(ListConsultas::class)
        ->assertOk()
        ->assertCanSeeTableRecords($consultas);
});

it('orders consultas by fecha descending', function () {
    $older = Consulta::factory()->create(['fecha' => '2026-09-01']);
    $newer = Consulta::factory()->create(['fecha' => '2026-09-21']);

    Livewire::test(ListConsultas::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('shows the plural model heading on the list page', function () {
    Livewire::test(ListConsultas::class)
        ->assertOk()
        ->assertSee('Consultas');
});

it('computes and persists the imc on create', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('consultas', [
        'paciente_id' => $paciente->id,
        'fecha' => '2026-09-21 00:00:00',
        'motivo' => 'control',
        'peso' => 82.4,
        'altura' => 174,
        'imc' => 27.22,
    ]);
});

it('persists an extreme imc without a database error', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 300.00,
            'altura' => 100.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('consultas', ['imc' => 300.00]);
});

it('recomputes the imc when peso changes on edit', function () {
    $consulta = Consulta::factory()->create([
        'fecha' => '2026-09-01',
        'motivo' => 'control',
        'peso' => 70.00,
        'altura' => 170.00,
        'imc' => 24.22,
    ]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->set('data.peso', 80.00)
        ->assertFormSet(['imc' => 27.68]);
});

it('rejects create without a motivo', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'peso' => 70.00,
            'altura' => 170.00,
        ])
        ->call('create')
        ->assertHasFormErrors(['motivo' => 'required']);
});

it('rejects create without a fecha', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
        ])
        ->call('create')
        ->assertHasFormErrors(['fecha' => 'required']);
});

it('rejects an out-of-range peso on create', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 15.00,
            'altura' => 170.00,
        ])
        ->call('create')
        ->assertHasFormErrors(['peso']);
});

it('rejects an out-of-range altura on create', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 260.00,
        ])
        ->call('create')
        ->assertHasFormErrors(['altura']);
});

it('stores pliegues as a json array on create', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
            'pliegues_cutaneos' => [
                ['pliegue' => 'subescapular', 'mm' => 18],
                ['pliegue' => 'triceps', 'mm' => 12],
                ['pliegue' => 'biceps', 'mm' => 10],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $pliegues = Consulta::query()->first()->pliegues_cutaneos;

    expect($pliegues)->toHaveCount(3)
        ->and($pliegues[0])->toMatchArray(['pliegue' => 'subescapular', 'mm' => 18])
        ->and($pliegues[1])->toMatchArray(['pliegue' => 'triceps', 'mm' => 12]);
});

it('persists an emptied pliegues state as null', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'pliegues_cutaneos' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Consulta::query()->first()->pliegues_cutaneos)->toBeNull();
});

it('does not register navigation for the resource', function () {
    expect(ConsultaResource::shouldRegisterNavigation())->toBeFalse();
});

it('exposes the three motivo options with Spanish labels', function () {
    Livewire::test(CreateConsulta::class)
        ->assertOk()
        ->assertSee('Primera consulta', escape: false)
        ->assertSee('Control', escape: false)
        ->assertSee('Derivación', escape: false);

    $options = Livewire::test(CreateConsulta::class)
        ->instance()
        ->form
        ->getComponent('motivo')
        ->getOptions();

    expect($options)->toBe([
        'primera_consulta' => 'Primera consulta',
        'control' => 'Control',
        'derivacion' => 'Derivación',
    ]);
});
