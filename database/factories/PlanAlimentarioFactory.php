<?php

namespace Database\Factories;

use App\Enums\PlanAlimentarioStatus;
use App\Models\PlanAlimentario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanAlimentario>
 */
class PlanAlimentarioFactory extends Factory
{
    protected $model = PlanAlimentario::class;

    public function definition(): array
    {
        return [
            'consulta_id' => ConsultaFactory::new(),
            'objetivo_calorico' => fake()->randomElement([1200, 1500, 1800, 2000, 2200, 2500, 2800, 3000]),
            'descripcion' => fake('es_AR')->sentence(10),
            'archivo_adjunto' => null,
            'estado' => PlanAlimentarioStatus::Pending,
            'fecha_entrega' => null,
            'vigente_desde' => Carbon::today(),
            'vigente_hasta' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+6 months'),
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'estado' => PlanAlimentarioStatus::Delivered,
            'fecha_entrega' => Carbon::today(),
        ]);
    }
}
