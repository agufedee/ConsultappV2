<?php

use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('consulta can be created via factory', function () {
    $consulta = Consulta::factory()->create();

    expect($consulta)->toBeInstanceOf(Consulta::class);
    expect($consulta->id)->not->toBeNull();
    expect($consulta->peso)->toBeNumeric();
    expect($consulta->altura)->toBeNumeric();
});

test('consulta belongs to paciente', function () {
    $consulta = Consulta::factory()->create();

    expect($consulta->paciente)->toBeInstanceOf(Paciente::class);
    expect($consulta->paciente_id)->toBe($consulta->paciente->id);
});

test('consulta has one plan alimentario', function () {
    $consulta = Consulta::factory()->create();
    PlanAlimentario::factory()->forConsulta()->create(['consulta_id' => $consulta->id]);

    expect($consulta->planAlimentario)->toBeInstanceOf(PlanAlimentario::class);
});

test('soft delete preserves consultas', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);

    $paciente->delete();

    expect(Consulta::where('paciente_id', $paciente->id)->count())->toBe(2);
});

test('imc is stored as float', function () {
    $consulta = Consulta::factory()->create([
        'peso' => 80.00,
        'altura' => 180.00,
        'imc' => 24.69,
    ]);

    expect(is_float((float) $consulta->imc))->toBeTrue();
    expect($consulta->imc)->toBe(24.69);
});

test('factory defaults requiere_plan to false', function () {
    $consulta = Consulta::factory()->create();

    expect($consulta->requiere_plan)->toBeFalse();
});

test('requiere_plan is persisted as a boolean', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => true]);

    expect($consulta->fresh()->requiere_plan)->toBeTrue();
});

test('an unmarked consulta has no plan alimentario request', function () {
    $consulta = Consulta::factory()->create(['requiere_plan' => false]);

    expect($consulta->planAlimentario)->toBeNull();
});
