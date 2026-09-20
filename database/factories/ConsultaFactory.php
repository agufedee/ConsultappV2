<?php

namespace Database\Factories;

use App\Models\Consulta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consulta>
 */
class ConsultaFactory extends Factory
{
    protected $model = Consulta::class;

    public function definition(): array
    {
        $peso = fake()->randomFloat(2, 45, 150);
        $altura = fake()->randomFloat(2, 140, 200);

        return [
            'paciente_id' => PacienteFactory::new(),
            'fecha' => fake()->dateTimeBetween('-1 year', 'now'),
            'motivo' => fake()->randomElement(['primera_consulta', 'control', 'derivacion']),
            'peso' => $peso,
            'altura' => $altura,
            'imc' => round($peso / ($altura / 100) ** 2, 2),
            'circunferencia_cintura' => fake()->optional(0.6)->randomFloat(2, 60, 120),
            'circunferencia_cadera' => fake()->optional(0.6)->randomFloat(2, 70, 130),
            'porcentaje_grasa' => fake()->optional(0.5)->randomFloat(2, 10, 45),
            'pliegues_cutaneos' => fake()->optional(0.4)->passthrough(['subescapular' => rand(10, 40), 'triceps' => rand(8, 30)]),
            'observaciones' => fake()->optional(0.5)->sentence(4),
            'proximo_control' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+6 months'),
        ];
    }
}
