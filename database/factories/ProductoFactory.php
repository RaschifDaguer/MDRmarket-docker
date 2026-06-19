<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_comerciante' => 1,
            'id_categoria' => 1,
            'nombre' => $this->faker->word(),
            'descripcion' => $this->faker->text(),
            'precio' => $this->faker->randomFloat(2, 1, 100),
            'stock' => $this->faker->numberBetween(5, 100),
            'id_imagen_principal' => null,
        ];
    }
}
