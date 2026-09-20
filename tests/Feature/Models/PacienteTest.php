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

test('delete soft-deletes the paciente keeping related records', function () {
    $paciente = Paciente::factory()->create();
    Consulta::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);
    Objetivo::factory()->count(2)->forPaciente()->create(['paciente_id' => $paciente->id]);

    $paciente->delete();

    expect($paciente->trashed())->toBeTrue();
    expect($paciente->deleted_at)->not->toBeNull();
    expect(Paciente::find($paciente->id))->toBeNull();
    expect(Paciente::withTrashed()->find($paciente->id)->id)->toBe($paciente->id);
    expect(Consulta::where('paciente_id', $paciente->id)->count())->toBe(2);
    expect(Objetivo::where('paciente_id', $paciente->id)->count())->toBe(2);
});

test('restore brings a soft-deleted paciente back to default queries', function () {
    $paciente = Paciente::factory()->create();

    $paciente->delete();
    $paciente->restore();

    expect($paciente->trashed())->toBeFalse();
    expect(Paciente::find($paciente->id)->id)->toBe($paciente->id);
});

test('edad accessor returns full years since birth', function () {
    $paciente = Paciente::factory()->create([
        'fecha_nacimiento' => now()->subYears(30)->toDateString(),
    ]);

    expect($paciente->edad)->toBe(30);
});

test('edad accessor is null without fecha_nacimiento', function () {
    $paciente = Paciente::factory()->create(['fecha_nacimiento' => null]);

    expect($paciente->edad)->toBeNull();
});

test('edad accessor counts complete years only', function () {
    $beforeAnniversary = Paciente::factory()->create([
        'fecha_nacimiento' => now()->subYears(30)->addDay()->toDateString(),
    ]);
    $afterAnniversary = Paciente::factory()->create([
        'fecha_nacimiento' => now()->subYears(30)->subDay()->toDateString(),
    ]);

    expect($beforeAnniversary->edad)->toBe(29);
    expect($afterAnniversary->edad)->toBe(30);
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
