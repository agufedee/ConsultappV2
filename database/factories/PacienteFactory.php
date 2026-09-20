<?php

namespace Database\Factories;

use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    public function definition(): array
    {
        return [
            'nombre' => fake('es_AR')->firstName(),
            'apellido' => fake('es_AR')->lastName(),
            'dni' => fake()->unique()->numerify('########'),
            'fecha_nacimiento' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'sexo' => fake()->randomElement(['masculino', 'femenino', 'otro']),
            'telefono' => fake('es_AR')->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'antecedentes' => fake('es_AR')->sentence(6),
            'fecha_alta' => Carbon::today(),
        ];
    }
}
