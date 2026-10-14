<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->renameColumn('porcentaje_grasa', 'masa_grasa');
            $table->string('masa_grasa_unidad', 2)->default('%')->after('masa_grasa');
            $table->decimal('masa_muscular', 5, 2)->nullable()->after('masa_grasa_unidad');
            $table->string('masa_muscular_unidad', 2)->default('%')->after('masa_muscular');
        });

        DB::table('consultas')->whereNotNull('masa_grasa')->update([
            'masa_grasa_unidad' => '%',
        ]);
    }

    public function down(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropColumn(['masa_muscular_unidad', 'masa_muscular']);
            $table->dropColumn('masa_grasa_unidad');
            $table->renameColumn('masa_grasa', 'porcentaje_grasa');
        });
    }
};
