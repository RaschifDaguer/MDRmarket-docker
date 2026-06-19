<?php

namespace Database\Factories;

use App\Models\Repartidor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repartidor>
 */
class RepartidorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->phoneNumber(),
            'placa' => $this->faker->bothify('???-####'),
            'tipo' => $this->faker->randomElement(['moto', 'bicicleta', 'auto', 'camion']),
        ];
    }
}
