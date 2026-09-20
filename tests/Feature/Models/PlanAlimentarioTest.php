<?php

use App\Models\Consulta;
use App\Models\PlanAlimentario;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
