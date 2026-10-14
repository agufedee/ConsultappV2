<?php

use App\Models\Consulta;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

it('renames legacy fat percentage data and assigns the percentage unit', function () {
    $consulta = Consulta::factory()->create(['masa_grasa' => 18.50]);

    Artisan::call('migrate:rollback', ['--step' => 1]);
    Artisan::call('migrate');

    expect(Schema::hasColumn('consultas', 'porcentaje_grasa'))->toBeFalse();
    expect(DB::table('consultas')->where('id', $consulta->id)->value('masa_grasa'))->toBe(18.5);
    expect(DB::table('consultas')->where('id', $consulta->id)->value('masa_grasa_unidad'))->toBe('%');
});
