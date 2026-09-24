<?php

use App\Enums\PlanAlimentarioStatus;
use App\Filament\Pages\DietaryPlanQueue;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('redirects unauthenticated visitors to panel authentication', function () {
    auth()->logout();

    $this->get(route('filament.consultapp.pages.planes-alimentarios', [], false))
        ->assertRedirect();
});

it('shows the patient and consultation context for each pending request', function () {
    $paciente = Paciente::factory()->create(['nombre' => 'Ana', 'apellido' => 'Gomez']);
    $consulta = Consulta::factory()->create([
        'paciente_id' => $paciente->id,
        'motivo' => 'control',
        'fecha' => '2026-09-01',
    ]);
    $plan = PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    Livewire::test(DietaryPlanQueue::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$plan])
        ->assertSee('Ana')
        ->assertSee('Gomez')
        ->assertSee('Control');
});

it('orders requests by creation time then identifier ascending', function () {
    $paciente = Paciente::factory()->create();
    $consultaA = Consulta::factory()->create(['paciente_id' => $paciente->id]);
    $consultaB = Consulta::factory()->create(['paciente_id' => $paciente->id]);
    $consultaC = Consulta::factory()->create(['paciente_id' => $paciente->id]);

    $older = PlanAlimentario::factory()->create([
        'consulta_id' => $consultaA->id,
        'created_at' => '2026-09-01 10:00:00',
    ]);
    $tieFirst = PlanAlimentario::factory()->create([
        'consulta_id' => $consultaB->id,
        'created_at' => '2026-09-02 10:00:00',
    ]);
    $tieSecond = PlanAlimentario::factory()->create([
        'consulta_id' => $consultaC->id,
        'created_at' => '2026-09-02 10:00:00',
    ]);

    Livewire::test(DietaryPlanQueue::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$older, $tieFirst, $tieSecond], inOrder: true);

    expect($tieFirst->id)->toBeLessThan($tieSecond->id);
});

it('delivers a request with an actual delivery date', function () {
    $plan = PlanAlimentario::factory()->create();

    Livewire::test(DietaryPlanQueue::class)
        ->callTableAction('deliver', $plan, ['fecha_entrega' => '2026-09-23'])
        ->assertHasNoTableActionErrors();

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fresh()->fecha_entrega->format('Y-m-d'))->toBe('2026-09-23');
});

it('rejects delivering without an actual delivery date', function () {
    $plan = PlanAlimentario::factory()->create();

    Livewire::test(DietaryPlanQueue::class)
        ->callTableAction('deliver', $plan, ['fecha_entrega' => null])
        ->assertHasTableActionErrors(['fecha_entrega' => 'required']);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('moves a delivered request to payment pending without billing', function () {
    $plan = PlanAlimentario::factory()->delivered()->create();

    Livewire::test(DietaryPlanQueue::class)
        ->callTableAction('markPaymentPending', $plan)
        ->assertHasNoTableActionErrors();

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::PaymentPending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('shows attachment availability without exposing a public url', function () {
    $withAttachment = PlanAlimentario::factory()->create(['archivo_adjunto' => 'plan.pdf']);
    $withoutAttachment = PlanAlimentario::factory()->create();

    Livewire::test(DietaryPlanQueue::class)
        ->assertOk()
        ->assertTableColumnFormattedStateSet('archivo_adjunto', 'PDF', record: $withAttachment)
        ->assertTableColumnFormattedStateSet('archivo_adjunto', '—', record: $withoutAttachment);
});

it('exposes the download action only for requests with an attachment', function () {
    $withAttachment = PlanAlimentario::factory()->create(['archivo_adjunto' => 'plan.pdf']);
    $withoutAttachment = PlanAlimentario::factory()->create();

    Livewire::test(DietaryPlanQueue::class)
        ->assertOk()
        ->assertTableActionVisible('download', $withAttachment)
        ->assertTableActionHidden('download', $withoutAttachment);
});

it('points the download action at the consultation-scoped route', function () {
    $consulta = Consulta::factory()->create();
    $plan = PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan.pdf',
    ]);

    Livewire::test(DietaryPlanQueue::class)
        ->assertTableActionHasUrl('download', route('plan-alimentario.download', $consulta), $plan);
});

it('provides no manual deletion action', function () {
    Livewire::test(DietaryPlanQueue::class)
        ->assertTableActionDoesNotExist(DeleteAction::class);
});
