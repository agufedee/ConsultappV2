<?php

use App\Models\Objetivo;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('objetivo can be created via factory', function () {
    $objetivo = Objetivo::factory()->create();

    expect($objetivo)->toBeInstanceOf(Objetivo::class);
    expect($objetivo->id)->not->toBeNull();
    expect($objetivo->tipo)->not->toBeEmpty();
    expect($objetivo->estado)->toBe('activo');
});

test('objetivo belongs to paciente', function () {
    $objetivo = Objetivo::factory()->create();

    expect($objetivo->paciente)->toBeInstanceOf(Paciente::class);
    expect($objetivo->paciente_id)->toBe($objetivo->paciente->id);
});

test('cascade delete via paciente', function () {
    $paciente = Paciente::factory()->create();
    Objetivo::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);

    $paciente->delete();

    expect(Objetivo::where('paciente_id', $paciente->id)->count())->toBe(0);
});
