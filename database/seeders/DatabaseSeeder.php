<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Crea exactamente 9 usuarios reales en Santa Cruz de la Sierra:
     *   3 Comerciantes  (TechStore SCZ, Sabor Cruceño, FarmaSuper)
     *   3 Repartidores  (Diego Rodríguez, Valentina Cruz, Marco Flores)
     *   3 Clientes      (Ana García, Carlos López, Sofía Martínez)
     *
     * Para resetear el entorno: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call(InicializarMdrMarketSeeder::class);
    }
}
