<?php

use App\Enums\PlanAlimentarioStatus;
use App\Filament\Resources\Consultas\ConsultaResource;
use App\Filament\Resources\Pacientes\PacienteResource;
use App\Filament\Widgets\OperationalDashboardWidget;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('redirects unauthenticated visitors to panel authentication', function () {
    auth()->logout();

    $this->get('/consultapp')->assertRedirect('/consultapp/login');
});

it('renders the operational desk widget for authenticated users', function () {
    $this->get('/consultapp')
        ->assertOk()
        ->assertSeeLivewire(OperationalDashboardWidget::class);
});

it('shows only the ten oldest pending plans', function () {
    $plans = collect(range(1, 12))->map(fn (int $day): PlanAlimentario => PlanAlimentario::factory()->create([
        'created_at' => '2026-09-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT).' 10:00:00',
    ]));

    Livewire::test(OperationalDashboardWidget::class)
        ->assertOk()
        ->assertCanSeeTableRecords($plans->take(10)->values()->all(), inOrder: true)
        ->assertCanNotSeeTableRecords($plans->slice(10)->values()->all());
});

it('orders pending plans by creation time then identifier', function () {
    $older = PlanAlimentario::factory()->create(['created_at' => '2026-09-01 10:00:00']);
    $tieFirst = PlanAlimentario::factory()->create(['created_at' => '2026-09-02 10:00:00']);
    $tieSecond = PlanAlimentario::factory()->create(['created_at' => '2026-09-02 10:00:00']);

    Livewire::test(OperationalDashboardWidget::class)
        ->assertCanSeeTableRecords([$older, $tieFirst, $tieSecond], inOrder: true);

    expect($tieFirst->id)->toBeLessThan($tieSecond->id);
});

it('renders patient and consultation context without lazy loading', function () {
    Model::preventLazyLoading();

    try {
        $paciente = Paciente::factory()->create(['nombre' => 'Ana', 'apellido' => 'Gomez']);
        $consulta = Consulta::factory()->create(['paciente_id' => $paciente->id, 'motivo' => 'control']);
        PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

        Livewire::test(OperationalDashboardWidget::class)
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('Gomez')
            ->assertSee('Control');
    } finally {
        Model::preventLazyLoading(false);
    }
});

it('counts payment pending separately and excludes it and delivered plans', function () {
    $pending = PlanAlimentario::factory()->create();
    PlanAlimentario::factory()->create(['estado' => PlanAlimentarioStatus::PaymentPending]);
    $other = PlanAlimentario::factory()->create(['estado' => PlanAlimentarioStatus::PaymentPending]);
    $delivered = PlanAlimentario::factory()->delivered()->create();

    Livewire::test(OperationalDashboardWidget::class)
        ->assertOk()
        ->assertSee('Falta de pago: 2')
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$other, $delivered]);
});

it('shows an empty state and keeps navigation when there are no pending plans', function () {
    Livewire::test(OperationalDashboardWidget::class)
        ->assertOk()
        ->assertSee('Sin planes pendientes')
        ->assertSee('Nueva consulta')
        ->assertSee('Nuevo paciente')
        ->assertSee('Buscar paciente')
        ->assertSee('Ver cola completa');
});

it('renders shortcuts and queue link with panel URLs', function () {
    Livewire::test(OperationalDashboardWidget::class)
        ->assertOk()
        ->assertSee(ConsultaResource::getUrl('create', panel: 'consultapp'))
        ->assertSee(PacienteResource::getUrl('create', panel: 'consultapp'))
        ->assertSee(PacienteResource::getUrl('index', panel: 'consultapp'))
        ->assertSee(route('filament.consultapp.pages.planes-alimentarios', [], false));
});

it('rejects delivering without a date and leaves the plan unchanged', function () {
    $plan = PlanAlimentario::factory()->create();

    Livewire::test(OperationalDashboardWidget::class)
        ->callTableAction('deliver', $plan, ['fecha_entrega' => null])
        ->assertHasTableActionErrors(['fecha_entrega' => 'required']);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('rejects delivering with a blank date and leaves every attribute unchanged', function () {
    $plan = PlanAlimentario::factory()->create();
    $attributesBefore = $plan->fresh()->getAttributes();

    Livewire::test(OperationalDashboardWidget::class)
        ->callTableAction('deliver', $plan, ['fecha_entrega' => '   '])
        ->assertHasTableActionErrors(['fecha_entrega' => 'required']);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fresh()->getAttributes())->toEqual($attributesBefore);
});

it('delivers a pending plan with a date and removes it from the list', function () {
    $plan = PlanAlimentario::factory()->create();

    Livewire::test(OperationalDashboardWidget::class)
        ->callTableAction('deliver', $plan, ['fecha_entrega' => '2026-09-23'])
        ->assertHasNoTableActionErrors()
        ->assertCanNotSeeTableRecords([$plan]);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fresh()->fecha_entrega->format('Y-m-d'))->toBe('2026-09-23');
});
