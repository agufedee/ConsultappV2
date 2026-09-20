<?php

use App\Models\Consulta;
use App\Models\Objetivo;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('paciente can be created via factory', function () {
    $paciente = Paciente::factory()->create();

    expect($paciente)->toBeInstanceOf(Paciente::class);
    expect($paciente->id)->not->toBeNull();
    expect($paciente->nombre)->not->toBeEmpty();
    expect($paciente->apellido)->not->toBeEmpty();
});

test('nombre completo accessor returns concatenated name', function () {
    $paciente = Paciente::factory()->create([
        'nombre' => 'María',
        'apellido' => 'González',
    ]);

    expect($paciente->nombre_completo)->toBe('María González');
});

test('nombre completo accessor trims extra spaces', function () {
    $paciente = Paciente::factory()->create([
        'nombre' => '',
        'apellido' => 'González',
    ]);

    expect($paciente->nombre_completo)->toBe('González');
});

test('paciente has many consultas', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->count(3)->forPaciente()->create(['paciente_id' => $paciente->id]);

    expect($paciente->consultas)->toHaveCount(3);
});

test('paciente has many objetivos', function () {
    $paciente = Paciente::factory()->create();
    Objetivo::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);

    expect($paciente->objetivos)->toHaveCount(2);
});

test('cascade delete removes consultas and objetivos', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);
    Objetivo::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);

    $paciente->delete();

    expect(Consulta::where('paciente_id', $paciente->id)->count())->toBe(0);
    expect(Objetivo::where('paciente_id', $paciente->id)->count())->toBe(0);
});

test('factory overrides work', function () {
    $paciente = Paciente::factory()->create([
        'nombre' => 'Test',
        'apellido' => 'Override',
        'sexo' => 'masculino',
    ]);

    expect($paciente->nombre)->toBe('Test');
    expect($paciente->apellido)->toBe('Override');
    expect($paciente->sexo)->toBe('masculino');
});
