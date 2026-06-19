<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Pedido', function (Blueprint $table) {
            $table->string('direccion_entrega')->nullable()->after('id_ubicacion_entrega');
            $table->decimal('latitud_entrega', 10, 7)->nullable()->after('direccion_entrega');
            $table->decimal('longitud_entrega', 10, 7)->nullable()->after('latitud_entrega');
            $table->decimal('comisiones', 10, 2)->default(0.00)->after('costo_envio');
            $table->decimal('total', 10, 2)->nullable()->after('comisiones')->comment('Total del pedido: subtotal + envío + comisiones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Pedido', function (Blueprint $table) {
            $table->dropColumn(['direccion_entrega', 'latitud_entrega', 'longitud_entrega', 'comisiones', 'total']);
        });
    }
};
