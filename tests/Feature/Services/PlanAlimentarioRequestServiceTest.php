<?php

use App\Enums\PlanAlimentarioStatus;
use App\Models\Consulta;
use App\Models\PlanAlimentario;
use App\Services\PlanAlimentarioRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;

uses(RefreshDatabase::class);

it('creates a pending request when the consultation is marked', function () {
    $consulta = Consulta::factory()->create();

    app(PlanAlimentarioRequestService::class)->sync($consulta, ['requiere_plan' => true]);

    expect(PlanAlimentario::count())->toBe(1);

    $plan = PlanAlimentario::query()->first();

    expect($plan->consulta_id)->toBe($consulta->id);
    expect($plan->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fecha_entrega)->toBeNull();
});

it('creates nothing for an unmarked consultation', function () {
    $consulta = Consulta::factory()->create();

    app(PlanAlimentarioRequestService::class)->sync($consulta, ['requiere_plan' => false]);

    expect(PlanAlimentario::count())->toBe(0);
});

it('forces the first request to pending even when delivered is passed', function () {
    $consulta = Consulta::factory()->create();

    app(PlanAlimentarioRequestService::class)->sync($consulta, [
        'requiere_plan' => true,
        'estado' => 'delivered',
        'fecha_entrega' => '2026-09-23',
    ]);

    $plan = PlanAlimentario::query()->first();

    expect($plan->estado)->toBe(PlanAlimentarioStatus::Pending);
    expect($plan->fecha_entrega)->toBeNull();
});

it('updates the existing request in place', function () {
    $consulta = Consulta::factory()->create();
    $plan = PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    app(PlanAlimentarioRequestService::class)->sync($consulta, [
        'requiere_plan' => true,
        'estado' => 'delivered',
        'fecha_entrega' => '2026-09-23',
    ]);

    expect(PlanAlimentario::count())->toBe(1);

    expect($plan->fresh()->estado)->toBe(PlanAlimentarioStatus::Delivered);
    expect($plan->fresh()->fecha_entrega->format('Y-m-d'))->toBe('2026-09-23');
});

it('removes the request when the consultation is unmarked', function () {
    $consulta = Consulta::factory()->create();
    $plan = PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    app(PlanAlimentarioRequestService::class)->sync($consulta, ['requiere_plan' => false]);

    expect(PlanAlimentario::count())->toBe(0);
});

it('requires a delivery date when delivering through the service', function () {
    $consulta = Consulta::factory()->create();
    PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    expect(fn () => app(PlanAlimentarioRequestService::class)->sync($consulta, [
        'requiere_plan' => true,
        'estado' => 'delivered',
    ]))->toThrow(LogicException::class);
});

it('deletes the replaced object only after a successful replacement', function () {
    Storage::disk('local_private')->put('plan/old.pdf', 'old');

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/old.pdf',
    ]);

    $service = app(PlanAlimentarioRequestService::class);

    $service->sync($consulta, [
        'requiere_plan' => true,
        'archivo_adjunto' => [
            'plan/new.pdf',
        ],
    ]);

    Storage::disk('local_private')->put('plan/new.pdf', 'new');

    expect($consulta->fresh()->planAlimentario->archivo_adjunto)->toBe('plan/new.pdf');
    expect(Storage::disk('local_private')->exists('plan/new.pdf'))->toBeTrue();
    expect(Storage::disk('local_private')->exists('plan/old.pdf'))->toBeFalse();
});

it('retains the existing object when the database write fails', function () {
    Storage::disk('local_private')->put('plan/old.pdf', 'old');

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/old.pdf',
    ]);

    $service = app(PlanAlimentarioRequestService::class);

    expect(fn () => $service->sync($consulta, [
        'requiere_plan' => true,
        'estado' => PlanAlimentarioStatus::Delivered->value,
        'fecha_entrega' => null,
        'archivo_adjunto' => [
            'plan/new.pdf',
        ],
    ]))->toThrow(LogicException::class);

    expect($consulta->fresh()->planAlimentario->archivo_adjunto)->toBe('plan/old.pdf');
    expect(Storage::disk('local_private')->exists('plan/old.pdf'))->toBeTrue();
    expect(Storage::disk('local_private')->exists('plan/new.pdf'))->toBeFalse();
});

it('removes the stored object when the request is unmarked', function () {
    Storage::disk('local_private')->put('plan/old.pdf', 'old');

    $consulta = Consulta::factory()->create(['requiere_plan' => true]);
    PlanAlimentario::factory()->create([
        'consulta_id' => $consulta->id,
        'archivo_adjunto' => 'plan/old.pdf',
    ]);

    app(PlanAlimentarioRequestService::class)->sync($consulta, ['requiere_plan' => false]);

    expect(PlanAlimentario::count())->toBe(0);
    expect(Storage::disk('local_private')->exists('plan/old.pdf'))->toBeFalse();
});
