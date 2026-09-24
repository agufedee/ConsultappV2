<?php

use App\Enums\PlanAlimentarioStatus;
use App\Filament\Resources\Consultas\ConsultaResource;
use App\Filament\Resources\Consultas\Pages\CreateConsulta;
use App\Filament\Resources\Consultas\Pages\EditConsulta;
use App\Filament\Resources\Consultas\Pages\ListConsultas;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

it('creates a pending plan alimentario request when the requires-plan control is enabled', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
            'requiere_plan' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $plan = PlanAlimentario::query()->first();

    expect($plan)->not->toBeNull();
    expect($plan->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fecha_entrega)->toBeNull();
});

it('leaves no request when the requires-plan control stays disabled', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
            'requiere_plan' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(PlanAlimentario::count())->toBe(0);
});

it('updates the existing request in place when editing a marked consultation', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    $plan = PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
            'estado' => 'delivered',
            'fecha_entrega' => '2026-09-23',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(PlanAlimentario::count())->toBe(1);
    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fresh()->fecha_entrega->format('Y-m-d'))->toBe('2026-09-23');
});

it('removes the request when the requires-plan control is disabled on edit', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(PlanAlimentario::count())->toBe(0);
});

it('requires a delivery date when the status is delivered', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
            'estado' => 'delivered',
            'fecha_entrega' => null,
        ])
        ->call('save')
        ->assertHasFormErrors(['fecha_entrega' => 'required']);

    expect($consulta->fresh()->planAlimentario->estado)->toBe(PlanAlimentarioStatus::Pending);
});

it('clears the delivery date when the status leaves delivered', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'estado' => PlanAlimentarioStatus::Delivered,
        'fecha_entrega' => '2026-09-23',
    ]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
            'estado' => 'payment_pending',
            'fecha_entrega' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $plan = $consulta->fresh()->planAlimentario;

    expect($plan->estado)->toBe(PlanAlimentarioStatus::PaymentPending);
    expect($plan->fecha_entrega)->toBeNull();
});

it('rejects a non-pdf attachment before persisting it', function () {
    $paciente = Paciente::factory()->create();

    Livewire::test(CreateConsulta::class)
        ->fillForm([
            'paciente_id' => $paciente->id,
            'fecha' => '2026-09-21',
            'motivo' => 'control',
            'peso' => 82.40,
            'altura' => 174.00,
            'requiere_plan' => true,
        ])
        ->upload('data.archivo_adjunto', [UploadedFile::fake()->create('plan.txt', 100, 'text/plain')])
        ->call('create')
        ->assertHasFormErrors(['archivo_adjunto']);

    expect(Consulta::count())->toBe(0);
    expect(PlanAlimentario::count())->toBe(0);
});

it('rejects an oversized attachment without replacing the existing one', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    $plan = PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'existing.pdf',
    ]);

    Livewire::test(EditConsulta::class, ['record' => $consulta->getRouteKey()])
        ->fillForm([
            'fecha' => '2026-09-01',
            'motivo' => 'control',
            'peso' => 70.00,
            'altura' => 170.00,
            'requiere_plan' => true,
        ])
        ->upload('data.archivo_adjunto', [UploadedFile::fake()->create('plan.pdf', 6000, 'application/pdf')])
        ->call('save')
        ->assertHasFormErrors(['archivo_adjunto']);

    expect($plan->fresh()->archivo_adjunto)->toBe('existing.pdf');
});
