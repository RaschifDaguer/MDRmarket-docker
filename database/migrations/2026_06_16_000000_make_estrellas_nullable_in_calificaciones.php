<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero eliminamos los CHECK constraints (para poder modificar las columnas)
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'calificaciones'
              AND CONSTRAINT_TYPE = 'CHECK'
        ");

        foreach ($constraints as $c) {
            DB::statement("ALTER TABLE `calificaciones` DROP CONSTRAINT `{$c->name}`");
        }

        // Hacer las columnas nullable
        DB::statement("ALTER TABLE `calificaciones` MODIFY `estrellas_producto` TINYINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `calificaciones` MODIFY `estrellas_repartidor` TINYINT UNSIGNED NULL");

        // Volver a agregar los CHECK constraints (MySQL acepta NULL como UNKNOWN → pasa el check)
        DB::statement("
            ALTER TABLE `calificaciones`
            ADD CONSTRAINT `check_estrellas_producto`
            CHECK (`estrellas_producto` IS NULL OR (`estrellas_producto` >= 1 AND `estrellas_producto` <= 5))
        ");
        DB::statement("
            ALTER TABLE `calificaciones`
            ADD CONSTRAINT `check_estrellas_repartidor`
            CHECK (`estrellas_repartidor` IS NULL OR (`estrellas_repartidor` >= 1 AND `estrellas_repartidor` <= 5))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `calificaciones` MODIFY `estrellas_producto` TINYINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE `calificaciones` MODIFY `estrellas_repartidor` TINYINT UNSIGNED NOT NULL");
    }
};
