<?php

use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

function insertRawPlan(array $attributes): void
{
    DB::table('planes_alimentarios')->insert([
        'consulta_id' => $attributes['consulta_id'],
        'objetivo_calorico' => $attributes['objetivo_calorico'] ?? null,
        'descripcion' => $attributes['descripcion'] ?? null,
        'archivo_adjunto' => $attributes['archivo_adjunto'] ?? null,
        'vigente_desde' => $attributes['vigente_desde'] ?? '2026-09-01',
        'vigente_hasta' => $attributes['vigente_hasta'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('applies the lifecycle migration on a fresh database', function () {
    expect(DB::table('migrations')->where('migration', 'like', '%add_dietary_plan_request_lifecycle')->exists())
        ->toBeTrue();
});

it('adds the lifecycle columns with working database defaults', function () {
    expect(Schema::hasColumn('consultas', 'requiere_plan'))->toBeTrue();
    expect(Schema::hasColumn('planes_alimentarios', 'estado'))->toBeTrue();
    expect(Schema::hasColumn('planes_alimentarios', 'fecha_entrega'))->toBeTrue();

    $paciente = Paciente::factory()->create();

    DB::table('consultas')->insert([
        'paciente_id' => $paciente->id,
        'fecha' => '2026-09-23',
        'motivo' => 'control',
        'peso' => 70.00,
        'altura' => 170.00,
        'imc' => 24.22,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $consultaId = DB::table('consultas')->value('id');

    insertRawPlan(['consulta_id' => $consultaId]);

    expect(DB::table('consultas')->value('requiere_plan'))->toBe(0);
    expect(DB::table('planes_alimentarios')->value('estado'))->toBe('pending');
    expect(DB::table('planes_alimentarios')->value('fecha_entrega'))->toBeNull();
});

it('keeps every existing planes_alimentarios column when the lifecycle migration runs', function () {
    $columns = array_map(fn (array $column): string => $column['name'], Schema::getColumns('planes_alimentarios'));

    expect($columns)->toContain(
        'id',
        'consulta_id',
        'objetivo_calorico',
        'descripcion',
        'archivo_adjunto',
        'vigente_desde',
        'vigente_hasta',
        'created_at',
        'updated_at',
        'estado',
        'fecha_entrega',
    );
});

it('rejects a second active request for the same consulta', function () {
    $consulta = Consulta::factory()->create();

    insertRawPlan(['consulta_id' => $consulta->id]);

    expect(fn () => insertRawPlan(['consulta_id' => $consulta->id]))
        ->toThrow(QueryException::class);

    expect(DB::table('planes_alimentarios')->where('consulta_id', $consulta->id)->count())->toBe(1);
});

it('rolls back only the lifecycle schema and preserves existing data', function () {
    $consulta = Consulta::factory()->create();
    PlanAlimentario::factory()->create(['consulta_id' => $consulta->id]);

    Artisan::call('migrate:rollback', ['--step' => 1]);

    expect(DB::table('migrations')->where('migration', 'like', '%add_dietary_plan_request_lifecycle')->exists())
        ->toBeFalse();
    expect(Schema::hasColumn('consultas', 'requiere_plan'))->toBeFalse();
    expect(Schema::hasColumn('planes_alimentarios', 'estado'))->toBeFalse();
    expect(Schema::hasColumn('planes_alimentarios', 'fecha_entrega'))->toBeFalse();
    expect(Schema::hasIndex('planes_alimentarios', 'planes_alimentarios_consulta_id_unique'))->toBeFalse();
    expect(DB::table('consultas')->count())->toBe(1);
    expect(DB::table('planes_alimentarios')->count())->toBe(1);
});

it('aborts with an actionable duplicate report before enforcing the unique index', function () {
    $consulta = Consulta::factory()->create();

    Artisan::call('migrate:rollback', ['--step' => 1]);

    insertRawPlan(['consulta_id' => $consulta->id]);
    insertRawPlan(['consulta_id' => $consulta->id]);

    expect(fn () => Artisan::call('migrate', ['--force' => true]))
        ->toThrow(RuntimeException::class, (string) $consulta->id);

    expect(DB::table('planes_alimentarios')->where('consulta_id', $consulta->id)->count())->toBe(2);
    expect(Schema::hasColumn('consultas', 'requiere_plan'))->toBeFalse();
    expect(Schema::hasIndex('planes_alimentarios', 'planes_alimentarios_consulta_id_unique'))->toBeFalse();
});
