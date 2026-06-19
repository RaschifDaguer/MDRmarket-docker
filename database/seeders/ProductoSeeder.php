<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        // IMPORTANTE: Desactivamos llaves foráneas temporalmente para poder truncar de forma segura
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('producto')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('producto')->insert([
            [
                'id_comerciante' => 11, // Usamos el ID 11 que ya vimos que existe en tu tabla de usuarios
                'id_categoria' => 1,
                'nombre' => 'Hamburguesa Clásica Pro',
                'descripcion' => 'Hamburguesa con queso, lechuga y tomate premium.',
                'precio' => 25.50,
                'stock' => 20,
                'en_oferta' => 0,
                'precio_oferta' => null,
                'id_imagen_principal' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_comerciante' => 11,
                'id_categoria' => 2,
                'nombre' => 'Medicamento Analgésico Plus',
                'descripcion' => 'Analgésico para el dolor leve y muscular.',
                'precio' => 18.00,
                'stock' => 50,
                'en_oferta' => 0,
                'precio_oferta' => null,
                'id_imagen_principal' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_comerciante' => 11,
                'id_categoria' => 3,
                'nombre' => 'Cerveza Artesanal Golden',
                'descripcion' => 'Cerveza artesanal 500ml helada.',
                'precio' => 12.00,
                'stock' => 40,
                'en_oferta' => 0,
                'precio_oferta' => null,
                'id_imagen_principal' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}