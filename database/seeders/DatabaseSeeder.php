<?php

namespace Database\Seeders;

use App\Models\Consulta;
use App\Models\Objetivo;
use App\Models\Paciente;
use App\Models\PlanAlimentario;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Paciente::factory()
            ->count(rand(5, 10))
            ->create()
            ->each(function (Paciente $paciente) {
                Consulta::factory()
                    ->count(rand(2, 4))
                    ->forPaciente()
                    ->create(['paciente_id' => $paciente->id])
                    ->each(function (Consulta $consulta) {
                        if (rand(1, 100) <= 50) {
                            PlanAlimentario::factory()
                                ->forConsulta()
                                ->create(['consulta_id' => $consulta->id]);
                        }
                    });

                Objetivo::factory()
                    ->count(rand(1, 2))
                    ->forPaciente()
                    ->create(['paciente_id' => $paciente->id]);
            });
    }
}
