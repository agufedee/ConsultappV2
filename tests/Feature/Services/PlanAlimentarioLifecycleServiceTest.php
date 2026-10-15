<?php

use App\Enums\PlanAlimentarioStatus;
use App\Models\PlanAlimentario;
use App\Services\PlanAlimentarioLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

it('delivers a pending request with a delivery date', function () {
    $plan = PlanAlimentario::factory()->create();

    app(PlanAlimentarioLifecycleService::class)->deliver($plan, '2026-09-23');

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fresh()->fecha_entrega->format('Y-m-d'))->toBe('2026-09-23');
});

it('rejects delivering without a delivery date and leaves the request unchanged', function () {
    $plan = PlanAlimentario::factory()->create();

    expect(fn () => app(PlanAlimentarioLifecycleService::class)->deliver($plan, null))
        ->toThrow(InvalidArgumentException::class);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('rejects delivering with a blank delivery date', function () {
    $plan = PlanAlimentario::factory()->create();

    expect(fn () => app(PlanAlimentarioLifecycleService::class)->deliver($plan, '   '))
        ->toThrow(InvalidArgumentException::class);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('moves a request to payment pending without a delivery date or billing action', function () {
    $plan = PlanAlimentario::factory()->delivered()->create();

    app(PlanAlimentarioLifecycleService::class)->markPaymentPending($plan);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::PaymentPending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('clears the delivery date when a delivered request leaves delivered', function () {
    $plan = PlanAlimentario::factory()->create();

    $service = app(PlanAlimentarioLifecycleService::class);
    $service->deliver($plan, '2026-09-23');
    $service->markPaymentPending($plan);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::PaymentPending);
    expect($plan->fresh()->fecha_entrega)->toBeNull();
});

it('leaves the in-memory request untouched when delivery is rejected', function () {
    $plan = PlanAlimentario::factory()->create();

    expect(fn () => app(PlanAlimentarioLifecycleService::class)->deliver($plan, null))
        ->toThrow(InvalidArgumentException::class);

    expect($plan->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fecha_entrega)->toBeNull();
});

it('delivers a payment pending request because payment never blocks delivery', function () {
    $plan = PlanAlimentario::factory()->create([
        'estado' => PlanAlimentarioStatus::PaymentPending,
    ]);

    app(PlanAlimentarioLifecycleService::class)->deliver($plan, '2026-09-30');

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fresh()->fecha_entrega->format('Y-m-d'))->toBe('2026-09-30');
});
