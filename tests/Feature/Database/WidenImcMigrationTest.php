<?php

use App\Models\Consulta;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

it('applies the widen_imc_on_consultas migration on a fresh database', function () {
    expect(DB::table('migrations')->where('migration', 'like', '%widen_imc_on_consultas')->exists())->toBeTrue();
});

it('persists an extreme imc after the widen_imc migration runs', function () {
    $consulta = Consulta::factory()->create([
        'peso' => 300.00,
        'altura' => 100.00,
        'imc' => 300.00,
    ]);

    expect($consulta->fresh()->imc)->toBe(300.0);
});

it('keeps every consulta column and the imc value when the widener is applied', function () {
    $consulta = Consulta::factory()->create([
        'fecha' => '2026-09-01',
        'motivo' => 'control',
        'peso' => 82.40,
        'altura' => 174.00,
        'imc' => 27.22,
    ]);

    expect($consulta->fresh()->imc)->toBe(27.22);

    $columns = array_column(Schema::getColumns('consultas'), 'name');

    expect($columns)->toContain(
        'id',
        'paciente_id',
        'fecha',
        'motivo',
        'peso',
        'altura',
        'imc',
        'circunferencia_cintura',
        'circunferencia_cadera',
        'porcentaje_grasa',
        'pliegues_cutaneos',
        'observaciones',
        'proximo_control',
        'created_at',
        'updated_at',
    );
});

it('rolls back only the widener on a single step and keeps consultas intact', function () {
    $consulta = Consulta::factory()->create([
        'fecha' => '2026-09-01',
        'motivo' => 'control',
        'peso' => 70.00,
        'altura' => 170.00,
        'imc' => 24.22,
    ]);

    expect(DB::table('migrations')->where('migration', 'like', '%widen_imc_on_consultas')->exists())->toBeTrue();

    Artisan::call('migrate:rollback', ['--step' => 1]);

    expect(DB::table('migrations')->where('migration', 'like', '%widen_imc_on_consultas')->exists())->toBeFalse();
    expect(Schema::hasTable('consultas'))->toBeTrue();
    expect($consulta->fresh()->imc)->toBe(24.22);
});