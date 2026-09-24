<?php

use App\Enums\PlanAlimentarioStatus;
use App\Models\Consulta;
use App\Models\PlanAlimentario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use ValueError;

uses(RefreshDatabase::class);

test('plan alimentario can be created via factory', function () {
    $plan = PlanAlimentario::factory()->create();

    expect($plan)->toBeInstanceOf(PlanAlimentario::class);
    expect($plan->id)->not->toBeNull();
    expect($plan->objetivo_calorico)->toBeNumeric();
    expect($plan->vigente_desde)->not->toBeNull();
});

test('plan alimentario belongs to consulta', function () {
    $plan = PlanAlimentario::factory()->create();

    expect($plan->consulta)->toBeInstanceOf(Consulta::class);
    expect($plan->consulta_id)->toBe($plan->consulta->id);
});

test('cascade delete via consulta', function () {
    $consulta = Consulta::factory()->create();
    PlanAlimentario::factory()->forConsulta()->create(['consulta_id' => $consulta->id]);

    $consulta->delete();

    expect(PlanAlimentario::where('consulta_id', $consulta->id)->count())->toBe(0);
});

test('factory defaults a new request to pending without a delivery date', function () {
    $plan = PlanAlimentario::factory()->create();

    expect($plan->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fecha_entrega)->toBeNull();
});

test('a delivered request persists with its delivery date', function () {
    $plan = PlanAlimentario::factory()->create([
        'estado' => PlanAlimentarioStatus::Delivered,
        'fecha_entrega' => '2026-09-23',
    ]);

    expect($plan->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fecha_entrega->format('Y-m-d'))->toBe('2026-09-23');
});

test('a delivered request without a delivery date is rejected', function () {
    expect(fn () => PlanAlimentario::factory()->create([
        'estado' => PlanAlimentarioStatus::Delivered,
    ]))->toThrow(LogicException::class);

    expect(PlanAlimentario::count())->toBe(0);
});

test('delivery date is cleared when the request leaves delivered', function () {
    $plan = PlanAlimentario::factory()->create([
        'estado' => PlanAlimentarioStatus::Delivered,
        'fecha_entrega' => '2026-09-23',
    ]);

    $plan->estado = PlanAlimentarioStatus::PaymentPending;
    $plan->save();

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::PaymentPending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

test('an unknown status value is rejected by the enum cast', function () {
    expect(fn () => PlanAlimentario::factory()->create(['estado' => 'archived']))
        ->toThrow(ValueError::class);
});

test('status labels describe the Spanish workflow states', function () {
    expect(PlanAlimentarioStatus::Pending->label())->toBe('Pendiente');
    expect(PlanAlimentarioStatus::Delivered->label())->toBe('Entregado');
    expect(PlanAlimentarioStatus::PaymentPending->label())->toBe('Falta de pago');
});

test('a second active request for the same consulta is rejected', function () {
    $consulta = Consulta::factory()->create();
    PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    expect(fn () => PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]))
        ->toThrow(QueryException::class);
});
