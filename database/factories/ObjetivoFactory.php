<?php

namespace Database\Factories;

use App\Models\Objetivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Objetivo>
 */
class ObjetivoFactory extends Factory
{
    protected $model = Objetivo::class;

    public function definition(): array
    {
        return [
            'paciente_id' => PacienteFactory::new(),
            'tipo' => fake()->randomElement(['Descenso de peso', 'Hipertrofia', 'Control de glucemia', 'Mejora de composición corporal', 'Control tensional']),
            'peso_objetivo' => fake()->randomFloat(2, 50, 120),
            'fecha_objetivo' => fake()->optional(0.8)->dateTimeBetween('+1 month', '+1 year'),
            'estado' => 'activo',
        ];
    }
}
