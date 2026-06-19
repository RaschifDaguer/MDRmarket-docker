<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar CHECK constraints para garantizar rango 1-5 en estrellas
        // Nota: El soporte de CHECK constraints varía según el driver de BD:
        // - MySQL 8.0.16+: Soporta CHECK constraints nativos
        // - MariaDB 10.2+: Soporta CHECK constraints nativos
        // - SQLite: Soporta CHECK constraints nativos
        
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb' || $driver === 'sqlite') {
            // Usar DB::statement para ejecutar SQL raw con CHECK constraints
            DB::statement('
                ALTER TABLE calificaciones
                ADD CONSTRAINT check_estrellas_producto
                CHECK (estrellas_producto >= 1 AND estrellas_producto <= 5)
            ');
            
            DB::statement('
                ALTER TABLE calificaciones
                ADD CONSTRAINT check_estrellas_repartidor
                CHECK (estrellas_repartidor >= 1 AND estrellas_repartidor <= 5)
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb' || $driver === 'sqlite') {
            DB::statement('ALTER TABLE calificaciones DROP CONSTRAINT check_estrellas_producto');
            DB::statement('ALTER TABLE calificaciones DROP CONSTRAINT check_estrellas_repartidor');
        }
    }
};
