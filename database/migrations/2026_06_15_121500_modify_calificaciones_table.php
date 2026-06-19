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
        // Drop existing foreign key on id_pedido if present, then make column nullable and re-add FK
        $constraint = DB::select("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificaciones' AND COLUMN_NAME = 'id_pedido' LIMIT 1");
        if (! empty($constraint)) {
            $name = $constraint[0]->name;
            DB::statement("ALTER TABLE `calificaciones` DROP FOREIGN KEY `$name`");
        }

        DB::statement("ALTER TABLE `calificaciones` MODIFY `id_pedido` BIGINT UNSIGNED NULL");

        // Recreate foreign key to Pedido (nullable)
        DB::statement("ALTER TABLE `calificaciones` ADD CONSTRAINT `calificaciones_id_pedido_foreign` FOREIGN KEY (`id_pedido`) REFERENCES `Pedido`(`id`) ON DELETE CASCADE");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FK then set NOT NULL again
        $constraint = DB::select("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificaciones' AND COLUMN_NAME = 'id_pedido' LIMIT 1");
        if (! empty($constraint)) {
            $name = $constraint[0]->name;
            DB::statement("ALTER TABLE `calificaciones` DROP FOREIGN KEY `$name`");
        }

        DB::statement("ALTER TABLE `calificaciones` MODIFY `id_pedido` BIGINT UNSIGNED NOT NULL");

        DB::statement("ALTER TABLE `calificaciones` ADD CONSTRAINT `calificaciones_id_pedido_foreign` FOREIGN KEY (`id_pedido`) REFERENCES `Pedido`(`id`) ON DELETE CASCADE");
    }
};
