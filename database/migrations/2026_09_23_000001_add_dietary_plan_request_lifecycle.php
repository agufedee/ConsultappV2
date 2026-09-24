<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicatesByConsulta = DB::table('planes_alimentarios as a')
            ->select('a.consulta_id')
            ->selectRaw('COUNT(*) as cantidad')
            ->groupBy('a.consulta_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('a.consulta_id')
            ->get()
            ->keyBy('consulta_id');

        if ($duplicatesByConsulta->isNotEmpty()) {
            $ids = $duplicatesByConsulta->keys()->implode(', ');

            throw new RuntimeException(
                'Cannot add a unique index on planes_alimentarios.consulta_id: '
                .'there are consultas with more than one plan alimentario request. '
                ."Consolidate them first. Consulta IDs: {$ids}"
            );
        }

        Schema::table('consultas', function (Blueprint $table) {
            $table->boolean('requiere_plan')->default(false)->after('proximo_control');
        });

        Schema::table('planes_alimentarios', function (Blueprint $table) {
            $table->string('estado', 30)->default('pending')->after('archivo_adjunto');
            $table->date('fecha_entrega')->nullable()->after('estado');
        });

        Schema::table('planes_alimentarios', function (Blueprint $table) {
            $table->unique('consulta_id', 'planes_alimentarios_consulta_id_unique');
            $table->index(['estado', 'created_at'], 'planes_alimentarios_estado_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('planes_alimentarios', function (Blueprint $table) {
            $table->dropIndex('planes_alimentarios_estado_created_at_index');
            $table->dropUnique('planes_alimentarios_consulta_id_unique');
        });

        Schema::table('planes_alimentarios', function (Blueprint $table) {
            $table->dropColumn(['estado', 'fecha_entrega']);
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropColumn('requiere_plan');
        });
    }
};
