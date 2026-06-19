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
            $table->string('tipo_pago')->nullable()->comment('Tipo de pago: efectivo, qr, transferencia, tarjeta');
            $table->string('referencia_pago')->nullable()->comment('Referencia o ID del pago QR');
            $table->string('estado_pago')->default('pendiente')->comment('Estado del pago: pendiente, completado, rechazado');
            $table->decimal('monto_total', 10, 2)->nullable()->comment('Monto total a pagar (subtotal + envío)');
            $table->decimal('monto_pagado', 10, 2)->default(0.00)->comment('Monto que se ha pagado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Pedido', function (Blueprint $table) {
            $table->dropColumn(['tipo_pago', 'referencia_pago', 'estado_pago', 'monto_total', 'monto_pagado']);
        });
    }
};
