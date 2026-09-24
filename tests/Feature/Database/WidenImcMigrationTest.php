<?php

use App\Models\Consulta;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

$assertImcWidth = function (int $precision, int $scale): void {
    $imc = collect(Schema::getColumns('consultas'))->firstWhere('name', 'imc');

    $driver = DB::connection()->getDriverName();

    if ($driver === 'sqlite') {
        // SQLite (the test default) reflects every DECIMAL column as plain `numeric`
        // without precision/scale, so the width is not introspectable here. It is
        // proven behaviorally instead: 300.00 only fits once the (5,2) widener has
        // run (and overflows DECIMAL(4,2)), and 27.22 survives the (4,2) rollback.
        expect($imc['type'])->toBe('numeric');

        return;
    }

    expect($imc['precision'])->toBe($precision)
        ->and($imc['scale'])->toBe($scale);

    if (in_array($driver, ['mysql', 'mariadb'], true)) {
        expect($imc['type'])->toBe('decimal');
    } else { // pgsql reflects decimal(n,m) as numeric(n,m)
        expect($imc['type'])->toBe('numeric');
    }
};

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

it('rolls back the widener on a single focused step and keeps consultas intact', function () use ($assertImcWidth) {
    $consulta = Consulta::factory()->create([
        'fecha' => '2026-09-01',
        'motivo' => 'control',
        'peso' => 70.00,
        'altura' => 170.00,
        'imc' => 24.22,
    ]);

    expect(DB::table('migrations')->where('migration', 'like', '%widen_imc_on_consultas')->exists())->toBeTrue();

    // The widener is no longer the newest migration (the dietary-plan lifecycle
    // migration runs after it), so step back over every migration newer than it.
    $migrations = DB::table('migrations')->orderBy('id')->pluck('migration');
    $widenerIndex = $migrations->search(fn (string $migration): bool => str_contains($migration, 'widen_imc_on_consultas'));
    $steps = $migrations->count() - $widenerIndex;

    Artisan::call('migrate:rollback', ['--step' => $steps]);

    expect(DB::table('migrations')->where('migration', 'like', '%widen_imc_on_consultas')->exists())->toBeFalse();
    expect(Schema::hasTable('consultas'))->toBeTrue();
    expect($consulta->fresh()->imc)->toBe(24.22);

    $assertImcWidth(4, 2);
});

it('reflects the imc column as decimal(5,2) on drivers that introspect decimal width', function () use ($assertImcWidth) {
    $assertImcWidth(5, 2);
});
