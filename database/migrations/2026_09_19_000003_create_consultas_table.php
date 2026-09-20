<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('motivo', 30);
            $table->decimal('peso', 5, 2);
            $table->decimal('altura', 5, 2);
            $table->decimal('imc', 4, 2)->nullable();
            $table->decimal('circunferencia_cintura', 5, 2)->nullable();
            $table->decimal('circunferencia_cadera', 5, 2)->nullable();
            $table->decimal('porcentaje_grasa', 4, 2)->nullable();
            $table->json('pliegues_cutaneos')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('proximo_control')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
