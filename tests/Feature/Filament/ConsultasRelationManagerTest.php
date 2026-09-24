<?php

use App\Enums\PlanAlimentarioStatus;
use App\Filament\Resources\Consultas\ConsultaResource;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\ConsultasRelationManager;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('mounts on the paciente view page', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->assertOk()
        ->assertSee('Consultas');
});

it('shows only the consultas of the owner paciente', function () {
    $paciente = Paciente::factory()->create();
    $otherPaciente = Paciente::factory()->create();

    $consulta = Consulta::factory()->create(['paciente_id' => $paciente->id]);
    $otherConsulta = Consulta::factory()->create(['paciente_id' => $otherPaciente->id]);

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$consulta])
        ->assertCanNotSeeTableRecords([$otherConsulta]);
});

it('orders consultas by fecha descending', function () {
    $paciente = Paciente::factory()->create();
    $older = Consulta::factory()->create(['paciente_id' => $paciente->id, 'fecha' => '2026-09-01']);
    $newer = Consulta::factory()->create(['paciente_id' => $paciente->id, 'fecha' => '2026-09-21']);

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('creates a consulta for the owner from the header create action', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('consultas', [
        'paciente_id' => $paciente->id,
        'fecha' => '2026-09-21 00:00:00',
        'motivo' => 'control',
        'peso' => 82.4,
        'altura' => 174,
        'imc' => 27.22,
    ]);
});

it('persists an extreme imc through the relation manager create action', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 300.00,
            'altura' => 100.00,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('consultas', [
        'paciente_id' => $paciente->id,
        'imc' => 300.00,
    ]);
});

it('rejects create without a motivo from the relation manager', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'fecha' => '2026-09-21',
            'peso' => 70.00,
            'altura' => 170.00,
        ])
        ->assertHasActionErrors(['motivo' => 'required']);

    $this->assertDatabaseMissing('consultas', ['paciente_id' => $paciente->id]);
});

it('edits a consulta from the table without changing the owner', function () {
    $paciente = Paciente::factory()->create();
    $consulta = Consulta::factory()->create([
        'paciente_id' => $paciente->id,
        'fecha' => '2026-09-01',
        'motivo' => 'control',
        'peso' => 70.00,
        'altura' => 170.00,
        'imc' => 24.22,
        'pliegues_cutaneos' => null,
    ]);

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->callAction(TestAction::make(EditAction::class)->table($consulta), [
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 80.00,
            'altura' => 170.00,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('consultas', [
        'id' => $consulta->id,
        'paciente_id' => $paciente->id,
        'peso' => 80.0,
        'altura' => 170,
        'imc' => 27.68,
    ]);
});

it('delegates the table configuration to the ConsultaResource', function () {
    $relatedResource = (new ReflectionClass(ConsultasRelationManager::class))
        ->getProperty('relatedResource')
        ->getValue();
    $relationship = (new ReflectionClass(ConsultasRelationManager::class))
        ->getProperty('relationship')
        ->getValue();

    expect($relatedResource)->toBe(ConsultaResource::class)
        ->and($relationship)->toBe('consultas');
});

it('shows a badge with the count of owner consultas', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->count(3)->create(['paciente_id' => $paciente->id]);

    expect(ConsultasRelationManager::getBadge($paciente, ViewPaciente::class))->toBe('3');
});

it('hides the badge when the owner has no consultas', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->create();

    expect(ConsultasRelationManager::getBadge($paciente, ViewPaciente::class))->toBeNull();
});

it('shows the consultas relation manager tab on the paciente view page', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->count(2)->create(['paciente_id' => $paciente->id]);

    Livewire::test(ViewPaciente::class, ['record' => $paciente->getRouteKey()])
        ->assertOk()
        ->assertSee('Consultas');
});

it('applies the requires-plan control from the relation manager create action', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(ConsultasRelationManager::class, [
        'ownerRecord' => $paciente,
        'pageClass' => ViewPaciente::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
            'requiere_plan' => true,
        ])
        ->assertHasNoActionErrors();

    expect(PlanAlimentario::count())->toBe(1);
    expect(PlanAlimentario::query()->first()->estado)->toBe(PlanAlimentarioStatus::Pending);
});
