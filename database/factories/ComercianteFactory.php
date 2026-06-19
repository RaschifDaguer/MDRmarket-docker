<?php

namespace Database\Factories;

use App\Models\Comerciante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comerciante>
 */
class ComercianteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->phoneNumber(),
            'direccion' => $this->faker->address(),
        ];
    }
}
